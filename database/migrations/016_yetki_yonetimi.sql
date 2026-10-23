-- Yetki profilleri ve sayfa / işlem izinleri
CREATE TABLE IF NOT EXISTS yetki_profilleri (
    id         SERIAL PRIMARY KEY,
    ad         VARCHAR(120) NOT NULL UNIQUE,
    tip        VARCHAR(20)  NOT NULL CHECK (tip IN ('admin', 'bayi', 'musteri')),
    aciklama   TEXT,
    sistem_mi  BOOLEAN NOT NULL DEFAULT FALSE,
    aktif      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS yetki_profil_izinleri (
    profil_id INT NOT NULL REFERENCES yetki_profilleri(id) ON DELETE CASCADE,
    izin_kodu VARCHAR(80) NOT NULL,
    PRIMARY KEY (profil_id, izin_kodu)
);

CREATE INDEX IF NOT EXISTS yetki_profil_izinleri_kod_idx ON yetki_profil_izinleri (izin_kodu);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS yetki_profil_id INT REFERENCES yetki_profilleri(id),
    ADD COLUMN IF NOT EXISTS bayi_kapsam VARCHAR(20)
        CHECK (bayi_kapsam IS NULL OR bayi_kapsam IN ('genel', 'kisisel'));

-- Varsayılan profiller
INSERT INTO yetki_profilleri (ad, tip, aciklama, sistem_mi) VALUES
    ('Yönetici (Tam Yetki)', 'admin', 'Tüm sayfalar ve işlemler. Yönetici rolüne atanır.', TRUE),
    ('Bayi Genel', 'bayi', 'Otopark operasyon kullanıcısı — lokasyondaki tüm araç ve hareketler.', TRUE),
    ('Bayi Kişisel', 'bayi', 'Otoparka bağlı kısıtlı kullanıcı — görüntüleme ve temel işlemler.', TRUE),
    ('Müşteri Portal', 'musteri', 'Firma portalı: kendi araçlarını ve hizmetlerini görür.', TRUE)
ON CONFLICT (ad) DO NOTHING;
