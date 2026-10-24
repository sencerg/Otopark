-- Raporlar hub + seçerek hazırla rapor izinlerini admin/bayi sistem profillerine ekle.
INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu)
SELECT p.id, k.kod
FROM yetki_profilleri p
CROSS JOIN (VALUES
    ('menu.raporlar'),
    ('menu.stok_ozet_raporu'),
    ('menu.giris_cikis_raporu')
) AS k(kod)
WHERE p.sistem_mi
  AND p.tip IN ('admin', 'bayi')
ON CONFLICT DO NOTHING;
