-- Detaylı Araç Ekle: otopark / bayi profillerine aç (sahada yapılır)
INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'menu.detayli_arac'
FROM yetki_profilleri p
WHERE p.sistem_mi
  AND p.tip = 'bayi'
  AND p.aktif
ON CONFLICT DO NOTHING;
