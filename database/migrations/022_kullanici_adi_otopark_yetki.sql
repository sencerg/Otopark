-- Giriş için kullanıcı adı + otopark yetki etiketleri
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS kullanici_adi VARCHAR(80);

CREATE UNIQUE INDEX IF NOT EXISTS users_kullanici_adi_uidx
    ON users (lower(kullanici_adi))
    WHERE kullanici_adi IS NOT NULL AND kullanici_adi <> '';

-- Sistem profil adlarını sadeleştir (tip aynı kalır: admin / bayi / musteri)
UPDATE yetki_profilleri SET ad = 'Otopark', aciklama = 'Otopark operasyon hesabı — lokasyondaki araç ve hareketler.', updated_at = NOW()
WHERE sistem_mi AND tip = 'bayi' AND ad = 'Bayi Genel';

UPDATE yetki_profilleri SET ad = 'Otopark Personel', aciklama = 'Otoparka bağlı personel — kısıtlı bireysel yetki.', updated_at = NOW()
WHERE sistem_mi AND tip = 'bayi' AND ad IN ('Bayi Kişisel', 'Bayi Kisisel');

UPDATE yetki_profilleri SET ad = 'Admin', aciklama = 'Tam yetkili yönetici hesabı.', updated_at = NOW()
WHERE sistem_mi AND tip = 'admin' AND ad = 'Yönetici (Tam Yetki)';
