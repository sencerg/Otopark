-- Depolama günlük fiyat geçmişi: araç içerideyken fiyat değişince dönemlere bölünür.
-- effective_to NULL = açık segment; gün d için geçerli aralık: effective_from <= d < effective_to (veya to NULL).
CREATE TABLE IF NOT EXISTS depolama_fiyat_gecmisi (
    id             BIGSERIAL PRIMARY KEY,
    bayi_id        INT NOT NULL REFERENCES bayiler(id),
    musteri_id     INT REFERENCES musteriler(id),
    arac_tipi_id   INT REFERENCES arac_tipleri(id),
    gunluk_fiyat   NUMERIC(12,2) NOT NULL CHECK (gunluk_fiyat >= 0),
    effective_from DATE NOT NULL,
    effective_to   DATE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by     BIGINT REFERENCES users(id),
    CHECK (effective_to IS NULL OR effective_to > effective_from)
);

CREATE UNIQUE INDEX IF NOT EXISTS depolama_fiyat_gecmisi_open_uidx
    ON depolama_fiyat_gecmisi (bayi_id, musteri_id, arac_tipi_id)
    NULLS NOT DISTINCT
    WHERE effective_to IS NULL;

CREATE INDEX IF NOT EXISTS depolama_fiyat_gecmisi_lookup_idx
    ON depolama_fiyat_gecmisi (bayi_id, musteri_id, arac_tipi_id, effective_from);

-- Mevcut tarifeleri geçmişe yaz (açık segment, 2000-01-01'den itibaren)
INSERT INTO depolama_fiyat_gecmisi (bayi_id, musteri_id, arac_tipi_id, gunluk_fiyat, effective_from, effective_to)
SELECT b.id, NULL, NULL,
       ROUND(COALESCE(NULLIF(b.gunluk_fiyat, 0), 0) * COALESCE(b.fiyat_carpani, 1), 2),
       DATE '2000-01-01', NULL
FROM bayiler b
WHERE COALESCE(NULLIF(b.gunluk_fiyat, 0), 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM depolama_fiyat_gecmisi g
      WHERE g.bayi_id = b.id AND g.musteri_id IS NULL AND g.arac_tipi_id IS NULL AND g.effective_to IS NULL
  );

INSERT INTO depolama_fiyat_gecmisi (bayi_id, musteri_id, arac_tipi_id, gunluk_fiyat, effective_from, effective_to)
SELECT f.bayi_id, f.musteri_id, f.arac_tipi_id, f.gunluk_fiyat, DATE '2000-01-01', NULL
FROM depolama_fiyatlari f
WHERE NOT EXISTS (
    SELECT 1 FROM depolama_fiyat_gecmisi g
    WHERE g.bayi_id = f.bayi_id
      AND g.musteri_id IS NOT DISTINCT FROM f.musteri_id
      AND g.arac_tipi_id IS NOT DISTINCT FROM f.arac_tipi_id
      AND g.effective_to IS NULL
);
