CREATE TABLE kullanici_gruplari (
    id  SERIAL PRIMARY KEY,
    ad  VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE bayiler (
    id     SERIAL PRIMARY KEY,
    ad     VARCHAR(150) NOT NULL UNIQUE,
    sehir  VARCHAR(80),
    adres  TEXT,
    aktif  BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE users (
    id                 BIGSERIAL PRIMARY KEY,
    name               VARCHAR(150) NOT NULL,
    email              VARCHAR(190) NOT NULL UNIQUE,
    password_hash      VARCHAR(255) NOT NULL,
    role               VARCHAR(30)  NOT NULL DEFAULT 'user',
    kullanici_grubu_id INT REFERENCES kullanici_gruplari(id),
    bayi_id            INT REFERENCES bayiler(id),
    telefon            VARCHAR(30),
    resim              VARCHAR(255),
    is_active          BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login_at      TIMESTAMPTZ,
    created_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
