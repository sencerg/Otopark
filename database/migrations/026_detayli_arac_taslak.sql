-- Detaylı araç ekleme taslakları (dosya yerine DB — canlıda kalıcı)
CREATE TABLE IF NOT EXISTS detayli_arac_taslaklari (
    id            VARCHAR(32) PRIMARY KEY,
    kullanici_id  BIGINT REFERENCES users(id),
    durum         VARCHAR(40) NOT NULL DEFAULT 'taslak',
    icerik        JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS detayli_arac_taslaklari_durum_idx
    ON detayli_arac_taslaklari (durum, updated_at DESC);

CREATE INDEX IF NOT EXISTS detayli_arac_taslaklari_kullanici_idx
    ON detayli_arac_taslaklari (kullanici_id);
