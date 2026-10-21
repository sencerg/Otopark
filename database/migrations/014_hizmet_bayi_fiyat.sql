-- Bayi, hizmet uygularken tutarı değiştirebilir mi? (admin katalog kilidi)
ALTER TABLE maliyet_tipleri
    ADD COLUMN IF NOT EXISTS bayi_fiyat_degistirebilir BOOLEAN NOT NULL DEFAULT TRUE;
