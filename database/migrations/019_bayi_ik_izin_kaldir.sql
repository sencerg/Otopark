-- İK ve Satın Alma menüsü bayi profillerinden kaldırılır (yalnızca yönetici varsayılanında kalır).
DELETE FROM yetki_profil_izinleri i
USING yetki_profilleri p
WHERE i.profil_id = p.id
  AND p.tip = 'bayi'
  AND i.izin_kodu IN ('menu.ik_dashboard', 'menu.destek');
