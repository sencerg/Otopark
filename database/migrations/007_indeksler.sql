-- Lokasyon, firma ve iş emri filtreleri için indeksler
CREATE INDEX IF NOT EXISTS araclar_bayi_idx ON araclar (bayi_id, stokta);
CREATE INDEX IF NOT EXISTS araclar_musteri_idx ON araclar (musteri_id);
CREATE INDEX IF NOT EXISTS araclar_marka_seri_idx ON araclar (marka_id, seri_id);
CREATE INDEX IF NOT EXISTS arac_hareketleri_bayi_idx ON arac_hareketleri (bayi_id, hareket_tipi, hareket_tarihi);
CREATE INDEX IF NOT EXISTS arac_hareketleri_musteri_idx ON arac_hareketleri (musteri_id);
CREATE INDEX IF NOT EXISTS arac_ekstreleri_bayi_idx ON arac_ekstreleri (bayi_id, islem_tarihi);
CREATE INDEX IF NOT EXISTS arac_ekstreleri_musteri_idx ON arac_ekstreleri (musteri_id);
CREATE INDEX IF NOT EXISTS arac_ekstreleri_is_emri_idx ON arac_ekstreleri (is_emri_id) WHERE is_emri_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS is_emri_araclari_arac_idx ON is_emri_araclari (arac_id);
CREATE INDEX IF NOT EXISTS is_emirleri_olusturan_idx ON is_emirleri (olusturan_id);
CREATE INDEX IF NOT EXISTS personeller_bayi_idx ON personeller (bayi_id);
CREATE INDEX IF NOT EXISTS users_bayi_idx ON users (bayi_id);
CREATE INDEX IF NOT EXISTS destek_talepleri_talep_eden_idx ON destek_talepleri (talep_eden_id);
CREATE INDEX IF NOT EXISTS islem_loglari_kullanici_idx ON islem_loglari (kullanici_id);

-- Araç tipi boş (tüm tipler) olan fiyat da müşteri + lokasyon başına tek olmalı
ALTER TABLE depolama_fiyatlari DROP CONSTRAINT IF EXISTS depolama_fiyatlari_musteri_id_bayi_id_arac_tipi_id_key;
ALTER TABLE depolama_fiyatlari ADD CONSTRAINT depolama_fiyatlari_tekil UNIQUE NULLS NOT DISTINCT (musteri_id, bayi_id, arac_tipi_id);
