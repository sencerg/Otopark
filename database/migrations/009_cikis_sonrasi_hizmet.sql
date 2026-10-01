-- Stoktan çıkışta ya da çıkıştan sonra yazılan hizmetler ilgili çıkış hareketine bağlanır.
ALTER TABLE arac_ekstreleri
    ADD COLUMN IF NOT EXISTS hareket_id BIGINT REFERENCES arac_hareketleri(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS cikis_sonrasi BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS idx_arac_ekstreleri_hareket ON arac_ekstreleri (hareket_id) WHERE hareket_id IS NOT NULL;
