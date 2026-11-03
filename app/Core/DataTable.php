<?php

declare(strict_types=1);

namespace App\Core;

/**
 * DataTables sunucu tarafı işleme. $from içinde FROM/JOIN, $where koşulları ve
 * $columns (istemci sütun adı => SQL ifadesi) verilir.
 */
final class DataTable
{
    public function __construct(
        private string $from,
        private array $columns,
        private array $where = [],
        private array $params = [],
        private array $searchable = [],
        private string $defaultOrder = '1 DESC',
    ) {
    }

    public function where(string $condition, array $params = []): self
    {
        $this->where[] = $condition;
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    private function whereSql(bool $withSearch): array
    {
        $where = $this->where;
        $params = $this->params;

        $fromQ = $_REQUEST['q'] ?? null;
        $fromDt = $_REQUEST['search']['value'] ?? null;
        $search = '';
        if (is_string($fromQ) && trim($fromQ) !== '') {
            $search = trim($fromQ);
        } elseif (is_string($fromDt)) {
            $search = trim($fromDt);
        }
        if ($withSearch && $search !== '' && $this->searchable) {
            $tokens = preg_split('/\s+/u', $search) ?: [];
            $tokenSql = [];
            foreach (array_values(array_filter($tokens, static fn ($t) => $t !== '')) as $t => $token) {
                $parts = [];
                foreach ($this->searchable as $i => $expr) {
                    $key = "dt_s{$t}_{$i}";
                    $parts[] = "{$expr}::text ILIKE :{$key} ESCAPE '\\'";
                    $params[$key] = self::like($token);
                    if (preg_match('/\.(plaka|sase)$/', $expr)) {
                        $norm = preg_replace('/[^0-9A-Za-z]/', '', $token) ?? '';
                        if ($norm !== '') {
                            $nkey = "dt_n{$t}_{$i}";
                            $parts[] = "regexp_replace(upper(coalesce({$expr}::text, '')), '[^0-9A-Z]', '', 'g') LIKE :{$nkey} ESCAPE '\\'";
                            $params[$nkey] = self::like(strtoupper($norm));
                        }
                    }
                }
                if ($parts) {
                    $tokenSql[] = '(' . implode(' OR ', $parts) . ')';
                }
            }
            if ($tokenSql) {
                $where[] = implode(' AND ', $tokenSql);
            }
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private static function like(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }

    private function select(): string
    {
        $parts = [];
        foreach ($this->columns as $alias => $expr) {
            $parts[] = "{$expr} AS \"{$alias}\"";
        }

        return 'SELECT ' . implode(', ', $parts) . ' FROM ' . $this->from;
    }

    private function orderSql(): string
    {
        $aliases = array_keys($this->columns);
        $order = $_REQUEST['order'][0] ?? null;
        $order = is_array($order) ? $order : null;
        $kolon = $order['column'] ?? null;
        $requested = is_numeric($kolon) ? ($_REQUEST['columns'][(int) $kolon]['data'] ?? null) : null;

        if (is_string($requested) && in_array($requested, $aliases, true)) {
            $dir = is_string($order['dir'] ?? null) && strtolower($order['dir']) === 'asc' ? 'ASC' : 'DESC';
            $sortExpr = $this->columns[$requested . '_sort'] ?? $this->columns[$requested];

            return " ORDER BY {$sortExpr} {$dir} NULLS LAST";
        }

        return ' ORDER BY ' . $this->defaultOrder;
    }

    /** Tüm satırlar (Excel dışa aktarma için). */
    public function all(): array
    {
        [$where, $params] = $this->whereSql(true);

        return Database::fetchAll($this->select() . $where . $this->orderSql(), $params);
    }

    public function sum(string $expr): float
    {
        [$where, $params] = $this->whereSql(true);

        return (float) (Database::fetch("SELECT COALESCE(SUM({$expr}), 0) AS t FROM {$this->from}{$where}", $params)['t'] ?? 0);
    }

    /** Filtrelenmiş satırların $label bazında adet ve $expr toplamı (büyükten küçüğe). */
    public function groupSum(string $label, string $expr, int $limit = 6): array
    {
        [$where, $params] = $this->whereSql(true);

        return Database::fetchAll(
            "SELECT {$label} AS ad, COUNT(*) AS adet, COALESCE(SUM({$expr}), 0) AS tutar FROM {$this->from}{$where}
             GROUP BY {$label} ORDER BY tutar DESC, ad LIMIT {$limit}",
            $params
        );
    }

    public function response(?callable $map = null, array $extra = []): never
    {
        [$baseWhere, $baseParams] = $this->whereSql(false);
        [$where, $params] = $this->whereSql(true);

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM {$this->from}{$baseWhere}", $baseParams)['c'];
        $filtered = $where === $baseWhere ? $total : (int) Database::fetch("SELECT COUNT(*) AS c FROM {$this->from}{$where}", $params)['c'];

        $start = max(0, (int) ($_REQUEST['start'] ?? 0));
        $length = (int) ($_REQUEST['length'] ?? 25);
        $limit = $length === -1 ? '' : ' LIMIT ' . min(max($length, 1), 1000) . ' OFFSET ' . $start;

        $rows = Database::fetchAll($this->select() . $where . $this->orderSql() . $limit, $params);
        if ($map) {
            $rows = array_map($map, $rows);
        }

        View::json(array_merge([
            'draw' => (int) ($_REQUEST['draw'] ?? 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ], $extra));
    }
}
