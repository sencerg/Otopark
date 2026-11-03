-- Müşteri fiyatı: Moex'in müşteriye kestiği tutar.
-- Bayi fiyatı: otopark anlaşması; yalnızca bayi ve Moex görür.
-- hizmet_fiyatlari.tutar müşteri fiyatıyla aynı kalır (eski okuyucular müşteri tutarını görür).

ALTER TABLE hizmet_fiyatlari
    ADD COLUMN IF NOT EXISTS musteri_fiyat NUMERIC(12,2),
    ADD COLUMN IF NOT EXISTS bayi_fiyat NUMERIC(12,2);

UPDATE hizmet_fiyatlari SET musteri_fiyat = tutar WHERE musteri_fiyat IS NULL;
UPDATE hizmet_fiyatlari SET bayi_fiyat = tutar WHERE bayi_fiyat IS NULL;

ALTER TABLE hizmet_fiyatlari ALTER COLUMN musteri_fiyat SET NOT NULL;
ALTER TABLE hizmet_fiyatlari ALTER COLUMN bayi_fiyat SET NOT NULL;

ALTER TABLE hizmet_fiyatlari DROP CONSTRAINT IF EXISTS hizmet_fiyatlari_musteri_fiyat_check;
ALTER TABLE hizmet_fiyatlari ADD CONSTRAINT hizmet_fiyatlari_musteri_fiyat_check CHECK (musteri_fiyat >= 0);
ALTER TABLE hizmet_fiyatlari DROP CONSTRAINT IF EXISTS hizmet_fiyatlari_bayi_fiyat_check;
ALTER TABLE hizmet_fiyatlari ADD CONSTRAINT hizmet_fiyatlari_bayi_fiyat_check CHECK (bayi_fiyat >= 0);

UPDATE hizmet_fiyatlari SET tutar = musteri_fiyat WHERE tutar IS DISTINCT FROM musteri_fiyat;

ALTER TABLE arac_ekstreleri
    ADD COLUMN IF NOT EXISTS bayi_tutar NUMERIC(14,2);

UPDATE arac_ekstreleri SET bayi_tutar = tutar WHERE bayi_tutar IS NULL;

ALTER TABLE arac_ekstreleri ALTER COLUMN bayi_tutar SET DEFAULT 0;
ALTER TABLE arac_ekstreleri ALTER COLUMN bayi_tutar SET NOT NULL;

ALTER TABLE depolama_fiyatlari
    ADD COLUMN IF NOT EXISTS bayi_gunluk_fiyat NUMERIC(12,2);

UPDATE depolama_fiyatlari SET bayi_gunluk_fiyat = gunluk_fiyat WHERE bayi_gunluk_fiyat IS NULL;

ALTER TABLE depolama_fiyatlari DROP CONSTRAINT IF EXISTS depolama_fiyatlari_bayi_gunluk_check;
ALTER TABLE depolama_fiyatlari ADD CONSTRAINT depolama_fiyatlari_bayi_gunluk_check CHECK (bayi_gunluk_fiyat IS NULL OR bayi_gunluk_fiyat >= 0);

ALTER TABLE depolama_fiyat_gecmisi
    ADD COLUMN IF NOT EXISTS bayi_gunluk_fiyat NUMERIC(12,2);

UPDATE depolama_fiyat_gecmisi SET bayi_gunluk_fiyat = gunluk_fiyat WHERE bayi_gunluk_fiyat IS NULL;
