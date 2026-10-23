-- Hizmet ücreti: müşteri × otopark (bayi) × hizmet.
-- Eski müşteri×hizmet satırları tüm aktif otoparklara çoğaltılır.
ALTER TABLE hizmet_fiyatlari DROP CONSTRAINT IF EXISTS hizmet_fiyatlari_musteri_id_maliyet_tipi_id_key;

ALTER TABLE hizmet_fiyatlari
    ADD COLUMN IF NOT EXISTS bayi_id INT REFERENCES bayiler(id) ON DELETE CASCADE;

INSERT INTO hizmet_fiyatlari (musteri_id, maliyet_tipi_id, bayi_id, tutar, created_at, updated_at)
SELECT f.musteri_id, f.maliyet_tipi_id, b.id, f.tutar, COALESCE(f.created_at, NOW()), NOW()
FROM hizmet_fiyatlari f
CROSS JOIN bayiler b
WHERE f.bayi_id IS NULL AND b.aktif;

DELETE FROM hizmet_fiyatlari WHERE bayi_id IS NULL;

ALTER TABLE hizmet_fiyatlari ALTER COLUMN bayi_id SET NOT NULL;

ALTER TABLE hizmet_fiyatlari
    DROP CONSTRAINT IF EXISTS hizmet_fiyatlari_musteri_bayi_tip_uq;
ALTER TABLE hizmet_fiyatlari
    ADD CONSTRAINT hizmet_fiyatlari_musteri_bayi_tip_uq UNIQUE (musteri_id, bayi_id, maliyet_tipi_id);

CREATE INDEX IF NOT EXISTS idx_hizmet_fiyatlari_bayi ON hizmet_fiyatlari (bayi_id);
CREATE INDEX IF NOT EXISTS idx_hizmet_fiyatlari_musteri_bayi ON hizmet_fiyatlari (musteri_id, bayi_id);
