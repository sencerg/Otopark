CREATE TABLE musteriler (
    id       SERIAL PRIMARY KEY,
    ad       VARCHAR(200) NOT NULL,
    vergi_no VARCHAR(20),
    telefon  VARCHAR(30),
    eposta   VARCHAR(190),
    aktif    BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE departmanlar (
    id  SERIAL PRIMARY KEY,
    ad  VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE personeller (
    id            SERIAL PRIMARY KEY,
    ad_soyad      VARCHAR(150) NOT NULL,
    departman_id  INT REFERENCES departmanlar(id),
    bayi_id       INT REFERENCES bayiler(id),
    telefon       VARCHAR(30),
    aktif         BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE arac_tipleri   (id SERIAL PRIMARY KEY, ad VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE kasa_tipleri   (id SERIAL PRIMARY KEY, ad VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE renkler        (id SERIAL PRIMARY KEY, ad VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE model_yillari  (id SERIAL PRIMARY KEY, yil SMALLINT NOT NULL UNIQUE);
CREATE TABLE yakit_tipleri  (id SERIAL PRIMARY KEY, ad VARCHAR(50) NOT NULL UNIQUE);
CREATE TABLE vites_tipleri  (id SERIAL PRIMARY KEY, ad VARCHAR(50) NOT NULL UNIQUE);

CREATE TABLE markalar (
    id  SERIAL PRIMARY KEY,
    ad  VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE seriler (
    id        SERIAL PRIMARY KEY,
    marka_id  INT NOT NULL REFERENCES markalar(id) ON DELETE CASCADE,
    ad        VARCHAR(100) NOT NULL,
    UNIQUE (marka_id, ad)
);

CREATE TABLE modeller (
    id       SERIAL PRIMARY KEY,
    seri_id  INT NOT NULL REFERENCES seriler(id) ON DELETE CASCADE,
    ad       VARCHAR(150) NOT NULL,
    UNIQUE (seri_id, ad)
);

CREATE TABLE envanterler (
    id  SERIAL PRIMARY KEY,
    ad  VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE donanimlar (
    id    SERIAL PRIMARY KEY,
    grup  VARCHAR(50)  NOT NULL,
    ad    VARCHAR(100) NOT NULL,
    UNIQUE (grup, ad)
);

CREATE TABLE hareket_nedenleri (
    id  SERIAL PRIMARY KEY,
    ad  VARCHAR(100) NOT NULL UNIQUE
);

-- BİA'da "Maliyet Tipi" ve "İş Emri Türü" aynı listedir.
CREATE TABLE maliyet_tipleri (
    id                SERIAL PRIMARY KEY,
    ad                VARCHAR(150) NOT NULL UNIQUE,
    varsayilan_tutar  NUMERIC(12,2) NOT NULL DEFAULT 0,
    depolama_mi       BOOLEAN NOT NULL DEFAULT FALSE,
    aktif             BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE depolama_fiyatlari (
    id            SERIAL PRIMARY KEY,
    musteri_id    INT REFERENCES musteriler(id) ON DELETE CASCADE,
    bayi_id       INT REFERENCES bayiler(id) ON DELETE CASCADE,
    arac_tipi_id  INT REFERENCES arac_tipleri(id) ON DELETE CASCADE,
    gunluk_fiyat  NUMERIC(12,2) NOT NULL,
    UNIQUE (musteri_id, bayi_id, arac_tipi_id)
);
