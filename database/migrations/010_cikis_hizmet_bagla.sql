-- 009'dan önce stoktan çıkışta yazılmış hizmetleri aynı gün yapılan çıkış hareketine bağlar.
UPDATE arac_ekstreleri e
SET hareket_id = h.id
FROM arac_hareketleri h
WHERE e.hareket_id IS NULL
  AND e.aciklama = 'Stoktan çıkış hizmeti'
  AND h.arac_id = e.arac_id
  AND h.hareket_tipi = 2
  AND h.hareket_tarihi::date = e.islem_tarihi;
