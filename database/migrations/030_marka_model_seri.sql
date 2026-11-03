-- Hiyerarşi: Marka → Model → Seri.
-- Eski seriler (markanın altı) model olur, eski modeller (serinin altı) seri olur.
-- Araçtaki işaretçiler aynı kaydı göstermeye devam eder.

ALTER TABLE araclar DROP CONSTRAINT IF EXISTS araclar_seri_id_fkey;
ALTER TABLE araclar DROP CONSTRAINT IF EXISTS araclar_model_id_fkey;
ALTER TABLE modeller DROP CONSTRAINT IF EXISTS modeller_seri_id_fkey;

ALTER TABLE modeller RENAME CONSTRAINT modeller_pkey TO _pkey_yaprak;
ALTER TABLE modeller RENAME CONSTRAINT modeller_seri_id_ad_key TO _uq_yaprak;

ALTER TABLE seriler RENAME TO _eski_seriler;
ALTER TABLE modeller RENAME TO _eski_modeller;

ALTER TABLE _eski_seriler RENAME TO modeller;
ALTER TABLE _eski_modeller RENAME COLUMN seri_id TO model_id;
ALTER TABLE _eski_modeller RENAME TO seriler;

ALTER TABLE modeller RENAME CONSTRAINT seriler_pkey TO modeller_pkey;
ALTER TABLE modeller RENAME CONSTRAINT seriler_marka_id_ad_key TO modeller_marka_id_ad_key;
ALTER TABLE modeller RENAME CONSTRAINT seriler_marka_id_fkey TO modeller_marka_id_fkey;

ALTER TABLE seriler RENAME CONSTRAINT _pkey_yaprak TO seriler_pkey;
ALTER TABLE seriler RENAME CONSTRAINT _uq_yaprak TO seriler_model_id_ad_key;

ALTER TABLE seriler
    ADD CONSTRAINT seriler_model_id_fkey
    FOREIGN KEY (model_id) REFERENCES modeller(id) ON DELETE CASCADE;

ALTER SEQUENCE seriler_id_seq RENAME TO _seq_modeller;
ALTER SEQUENCE modeller_id_seq RENAME TO seriler_id_seq;
ALTER SEQUENCE _seq_modeller RENAME TO modeller_id_seq;

ALTER TABLE araclar RENAME COLUMN seri_id TO _tmp_model_id;
ALTER TABLE araclar RENAME COLUMN model_id TO seri_id;
ALTER TABLE araclar RENAME COLUMN _tmp_model_id TO model_id;

ALTER TABLE araclar
    ADD CONSTRAINT araclar_model_id_fkey FOREIGN KEY (model_id) REFERENCES modeller(id);
ALTER TABLE araclar
    ADD CONSTRAINT araclar_seri_id_fkey FOREIGN KEY (seri_id) REFERENCES seriler(id);

UPDATE detayli_arac_taslaklari
SET icerik = jsonb_set(
    icerik,
    '{veri}',
    (icerik->'veri') || jsonb_build_object(
        'seri_id', COALESCE(icerik#>'{veri,model_id}', 'null'::jsonb),
        'model_id', COALESCE(icerik#>'{veri,seri_id}', 'null'::jsonb)
    )
)
WHERE jsonb_typeof(icerik->'veri') = 'object'
  AND ((icerik->'veri') ? 'seri_id' OR (icerik->'veri') ? 'model_id');
