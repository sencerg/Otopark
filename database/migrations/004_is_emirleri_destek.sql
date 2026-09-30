CREATE TABLE is_emirleri (
    id                 BIGSERIAL PRIMARY KEY,
    kod                VARCHAR(30) NOT NULL UNIQUE,
    maliyet_tipi_id    INT NOT NULL REFERENCES maliyet_tipleri(id),   -- İş Emri Türü
    musteri_id         INT NOT NULL REFERENCES musteriler(id),
    talep_tarihi       DATE NOT NULL DEFAULT CURRENT_DATE,
    istenen_tarih      DATE,
    durum              SMALLINT NOT NULL DEFAULT 1,  -- 1 Yapılacak, 2 Onay Bekliyor, 3 İşleme Alındı, 4 Tamamlandı, 5 Reddedildi, 6 İptal Edildi
    tamamlanma_tarihi  DATE,
    detaylar           TEXT,
    yorum              TEXT,
    arsiv              BOOLEAN NOT NULL DEFAULT FALSE,
    olusturan_id       BIGINT REFERENCES users(id),
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE is_emri_araclari (
    id          BIGSERIAL PRIMARY KEY,
    is_emri_id  BIGINT NOT NULL REFERENCES is_emirleri(id) ON DELETE CASCADE,
    arac_id     BIGINT NOT NULL REFERENCES araclar(id) ON DELETE CASCADE,
    durum       SMALLINT NOT NULL DEFAULT 1,  -- 1 Yapılacak, 2 Onay Bekliyor, 3 İşleme Alındı, 4 Tamamlandı
    UNIQUE (is_emri_id, arac_id)
);

CREATE TABLE is_emri_departmanlari (
    is_emri_id    BIGINT NOT NULL REFERENCES is_emirleri(id) ON DELETE CASCADE,
    departman_id  INT NOT NULL REFERENCES departmanlar(id) ON DELETE CASCADE,
    PRIMARY KEY (is_emri_id, departman_id)
);

CREATE TABLE is_emri_personelleri (
    is_emri_id    BIGINT NOT NULL REFERENCES is_emirleri(id) ON DELETE CASCADE,
    personel_id   INT NOT NULL REFERENCES personeller(id) ON DELETE CASCADE,
    PRIMARY KEY (is_emri_id, personel_id)
);

ALTER TABLE arac_ekstreleri
    ADD CONSTRAINT arac_ekstreleri_is_emri_fk FOREIGN KEY (is_emri_id) REFERENCES is_emirleri(id) ON DELETE SET NULL;

CREATE TABLE destek_talepleri (
    id            BIGSERIAL PRIMARY KEY,
    kod           VARCHAR(30) NOT NULL UNIQUE,
    konu          VARCHAR(200) NOT NULL,
    tur           SMALLINT NOT NULL DEFAULT 1,   -- 1 Teknik, 2 Operasyon, 3 Talep/Öneri
    durum         SMALLINT NOT NULL DEFAULT 1,   -- 1 Açık, 2 İşlemde, 3 Çözüldü, 4 Kapatıldı
    aciklama      TEXT,
    cevap         TEXT,
    talep_eden_id BIGINT REFERENCES users(id),
    arsiv         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE duyurular (
    id          SERIAL PRIMARY KEY,
    baslik      VARCHAR(200) NOT NULL,
    icerik      TEXT,
    tur         VARCHAR(20) NOT NULL DEFAULT 'duyuru',  -- duyuru, hatirlatma
    aktif       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE islem_loglari (
    id          BIGSERIAL PRIMARY KEY,
    modul       VARCHAR(50) NOT NULL,
    aciklama    VARCHAR(255) NOT NULL,
    ilgili_id   BIGINT,
    kullanici_id BIGINT REFERENCES users(id),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX islem_loglari_tarih_idx ON islem_loglari (created_at DESC);
