-- Detaylı Araç Ekle kaynağı + müşterinin müşterisi + ekspertiz özeti
ALTER TABLE araclar
    ADD COLUMN IF NOT EXISTS musteri_musterisi VARCHAR(150),
    ADD COLUMN IF NOT EXISTS kayit_kaynagi VARCHAR(30) NOT NULL DEFAULT 'standart',
    ADD COLUMN IF NOT EXISTS ekspertiz JSONB;

COMMENT ON COLUMN araclar.kayit_kaynagi IS 'standart | detayli | hizli | toplu';
COMMENT ON COLUMN araclar.musteri_musterisi IS 'Firma müşterisinin nihai / getiren tarafı (detaylı ekleme)';
