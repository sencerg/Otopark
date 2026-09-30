-- Her giriş hareketi, aynı aracın kendisinden sonraki ilk çıkış hareketiyle eşleşir (depolama süresi hesabı).
CREATE VIEW arac_konaklamalari AS
SELECT
    g.id            AS giris_hareket_id,
    g.arac_id,
    g.musteri_id,
    g.bayi_id,
    g.hareket_tarihi AS giris_tarihi,
    c.hareket_tarihi AS cikis_tarihi
FROM arac_hareketleri g
LEFT JOIN LATERAL (
    SELECT h.hareket_tarihi
    FROM arac_hareketleri h
    WHERE h.arac_id = g.arac_id
      AND h.hareket_tipi = 2
      AND NOT h.arsiv
      AND h.hareket_tarihi >= g.hareket_tarihi
    ORDER BY h.hareket_tarihi
    LIMIT 1
) c ON TRUE
WHERE g.hareket_tipi = 1 AND NOT g.arsiv;
