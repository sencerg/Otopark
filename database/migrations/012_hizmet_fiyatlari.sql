-- Firma (müşteri) bazlı hizmet ücretleri.
-- Otopark günlük depolama fiyatı / çarpanı bundan ayrıdır (bayiler.gunluk_fiyat, depolama_fiyatlari).
CREATE TABLE hizmet_fiyatlari (
    id               BIGSERIAL PRIMARY KEY,
    musteri_id       INT NOT NULL REFERENCES musteriler(id) ON DELETE CASCADE,
    maliyet_tipi_id  INT NOT NULL REFERENCES maliyet_tipleri(id) ON DELETE CASCADE,
    tutar            NUMERIC(12,2) NOT NULL CHECK (tutar >= 0),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (musteri_id, maliyet_tipi_id)
);

CREATE INDEX idx_hizmet_fiyatlari_tip ON hizmet_fiyatlari (maliyet_tipi_id);
