-- Proje Notları = sürüm geçmişi izni (eski karsilastirma/tema menü izinlerinin yerine)
INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'menu.surumler'
FROM yetki_profilleri p
WHERE p.sistem_mi
  AND p.tip IN ('admin', 'bayi')
ON CONFLICT DO NOTHING;

-- Eski menü izinlerini temizle (route doğrudan URL ile kalabilir)
DELETE FROM yetki_profil_izinleri
WHERE izin_kodu IN ('menu.karsilastirma', 'menu.tema');
