CREATE TABLE araclar (
    id                  BIGSERIAL PRIMARY KEY,
    sase                VARCHAR(30)  NOT NULL UNIQUE,
    plaka               VARCHAR(20),
    onceki_plaka        VARCHAR(20),
    park_kodu           VARCHAR(50),
    arac_durumu         SMALLINT,               -- 1 Sıfır, 2 İkinci El
    arac_tipi_id        INT REFERENCES arac_tipleri(id),
    kasa_tipi_id        INT REFERENCES kasa_tipleri(id),
    renk_id             INT REFERENCES renkler(id),
    marka_id            INT REFERENCES markalar(id),
    seri_id             INT REFERENCES seriler(id),
    model_id            INT REFERENCES modeller(id),
    model_yili_id       INT REFERENCES model_yillari(id),
    motor_hacmi         INT,
    motor_gucu          INT,
    yakit_tipi_id       INT REFERENCES yakit_tipleri(id),
    vites_tipi_id       INT REFERENCES vites_tipleri(id),
    km                  INT,
    yakit_durumu        SMALLINT,
    musteri_id          INT REFERENCES musteriler(id),
    bayi_id             INT REFERENCES bayiler(id),
    lokasyon_turu       SMALLINT,               -- 1 Açık, 2 Kapalı, 3 Her İkisi De
    lokasyon_detay      VARCHAR(150),
    proje_adi           VARCHAR(150),
    konsinye            BOOLEAN NOT NULL DEFAULT FALSE,
    stokta              BOOLEAN NOT NULL DEFAULT FALSE,
    stoga_giris_tarihi  TIMESTAMPTZ,
    stoktan_cikis_tarihi TIMESTAMPTZ,
    etiket_fiyati       NUMERIC(14,2),
    sigorta_tarihi      DATE,
    kasko_tarihi        DATE,
    muayene_tarihi      DATE,
    arsiv               BOOLEAN NOT NULL DEFAULT FALSE,
    olusturan_id        BIGINT REFERENCES users(id),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX araclar_plaka_idx ON araclar (plaka);
CREATE INDEX araclar_stok_idx ON araclar (stokta, bayi_id, musteri_id);

CREATE TABLE arac_envanterleri (
    arac_id      BIGINT NOT NULL REFERENCES araclar(id) ON DELETE CASCADE,
    envanter_id  INT    NOT NULL REFERENCES envanterler(id) ON DELETE CASCADE,
    PRIMARY KEY (arac_id, envanter_id)
);

CREATE TABLE arac_donanimlari (
    arac_id      BIGINT NOT NULL REFERENCES araclar(id) ON DELETE CASCADE,
    donanim_id   INT    NOT NULL REFERENCES donanimlar(id) ON DELETE CASCADE,
    PRIMARY KEY (arac_id, donanim_id)
);

CREATE TABLE arac_hareketleri (
    id                       BIGSERIAL PRIMARY KEY,
    arac_id                  BIGINT NOT NULL REFERENCES araclar(id) ON DELETE CASCADE,
    hareket_tipi             SMALLINT NOT NULL,     -- 1 Giriş, 2 Çıkış
    hareket_nedeni_id        INT REFERENCES hareket_nedenleri(id),
    musteri_id               INT REFERENCES musteriler(id),
    bayi_id                  INT REFERENCES bayiler(id),
    lokasyon_turu            SMALLINT,
    lokasyon_detay           VARCHAR(150),
    km                       INT,
    yakit_durumu             SMALLINT,
    teslim_eden              VARCHAR(150),
    teslim_eden_telefon      VARCHAR(30),
    teslim_eden_eposta       VARCHAR(190),
    teslim_alan              VARCHAR(150),
    teslim_alan_telefon      VARCHAR(30),
    teslim_alan_eposta       VARCHAR(190),
    teslim_alan_personel_id  INT REFERENCES personeller(id),
    sevkiyat_tipi            SMALLINT,              -- 1 Bireysel, 2 Vale, 3 Çekici
    sevkiyat_durumu          SMALLINT,              -- 1 Bekliyor, 2 Devam Ediyor, 3 Tamamlandı
    sofor_adi_soyadi         VARCHAR(150),
    sofor_telefon            VARCHAR(30),
    cekici_plakasi           VARCHAR(20),
    sevkiyat_kodu            VARCHAR(50),
    irsaliye_kodu            VARCHAR(50),
    aciklama                 TEXT,
    hareket_tarihi           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    arsiv                    BOOLEAN NOT NULL DEFAULT FALSE,
    kullanici_id             BIGINT REFERENCES users(id),
    created_at               TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX arac_hareketleri_arac_idx ON arac_hareketleri (arac_id, hareket_tarihi DESC);
CREATE INDEX arac_hareketleri_tarih_idx ON arac_hareketleri (hareket_tipi, hareket_tarihi);

-- BİA "arac_ekstreleri": araca yazılan ek hizmet / maliyet kalemleri
CREATE TABLE arac_ekstreleri (
    id               BIGSERIAL PRIMARY KEY,
    arac_id          BIGINT NOT NULL REFERENCES araclar(id) ON DELETE CASCADE,
    maliyet_tipi_id  INT NOT NULL REFERENCES maliyet_tipleri(id),
    musteri_id       INT REFERENCES musteriler(id),
    bayi_id          INT REFERENCES bayiler(id),
    tutar            NUMERIC(14,2) NOT NULL DEFAULT 0,
    aciklama         TEXT,
    irsaliye         VARCHAR(50),
    fatura_no        VARCHAR(50),
    fatura_tarihi    DATE,
    islem_tarihi     DATE NOT NULL DEFAULT CURRENT_DATE,
    is_emri_id       BIGINT,
    kullanici_id     BIGINT REFERENCES users(id),
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX arac_ekstreleri_tarih_idx ON arac_ekstreleri (islem_tarihi, maliyet_tipi_id);
CREATE INDEX arac_ekstreleri_arac_idx ON arac_ekstreleri (arac_id);

CREATE TABLE dosyalar (
    id           BIGSERIAL PRIMARY KEY,
    ilgili_tip   VARCHAR(30) NOT NULL,   -- arac, hareket, is_emri, destek
    ilgili_id    BIGINT NOT NULL,
    tur          VARCHAR(20) NOT NULL DEFAULT 'belge',  -- belge, fotograf
    tanim        VARCHAR(200),
    dosya_yolu   VARCHAR(255) NOT NULL,
    orijinal_ad  VARCHAR(255),
    kullanici_id BIGINT REFERENCES users(id),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX dosyalar_ilgili_idx ON dosyalar (ilgili_tip, ilgili_id);
