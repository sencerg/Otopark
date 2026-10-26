# Detaylı Araç — alan kataloğu

## Geri dönüş (eski / basit UI)

Müşteri mevcut akışı isterse:

```bash
git checkout checkpoint/detayli-arac-basit-v1 -- \
  app/Services/DetayliAracStore.php \
  app/Controllers/DetayliAracController.php \
  views/detayli_arac \
  public/assets/js/detayli-ekspertiz.js
```

Tag: `checkpoint/detayli-arac-basit-v1`  
Akış: Kontrol → Taraflar → Araç → Ekspertiz (hasar / mekanik / foto / lokasyon / kondisyon) → Özet

## Kaynaklar

| Kaynak | Dosya / konum |
| --- | --- |
| Basit UI | `DetayliAracStore` + `views/detayli_arac` |
| Excel (teslim formu / VIS handover) | `Teslim_Formu_Alan_Listesi.xlsx` |
| Excel JSON | `excel_alanlari.json` (188 alan) |
| Ortak şema (kod) | `app/Services/DetayliAracAlanSema.php` |

## Ortak iskelet

Excel adımları ekspertiz altına; stok alanları (marka, seri, müşteri…) basit taraflar/araç adımında kalır. Hasar şemasındaki paneller excel gövde kontrollerine `hasarEslestirme()` ile bağlanır.
