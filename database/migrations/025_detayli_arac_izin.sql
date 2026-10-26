-- menu.vis → menu.detayli_arac (görünen ad: Detaylı araç ekle)
UPDATE yetki_profil_izinleri
SET izin_kodu = 'menu.detayli_arac'
WHERE izin_kodu = 'menu.vis'
  AND NOT EXISTS (
      SELECT 1 FROM yetki_profil_izinleri x
      WHERE x.profil_id = yetki_profil_izinleri.profil_id
        AND x.izin_kodu = 'menu.detayli_arac'
  );

DELETE FROM yetki_profil_izinleri WHERE izin_kodu = 'menu.vis';

INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'menu.detayli_arac'
FROM yetki_profilleri p
WHERE p.sistem_mi AND p.tip = 'admin'
ON CONFLICT DO NOTHING;
