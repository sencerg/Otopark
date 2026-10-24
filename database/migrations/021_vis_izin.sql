-- Araç Ekspertiz (VIS) izni — yalnızca admin sistem profili (bağımsız modül).
INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, 'menu.vis'
FROM yetki_profilleri p
WHERE p.sistem_mi
  AND p.tip = 'admin'
ON CONFLICT DO NOTHING;
