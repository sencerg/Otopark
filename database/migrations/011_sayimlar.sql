CREATE TABLE sayimlar (
    id                   BIGSERIAL PRIMARY KEY,
    kod                  VARCHAR(30) NOT NULL UNIQUE,
    baslik               VARCHAR(200) NOT NULL,
    bayi_id              INT NOT NULL REFERENCES bayiler(id),
    sorumlu_personel_id  INT REFERENCES personeller(id) ON DELETE SET NULL,
    durum                SMALLINT NOT NULL DEFAULT 1,  -- 1 Devam Ediyor, 2 Tamamlandı
    tamamlanma_tarihi    TIMESTAMPTZ,
    arsiv                BOOLEAN NOT NULL DEFAULT FALSE,
    olusturan_id         BIGINT REFERENCES users(id),
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_sayimlar_bayi ON sayimlar (bayi_id, durum);

CREATE TABLE sayim_okutmalari (
    id             BIGSERIAL PRIMARY KEY,
    sayim_id       BIGINT NOT NULL REFERENCES sayimlar(id) ON DELETE CASCADE,
    sase           VARCHAR(30) NOT NULL,
    arac_id        BIGINT REFERENCES araclar(id) ON DELETE SET NULL,
    sonuc          SMALLINT NOT NULL,  -- 1 Stokta, 2 Lokasyonda Değil, 3 Sayımda Stoğa Alındı, 4 Bulunamadı
    okutan_id      BIGINT REFERENCES users(id),
    okutma_tarihi  TIMESTAMPTZ,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (sayim_id, sase)
);
CREATE INDEX idx_sayim_okutmalari_arac ON sayim_okutmalari (sayim_id, arac_id);
