-- Plakalar boşluksuz saklanır: "06 TX 584" → "06TX584"
UPDATE araclar
   SET plaka = NULLIF(regexp_replace(upper(coalesce(plaka, '')), '[^0-9A-Z]', '', 'g'), '')
 WHERE plaka IS NOT NULL;

UPDATE araclar
   SET onceki_plaka = NULLIF(regexp_replace(upper(coalesce(onceki_plaka, '')), '[^0-9A-Z]', '', 'g'), '')
 WHERE onceki_plaka IS NOT NULL;

UPDATE arac_hareketleri
   SET cekici_plakasi = NULLIF(regexp_replace(upper(coalesce(cekici_plakasi, '')), '[^0-9A-Z]', '', 'g'), '')
 WHERE cekici_plakasi IS NOT NULL;
