-- Hizmetin aynı araca birden fazla kez eklenip eklenemeyeceği (bayi engeli; admin istisna + özel fiyat).
ALTER TABLE maliyet_tipleri
    ADD COLUMN IF NOT EXISTS tekrar_eklenebilir BOOLEAN NOT NULL DEFAULT TRUE;

COMMENT ON COLUMN maliyet_tipleri.tekrar_eklenebilir IS
    'false = bayi aynı araca yalnızca 1 kez ekler; admin 2+ kez (sınırsız) özel fiyatla ekleyebilir.';
