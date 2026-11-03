ALTER TABLE users ADD COLUMN IF NOT EXISTS surum_eposta BOOLEAN NOT NULL DEFAULT TRUE;

CREATE TABLE IF NOT EXISTS duyuru_gruplari (
    id          SERIAL PRIMARY KEY,
    ad          VARCHAR(120) NOT NULL UNIQUE,
    aciklama    TEXT,
    sistem_kod  VARCHAR(40) UNIQUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS duyuru_grup_uyeleri (
    grup_id  INT NOT NULL REFERENCES duyuru_gruplari(id) ON DELETE CASCADE,
    user_id  BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    PRIMARY KEY (grup_id, user_id)
);

CREATE TABLE IF NOT EXISTS bildirimler (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tur         VARCHAR(30) NOT NULL,
    baslik      VARCHAR(200) NOT NULL,
    metin       TEXT,
    url         VARCHAR(300),
    okundu_at   TIMESTAMPTZ,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS bildirimler_kullanici_idx ON bildirimler (user_id, created_at DESC);

CREATE TABLE IF NOT EXISTS eposta_kuyruk (
    id             BIGSERIAL PRIMARY KEY,
    user_id        BIGINT REFERENCES users(id) ON DELETE SET NULL,
    alici          VARCHAR(190) NOT NULL,
    konu           VARCHAR(200) NOT NULL,
    govde          TEXT NOT NULL,
    durum          VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
    hata           TEXT,
    deneme         INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    gonderildi_at  TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS eposta_kuyruk_durum_idx ON eposta_kuyruk (durum, id);

CREATE TABLE IF NOT EXISTS surum_duyurulari (
    id            BIGSERIAL PRIMARY KEY,
    surum         VARCHAR(20) NOT NULL,
    baslik        VARCHAR(200) NOT NULL,
    metin         TEXT NOT NULL,
    zorunlu       BOOLEAN NOT NULL DEFAULT FALSE,
    gonderen_id   BIGINT REFERENCES users(id),
    alici_sayisi  INT NOT NULL DEFAULT 0,
    eposta_sayisi INT NOT NULL DEFAULT 0,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE SEQUENCE IF NOT EXISTS konusma_kod_seq;

CREATE TABLE IF NOT EXISTS konusmalar (
    id          BIGSERIAL PRIMARY KEY,
    tur         VARCHAR(20) NOT NULL,
    kod         VARCHAR(20) NOT NULL UNIQUE,
    konu        VARCHAR(200) NOT NULL,
    durum       VARCHAR(20) NOT NULL DEFAULT 'acik',
    oncelik     VARCHAR(20) NOT NULL DEFAULT 'normal',
    acan_id     BIGINT NOT NULL REFERENCES users(id),
    bayi_id     INT REFERENCES bayiler(id),
    musteri_id  INT REFERENCES musteriler(id),
    arac_id     BIGINT REFERENCES araclar(id),
    sorumlu_id  BIGINT REFERENCES users(id),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS konusmalar_taraf_idx ON konusmalar (bayi_id, musteri_id, updated_at DESC);

CREATE TABLE IF NOT EXISTS konusma_mesajlari (
    id          BIGSERIAL PRIMARY KEY,
    konusma_id  BIGINT NOT NULL REFERENCES konusmalar(id) ON DELETE CASCADE,
    user_id     BIGINT NOT NULL REFERENCES users(id),
    metin       TEXT NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS konusma_mesajlari_konusma_idx ON konusma_mesajlari (konusma_id, id);

INSERT INTO duyuru_gruplari (ad, aciklama, sistem_kod) VALUES
    ('Moex içi', 'Yönetici hesapları', 'moex'),
    ('Bayiler', 'Lokasyon kullanıcıları', 'bayi'),
    ('Müşteriler', 'Müşteri portalı kullanıcıları', 'musteri'),
    ('Tüm paydaşlar', 'Aktif kullanıcıların tamamı', 'herkes')
ON CONFLICT (sistem_kod) DO NOTHING;

INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'menu.mesaj'
FROM yetki_profilleri p
WHERE p.tip IN ('admin', 'bayi')
ON CONFLICT DO NOTHING;

INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'musteri.mesaj'
FROM yetki_profilleri p
WHERE p.tip = 'musteri'
ON CONFLICT DO NOTHING;
