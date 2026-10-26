# Detaylı Araç — alan kataloğu

## Geri dönüş (eski / basit UI)

Müşteri mevcut özet ekspertiz akışını isterse:

```bash
git checkout detayli-arac-v1 -- \
  app/Services/DetayliAracStore.php \
  app/Controllers/DetayliAracController.php \
  views/detayli_arac \
  public/assets/js/detayli-ekspertiz.js
```

Tag’ler (aynı commit): `detayli-arac-v1` · `checkpoint/detayli-arac-basit-v1`  
Akış: Kontrol → Taraflar → Araç → Ekspertiz (hasar / mekanik / foto / lokasyon / kondisyon) → Özet

## Kaynaklar

| Kaynak | Dosya / konum |
| --- | --- |
| Basit UI | `DetayliAracStore` + `views/detayli_arac` |
| Excel (teslim formu / VIS handover) | `Teslim_Formu_Alan_Listesi.xlsx` |
| Excel JSON | `teslim_formu_alanlari.json` (188 alan) |
| Basit UI envanteri | `sema_v1_baseline.json` |
| Ortak şema (kod) | `app/Services/DetayliAracAlanSema.php` |

## Ortak iskelet

Excel adımları ekspertiz altına; stok alanları (marka, seri, müşteri…) basit taraflar/araç adımında kalır. Hasar şemasındaki paneller excel gövde kontrollerine `hasarEslestirme()` ile bağlanır (~21 panel).
