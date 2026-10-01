# Otopark Modülü Kullanım Rehberi

Bu rehber, bilgisayar kullanmayı yeni öğrenmiş kişiler için hazırlanmıştır. Her adımı sırasıyla takip ederseniz, sistemi rahatça kullanabilirsiniz.

---

## 1. Sisteme Giriş

1. Tarayıcıdan `http://**` adresine gidin.
2. Kullanıcı adınızı ve şifrenizi yazın.
3. `Giriş Yap` düğmesine basın.

> **Not:** Şifrenizi unutursanız yöneticiden yeni şifre isteyin.

---

## 2. Ana Ekranı Tanıyalım

Soldaki menüde tüm işlemler var. Her menü satırına tıklayınca ilgili sayfa açılır.

- `Otoparklar`: Otopark listesi
- `Tarifeler`: Günlük ücretleri ayarlama
- `Müşteriler / Otopark Bayileri`: İş ortaklarını yönetme
- `Araçlar`: Otoparktaki tüm araç listesi
- `Müşteri Araçlarım`: Sadece kendi müşterinizin araçları
- `Teslim Alma / Teslim Etme`: Araç giriş-çıkış kayıtları
- `Teslim Formları`: Tutulan formların listesi
- `Giriş-Çıkış Kayıtları`: Tüm park hareketleri
- `Raporlar`: Doluluk ve durum ekranları

---

## 3. Otopark Yönetimi

### 3.1 Otopark Listesi
1. Soldan `Otoparklar`ı seçin.
2. Listeyi göreceksiniz. `Düzenle` ile kapasite ve bilgi değiştirebilirsiniz.

### 3.2 Tarife (Ücret) Belirleme
1. `Tarifeler` menüsüne tıklayın.
2. `Yeni Tarife Ekle` deyip günlük fiyatı yazın.
3. Kaydedin. Artık check-in sırasında bu tarife seçilebilir.

---

## 4. Araç İşlemleri

### 4.1 Araç Ekleme
1. `Araçlar > Yeni Araç` butonuna basın.
2. Formda plaka, VIN, marka, model, yıl ve otopark seçin.
3. Kapasite doluysa sistem uyarır. Başka otopark seçin.
4. Kaydedince araç listede görünür.

### 4.2 Araç Güncelleme
1. Listeden `Düzenle`ye basın.
2. Gerekli değişiklikleri yapın (ör. durum, park yeri).
3. `Güncelle` deyin.

### 4.3 Araç Durumu
- **Beklemede:** Henüz park edilmedi.
- **Otoparkta:** Parkta bulunuyor.
- **Müsait:** Teslim için hazır.
- **Teslim Edildi:** Parktan çıktı.

Durumu listeden `Gönder` veya `Çıkar` butonlarıyla değiştirebilirsiniz.

---

## 5. Teslim Alma (Check-in) Formu

### 5.1 Formu Açma
1. `Teslim Alma` menüsüne tıklayın.
2. `Araç Girişi` formu açılır.

### 5.2 Formu Doldurma
1. `Araç` ve `Otopark` seçin.
2. `Giriş Tarihi`, kilometre, teslim alan bilgilerini yazın.
3. Aşağıda dikey kartlar şeklinde birçok kontrol alanı vardır (kaporta, iç trim, lastik vb.). Tablet videosundaki gibi sırayla doldurun.
4. Fotoğraf istenen yerlerde dosya yükleyin.
5. En altta imza görselini ekleyin ve `Kaydet` deyin.

Form kaydolunca:
- `vehicle_parking` kaydı oluşur.
- `handover_form` (teslim alma tutanağı) kaydı oluşur.

---

## 6. Teslim Etme (Check-out) Formu

### 6.1 Formu Açma
1. `Teslim Etme` menüsüne girin.
2. Açılan sayfada `Araç Giriş Kaydı` listesinden çıkış yapacağınız kaydı seçin.

### 6.2 Ön Hesaplama Kartı
- Araç seçince kartta giriş/çıkış tarihleri, gün sayısı ve toplam tutar otomatik hesaplanır.
- `Çıkış Tarihi`ni güncellediğinizde kart yeniden hesaplar.

### 6.3 Formu Doldurma
1. Kilometre, alıcı bilgileri, `Yapılan İşlemler` ve notları yazın.
2. İmza dosyasını yükleyin.
3. `Çıkış Yap ve Tutanak Kaydet` butonuna basın.

Sonuç olarak:
- `vehicle_parking` kaydı checkout bilgileriyle güncellenir.
- Yeni bir `handover_form` (teslim etme tutanağı) oluşturulur.
- Sistem otomatik olarak `Giriş-Çıkış Detay` sayfasına yönlendirir.

---

## 7. Teslim Formları ve PDF

1. `Teslim Formları` menüsünden tüm tutanakları listeleyin.
2. `Detay` butonuyla formu açın.
3. Sağ üstteki `PDF İndir` butonu ile Türkçe karakter destekli PDF alabilirsiniz.

---

## 8. Giriş-Çıkış Kayıtları

1. `Giriş-Çıkış Kayıtları` menüsü; tüm check-in/check-out kayıtlarını gösterir.
2. Bir kayda tıklayınca detay sayfası açılır. Burada:
   - Araç bilgileri
   - Tarife bilgileri
   - Yapılan işlemler
   - İlgili teslim formları ve PDF linkleri
   görünür.

---

## 9. Raporlar


### 9.1 Durum Raporu
- `Otopark > Otopark Durum Raporu`: Hangi müşteri/bayi hangi aracı getirdi, kim teslim aldı, araç yeni mi kullanılmış mı gibi detayları listeler.

---

## 10. Müşteri Araçlarım

Müşteri kullanıcıları için özel listedir.

1. `Müşteri Araçlarım` menüsüne girin.
2. Sadece size ait araçları görürsünüz.
3. Uygunsa `Otoparka Gönder` butonuyla araç durumunu değiştirebilirsiniz.

---

## 11. Sık Yapılan Hatalar ve Çözümler

| Hata | Çözüm |
| --- | --- |
| “Bu otoparkta yer kalmamıştır” uyarısı | Başka otopark seçin veya kapasiteyi yükseltin. |
| “Bu araç için aktif bir otopark giriş kaydı bulunuyor” mesajı | Araç zaten otoparkta görünüyor; önce teslim etme işlemini tamamlayın. |
| Seçilen değer formda kayboluyor | Select alanını seçtikten sonra formu gönderin; sayfayı yenilerseniz seçimleri tekrar yapın. |
| PDF’de Türkçe karakter bozuk | Sayfayı yenileyip tekrar indirin; yeni sistem UTF-8’e uygun PDF üretir. |

Bu rehberi adım adım takip ederek otopark modülündeki tüm işlemleri rahatça yapabilirsiniz. İşlem sırasında ekranda çıkan uyarıları okuyup önerilen adımları izlemeyi unutmayın. İyi çalışmalar!
