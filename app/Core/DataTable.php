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

        $search = $_REQUEST['search']['value'] ?? $_REQUEST['q'] ?? '';
        $search = is_string($search) ? trim($search) : '';
        if ($withSearch && $search !== '' && $this->searchable) {
            $parts = [];
            foreach ($this->searchable as $i => $expr) {
                $parts[] = "{$expr}::text ILIKE :dt_s{$i}";
                $params["dt_s{$i}"] = '%' . $search . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
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
