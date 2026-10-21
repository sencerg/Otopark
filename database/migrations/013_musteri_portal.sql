-- Müşteri portalı: kullanıcı ↔ firma bağlantısı
ALTER TABLE users ADD COLUMN IF NOT EXISTS musteri_id INT REFERENCES musteriler(id);
CREATE INDEX IF NOT EXISTS idx_users_musteri ON users(musteri_id) WHERE musteri_id IS NOT NULL;
