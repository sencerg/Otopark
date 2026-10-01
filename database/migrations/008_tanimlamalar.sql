-- Lokasyon (otopark) standart günlük fiyatı ve fiyat çarpanı; müşteri iletişim alanları.
ALTER TABLE bayiler
    ADD COLUMN IF NOT EXISTS gunluk_fiyat NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (gunluk_fiyat >= 0),
    ADD COLUMN IF NOT EXISTS fiyat_carpani NUMERIC(6,3) NOT NULL DEFAULT 1 CHECK (fiyat_carpani > 0);

ALTER TABLE musteriler
    ADD COLUMN IF NOT EXISTS yetkili VARCHAR(150),
    ADD COLUMN IF NOT EXISTS adres TEXT;
