# BİA ↔ MD Karşılaştırması: İş Akışı ve Farklar

> Bu dosya uygulamadaki "BİA ↔ MD Karşılaştırma" sayfasından üretilir (30.09.2026 22:38). Kararlar sayfadan güncellenir.

## Kaynaklar

- **BİA:** BİA 360 Yönetim Paneli (bayi hesabıyla, salt okunur tarama)
- **MD:** otopark-modulu-kullanim.md (Otopark Modülü Kullanım Rehberi)
- **Sistem:** Bu proje (BİA akışına göre kuruldu)

## Özet

- İki kaynak aynı işi (başkasına ait araçları bir alanda tutup hizmet vermek) farklı bakış açılarıyla anlatıyor.
- BİA "stok ve maliyet" mantığıyla çalışır: araç stoğa girer, stoktayken gün ve ek hizmetler birikir, dönem sonunda depolama ve ek hizmet raporlarıyla firmaya faturalanır.
- MD "otopark ve tutanak" mantığıyla çalışır: araç önce kaydedilir, teslim alma formu ile otoparka girer, teslim etme formu ile çıkar; her giriş ve çıkışta imzalı tutanak (PDF) oluşur ve ücret çıkışta gün × tarife olarak hesaplanır.
- Müşterinin takıldığı yerler büyük ölçüde sözcüklerden (Otopark ↔ Lokasyon, Tarife ↔ Depolama Fiyatı, Teslim Alma ↔ Stoğa Giriş) ve durum akışından (Beklemede/Otoparkta/Müsait/Teslim Edildi ↔ Stokta/Stoktan Çıktı) kaynaklanıyor.
- Şu anki sistem tamamen BİA sözcükleri ve akışıyla çalışıyor. MD'ye özgü özellikler (kapasite, imzalı tutanak, ön hesaplama kartı, müşteri kullanıcısı) henüz yok; aşağıdaki maddelerden karar verilerek eklenebilir.

## Sözlük (MD terimi → BİA karşılığı)

| MD | BİA | Not |
| --- | --- | --- |
| Otopark | Lokasyon (Bayi) | BİA'da her bayi bir lokasyondur; ayrıca Lokasyon Türü (Açık/Kapalı) ve Lokasyon Detay alanları var. |
| Tarife | Depolama Fiyatı (günlük) | Firma + lokasyon + araç tipine göre tanımlanır, girişte seçilmez, otomatik uygulanır. |
| Teslim Alma (Check-in) | Stoğa Giriş / Giriş Hareketi | BİA'da "Hızlı Araç Ekle" veya "Araç Ekle" formunda Hareket Tipi = Giriş. |
| Teslim Etme (Check-out) | Stoktan Çıkar / Çıkış Hareketi | Stoktaki Araçlar listesinden yapılır. |
| Otoparkta (durum) | Stokta | Stoktaki Araçlar listesinde görünür. |
| Teslim Edildi (durum) | Stoktan Çıkan | Stoktan Çıkanlar listesinde görünür. |
| Beklemede / Müsait (durum) | — (karşılığı yok) | BİA'da "Araç Durumu" Sıfır / İkinci El demektir, park durumu değildir. |
| VIN | Şasi Numarası (Şase) | BİA'da aracın benzersiz anahtarı şasidir. |
| Park yeri | Park Kodu / Lokasyon Detay |  |
| Teslim Formu / Tutanak (handover_form) | Tesellüm Formu | BİA'da araç bazlı yazdırılır; ayrı tutanak listesi yok. |
| Giriş-Çıkış Kayıtları | Araç Hareketleri (Stoktaki Araçlar + Stoktan Çıkanlar) | BİA iki ayrı liste kullanır. |
| Müşteriler / Otopark Bayileri | Firma (Müşteri) ve Bayi (Lokasyon) | BİA'da ikisi farklı kavram: firma aracın sahibi, bayi aracın durduğu yer. |
| Yapılan İşlemler | Ek Hizmet / Ek Maliyet (Maliyet Tipi) | BİA'da her işlem tutarıyla araç ekstresine yazılır. |
| Gün sayısı | Depolama Süresi |  |
| Toplam tutar | Depolama Tutarı + Ek Hizmet Tutarı | BİA'da iki ayrı raporda çıkar. |
| Doluluk raporu | Anlık Stok (dashboard) | BİA'da kapasite olmadığı için doluluk oranı yok. |
| Müşteri Araçlarım | — (bayi hesabıyla görülmedi) | BİA'da "Müşteri" kullanıcı grubu tanımlı ama menüsü taramada görünmedi. |

## İş akışı

### BİA akışı (sistem şu an böyle çalışıyor)

1. **Araç gelir, stoğa alınır:** "Hızlı Araç Ekle" (veya Araç Ekle / Toplu Stok Girişi) formunda şasi, plaka, araç tipi, firma, lokasyon, teslim eden/alan, envanter girilir. Araç kaydı ve Giriş hareketi aynı anda oluşur.
2. **Etiket ve tesellüm:** QR etiket basılır, istenirse tesellüm formu yazdırılır.
3. **Stokta bekler:** "Stoktaki Araçlar" listesinde görünür, Depolama Süresi her gün artar.
4. **Ek hizmetler yazılır:** Yıkama, hazırlık, taşıma gibi işler "Hızlı Maliyet Ekle", "Toplu Ek Maliyet Girişi" veya tamamlanan İş Emri ile araç ekstresine yazılır.
5. **Stoktan çıkar:** "Stoktan Çıkar" ile hareket nedeni, sevkiyat tipi/durumu ve teslim alan girilir; araç "Stoktan Çıkanlar"a düşer.
6. **Dönem sonu faturalama:** "Depolama Raporu" (tarih aralığında gün × günlük fiyat) ve "Ek Hizmetler Raporu" ile firmaya faturalanacak tutar çıkar.

### MD akışı

1. **Otopark ve tarife tanımlanır:** Otoparklar (kapasite) ve Tarifeler (günlük fiyat) önceden girilir.
2. **Araç kaydedilir:** Araçlar > Yeni Araç: plaka, VIN, marka, model, yıl, otopark. Kapasite doluysa sistem uyarır. Durum: Beklemede.
3. **(Müşteri) otoparka gönderir:** Müşteri kullanıcısı "Müşteri Araçlarım"dan "Otoparka Gönder" der.
4. **Teslim Alma formu:** Araç + otopark + tarife seçilir; tarih, km, teslim alan, kaporta/iç trim/lastik kontrol kartları, fotoğraf, imza. Park kaydı ve teslim alma tutanağı oluşur. Durum: Otoparkta.
5. **Teslime hazır:** Durum "Müsait" yapılır (listeden Gönder/Çıkar butonları).
6. **Teslim Etme formu:** Giriş kaydı seçilir, ön hesaplama kartı gün ve toplam tutarı gösterir; km, alıcı, yapılan işlemler, imza. Park kaydı kapanır, teslim etme tutanağı oluşur, detay sayfasına gider. Durum: Teslim Edildi.
7. **Tutanak ve rapor:** Teslim Formları listesinden PDF indirilir; Otopark Durum Raporu incelenir.

## Farklar ve kararlar

### Temel kavramlar

#### K01 · Ana kavram: Stok mu, Otopark mı?

- **Fark türü:** Sözcük farkı
- **BİA:** Her şey "stok" üzerinden anlatılır: araç stoğa girer, stokta kalır, stoktan çıkar. Menü ve başlıklar: Stoktaki Araçlar, Stoktan Çıkanlar, Anlık Stok.
- **MD:** Her şey "otopark" ve "park kaydı" üzerinden anlatılır: araç otoparka teslim alınır, otoparkta durur, teslim edilir.
- **Sistemde şu an:** BİA sözcükleri kullanılıyor (Stoktaki Araçlar / Stoktan Çıkanlar).
- **Karar:** Karar bekliyor

#### K02 · Otopark ↔ Lokasyon

- **Fark türü:** Sözcük farkı
- **BİA:** Aracın durduğu yer "Lokasyon"dur ve bir bayiye karşılık gelir (ör. BİA ANKARA). Ek olarak Lokasyon Türü (Açık / Kapalı / Her İkisi De) ve serbest metin Lokasyon Detay var.
- **MD:** "Otoparklar" menüsünde otopark listesi, her otoparkın kapasitesi ve bilgileri düzenlenir.
- **Sistemde şu an:** Lokasyon = Bayi tanımı (Tanımlar > Bayiler). Ayrı bir "Otopark" tanımı ve kapasite alanı yok.
- **Karar:** Karar bekliyor

#### K03 · Müşteri ve bayi ayrımı

- **Fark türü:** Sözcük farkı
- **BİA:** Firma (Müşteri) aracın sahibidir (ör. DOĞUŞ OTO); Bayi ise aracın bulunduğu lokasyonu işleten taraftır. Bayi kullanıcıları yalnızca kendi lokasyonlarını görür.
- **MD:** "Müşteriler / Otopark Bayileri" tek menüde "iş ortakları" olarak anılır; ayrım net değil.
- **Sistemde şu an:** BİA gibi: Müşteriler ve Bayiler ayrı tanımlar, bayi kullanıcısı lokasyonla sınırlı.
- **Karar:** Karar bekliyor

#### K04 · Otopark kapasitesi

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Kapasite kavramı yok; bir lokasyona sınırsız araç girilebilir.
- **MD:** Araç eklerken kapasite doluysa "Bu otoparkta yer kalmamıştır" uyarısı çıkar; başka otopark seçilir veya kapasite artırılır.
- **Sistemde şu an:** Kapasite kontrolü yok (BİA gibi).
- **Karar:** Karar bekliyor

### Araç kaydı

#### A01 · Araç kaydı ile girişin ilişkisi

- **Fark türü:** Akış farkı
- **BİA:** Araç kaydı ve stoğa giriş aynı formda, tek adımda yapılır (Hızlı Araç Ekle: araç bilgileri + hareket bilgileri). Şasi daha önce kayıtlıysa mevcut araç güncellenip yeni giriş hareketi eklenir.
- **MD:** Önce "Araçlar > Yeni Araç" ile araç kaydedilir (durum Beklemede), sonra ayrı bir "Teslim Alma" formu ile otoparka alınır.
- **Sistemde şu an:** BİA gibi tek adım. "Araç Ekle" sayfasında hareket bölümü boş bırakılırsa araç stoğa alınmadan da kaydedilebiliyor.
- **Karar:** Karar bekliyor

#### A02 · VIN ↔ Şasi Numarası

- **Fark türü:** Sözcük farkı
- **BİA:** Alan adı "Şasi Numarası *"; aracın benzersiz anahtarıdır, listelerde "Şase" sütunu.
- **MD:** Formda "VIN" olarak geçer.
- **Sistemde şu an:** "Şasi Numarası" kullanılıyor.
- **Karar:** Karar bekliyor

#### A03 · Araç formundaki alanlar

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Zorunlu: şasi, plaka, araç durumu (Sıfır/İkinci El), araç tipi, kasa tipi, renk, marka, seri. Ayrıca model, model yılı, motor hacmi/gücü, yakıt, vites, KM, yakıt durumu, park kodu, önceki plaka, donanım listesi (güvenlik/iç/dış), araç envanteri, fotoğraflar ve kayıt belgeleri.
- **MD:** Plaka, VIN, marka, model, yıl ve otopark.
- **Sistemde şu an:** BİA'daki tüm alanlar var.
- **Karar:** Karar bekliyor

#### A04 · Araç durumları

- **Fark türü:** Akış farkı
- **BİA:** Park durumu yalnızca "Stokta" / "Stoktan çıktı" (son hareket tipi Giriş / Çıkış). "Araç Durumu" alanı Sıfır / İkinci El anlamına gelir, park durumu değildir.
- **MD:** Dört durum: Beklemede (henüz park edilmedi), Otoparkta, Müsait (teslime hazır), Teslim Edildi. Listeden "Gönder" / "Çıkar" butonlarıyla değiştirilir.
- **Sistemde şu an:** BİA gibi: stokta / stoktan çıktı. Beklemede ve Müsait durumları yok.
- **Karar:** Karar bekliyor

#### A05 · Park yeri

- **Fark türü:** Sözcük farkı
- **BİA:** "Park Kodu" (araç kaydında) ve "Lokasyon Detay" (hareket kaydında, ör. Blok A-12).
- **MD:** Araç güncellemede "park yeri" değiştirilir.
- **Sistemde şu an:** Park Kodu ve Lokasyon Detay alanları var; toplu lokasyon güncelleme de var.
- **Karar:** Karar bekliyor

### Giriş (Teslim Alma)

#### G01 · Giriş formunun adı ve yeri

- **Fark türü:** Sözcük farkı
- **BİA:** Üst bardaki "Hızlı Araç Ekle" butonu veya Araç Yönetimi > Araç Ekle; toplu için Toplu Stok Girişi (Excel).
- **MD:** Soldaki "Teslim Alma" menüsü, açılan form "Araç Girişi".
- **Sistemde şu an:** BİA gibi: Hızlı Araç Ekle (her sayfada), Araç Ekle, Toplu Stok Girişi.
- **Karar:** Karar bekliyor

#### G02 · Giriş bilgileri

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Hareket Tipi (Giriş/Çıkış), Hareket Nedeni (Sevk / Müşteri / Vale / Servis teslimatı, Servis iadesi), Müşteri, Lokasyon Türü, Lokasyon, Lokasyon Detay, Teslim Eden (ad, telefon, e-posta), Teslim Alan (ad, telefon, e-posta), Teslim Alan Personel, Hareket Tarihi + saat, açıklama.
- **MD:** Araç, otopark, giriş tarihi, kilometre, teslim alan bilgileri.
- **Sistemde şu an:** BİA'daki tüm alanlar var.
- **Karar:** Karar bekliyor

#### G03 · Kontrol kartları (kaporta, iç trim, lastik...)

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Girişte yalnızca envanter onay kutuları (yedek anahtar, stepne, ruhsat, paspas...) ve donanım listesi var. Hasar/kontrol listesi yok; "Ekspertiz Yönetimi" ayrı bir modül (bayi hesabında içi boş).
- **MD:** Formda dikey kartlar halinde çok sayıda kontrol alanı (kaporta, iç trim, lastik vb.) tablet üzerinden sırayla doldurulur.
- **Sistemde şu an:** Envanter ve donanım var, kontrol kartları yok.
- **Karar:** Karar bekliyor

#### G04 · Fotoğraf ve imza

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Araç kaydına fotoğraf ve kayıt belgesi yüklenebilir; imza alanı yok.
- **MD:** Fotoğraf istenen yerlere dosya yüklenir; en altta imza görseli eklenir.
- **Sistemde şu an:** Fotoğraf ve belge yükleme var (araç düzenleme), imza yok.
- **Karar:** Karar bekliyor

#### G05 · Tarife seçimi

- **Fark türü:** Akış farkı
- **BİA:** Girişte fiyat seçilmez; firma + lokasyon + araç tipine göre tanımlı günlük depolama fiyatı raporda otomatik uygulanır.
- **MD:** Check-in sırasında tarife seçilir.
- **Sistemde şu an:** BİA gibi otomatik.
- **Karar:** Karar bekliyor

#### G06 · Aynı araç ikinci kez girilemez

- **Fark türü:** Aynı / uyumlu
- **BİA:** Stokta olan araç tekrar stoğa alınamaz.
- **MD:** "Bu araç için aktif bir otopark giriş kaydı bulunuyor" mesajı; önce teslim etme yapılmalı.
- **Sistemde şu an:** Aynı kural var; mesaj: "... şasi numaralı araç zaten stokta." Başka lokasyonun stoğundaki araç için "Bu araç başka bir lokasyonun stoğunda."
- **Karar:** Karar bekliyor

### Çıkış (Teslim Etme)

#### C01 · Çıkışın başlatıldığı yer

- **Fark türü:** Akış farkı
- **BİA:** "Stoktaki Araçlar" listesinde aracın satırından "Stoktan Çıkar".
- **MD:** Ayrı "Teslim Etme" menüsü; açılan sayfada "Araç Giriş Kaydı" listesinden kayıt seçilir.
- **Sistemde şu an:** BİA gibi: Stoktaki Araçlar > Stoktan Çıkar.
- **Karar:** Karar bekliyor

#### C02 · Ön hesaplama kartı

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Çıkış formunda ücret gösterilmez; ücret dönem sonunda Depolama Raporu'nda çıkar.
- **MD:** Araç seçilince kartta giriş/çıkış tarihi, gün sayısı ve toplam tutar otomatik hesaplanır; çıkış tarihi değişince yeniden hesaplanır.
- **Sistemde şu an:** Çıkış formunda kart yok. Aracın düzenleme sayfasındaki "Depolama" sekmesinde gün ve tutar görülebiliyor.
- **Karar:** Karar bekliyor

#### C03 · Çıkış bilgileri

- **Fark türü:** Sözcük farkı
- **BİA:** Hareket Nedeni, Sevkiyat Tipi (Bireysel / Vale / Çekici), Sevkiyat Durumu (Bekliyor / Devam Ediyor / Tamamlandı), Teslim Alan bilgileri, Hareket Tarihi.
- **MD:** Kilometre, alıcı bilgileri, "Yapılan İşlemler", notlar, imza; buton adı "Çıkış Yap ve Tutanak Kaydet".
- **Sistemde şu an:** BİA alanları var; "Yapılan İşlemler" yerine ek hizmetler maliyet olarak ayrıca girilir. İmza ve çıkış KM'si yok.
- **Karar:** Karar bekliyor

#### C04 · Çıkış sonrası

- **Fark türü:** Akış farkı
- **BİA:** Araç "Stoktan Çıkanlar" listesine düşer.
- **MD:** Park kaydı kapanır, teslim etme tutanağı oluşur ve sistem "Giriş-Çıkış Detay" sayfasına yönlendirir.
- **Sistemde şu an:** BİA gibi. Hareket detay sayfası var ama otomatik yönlendirme ve tutanak yok.
- **Karar:** Karar bekliyor

#### C05 · Tarih kontrolü

- **Fark türü:** Aynı / uyumlu
- **BİA:** Taramada görülemedi (form gönderilmedi).
- **MD:** Belirtilmemiş.
- **Sistemde şu an:** Çıkış tarihi stoğa giriş tarihinden önce olamaz; stokta olmayan araç çıkarılamaz.
- **Karar:** Karar bekliyor

### Ücret ve faturalama

#### U01 · Tarife ↔ Depolama Fiyatı

- **Fark türü:** Sözcük farkı
- **BİA:** "Depolama Fiyatı": firma + lokasyon + araç tipi başına günlük tutar (yönetici tanımlar).
- **MD:** "Tarifeler" menüsü, "Yeni Tarife Ekle", günlük fiyat.
- **Sistemde şu an:** Tanımlar > Depolama Fiyatları (yönetici).
- **Karar:** Karar bekliyor

#### U02 · Ücretin hesaplandığı an

- **Fark türü:** Akış farkı
- **BİA:** Dönem bazlı: Depolama Raporu seçilen tarih aralığında her araç için başlangıç, bitiş, günlük fiyat, süre ve tutarı listeler (aylık faturalama).
- **MD:** Araç bazlı: çıkışta gün × tarife = toplam tutar.
- **Sistemde şu an:** BİA gibi Depolama Raporu; araç sayfasında da gün ve tutar görülebiliyor.
- **Karar:** Karar bekliyor

#### U03 · Ek hizmet ücretleri

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Yıkama, hazırlık, teslim alma/etme, taşıma gibi her hizmet tutarıyla aracın ekstresine yazılır; Ek Hizmetler Raporu ile faturalanır.
- **MD:** Yalnızca çıkışta serbest metin "Yapılan İşlemler"; ücrete dahil değil.
- **Sistemde şu an:** BİA gibi: araç ekstresi, Hızlı Maliyet Ekle, Toplu Ek Maliyet, Ek Hizmetler Raporu.
- **Karar:** Karar bekliyor

### Tutanak / form / PDF

#### F01 · Teslim tutanakları

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Araç bazlı "Tesellüm Formu" yazdırılır; ayrıca kayıtlı bir tutanak listesi yok.
- **MD:** Her giriş ve çıkışta bir tutanak (handover_form) kaydı oluşur; "Teslim Formları" menüsünde listelenir, "Detay" ile açılır.
- **Sistemde şu an:** Tesellüm formu yazdırma var (araç sayfası). Tutanak kaydı ve listesi yok.
- **Karar:** Karar bekliyor

#### F02 · PDF indirme

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** QR etiketleri ve tesellüm formu PDF/yazdırma olarak alınır.
- **MD:** Tutanak detayında "PDF İndir" (Türkçe karakter destekli).
- **Sistemde şu an:** Tesellüm formu ve QR etiketleri yazdırma sayfası olarak açılır; tarayıcıdan PDF kaydedilebilir. Sunucuda üretilen PDF yok.
- **Karar:** Karar bekliyor

#### F03 · QR etiket

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Seçili araçlar için toplu QR etiket PDF'i.
- **MD:** Yok.
- **Sistemde şu an:** Toplu QR etiket yazdırma var.
- **Karar:** Karar bekliyor

### Liste ve raporlar

#### R01 · Giriş-çıkış listeleri

- **Fark türü:** Akış farkı
- **BİA:** İki ayrı liste: "Stoktaki Araçlar" (şase, plaka, firma, marka, seri, lokasyon, giriş tarihi, depolama süresi) ve "Stoktan Çıkanlar" (+ sevkiyat tipi/durumu).
- **MD:** Tek liste: "Giriş-Çıkış Kayıtları"; detayda araç, tarife, yapılan işlemler, tutanaklar ve PDF linkleri.
- **Sistemde şu an:** BİA gibi iki liste + hareket detay sayfası.
- **Karar:** Karar bekliyor

#### R02 · Otopark Durum Raporu

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** Aynı adla bir rapor yok. Benzer bilgi Stoktaki Araçlar listesinde ve dashboardlarda (firma dağılımı, stok süre dağılımı).
- **MD:** Hangi müşteri/bayi hangi aracı getirdi, kim teslim aldı, araç yeni mi kullanılmış mı.
- **Sistemde şu an:** Ayrı rapor yok; listeler ve Excel çıktıları bu bilgileri içeriyor.
- **Karar:** Karar bekliyor

#### R03 · Dashboardlar

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Ana Dashboard (anlık stok, giriş/çıkış, maliyet, firma ve hizmet dağılımı, duyurular), Araç Yönetimi (Stok) Dashboard, İK Dashboard.
- **MD:** Ana ekranda yalnızca sol menü anlatılıyor.
- **Sistemde şu an:** Üç dashboard da var.
- **Karar:** Karar bekliyor

### Kullanıcılar ve yetki

#### Y01 · Müşteri kullanıcısı ve "Müşteri Araçlarım"

- **Fark türü:** MD'de var, BİA'da yok
- **BİA:** "Müşteri" kullanıcı grubu tanımlı ama bayi hesabıyla ilgili menü görülmedi.
- **MD:** Müşteri kullanıcıları yalnızca kendi araçlarını görür ve "Otoparka Gönder" ile aracı otoparka gönderir.
- **Sistemde şu an:** Yalnızca Yönetici ve Bayi rolleri var; müşteri girişi yok.
- **Karar:** Karar bekliyor

#### Y02 · Şifre unutma

- **Fark türü:** Aynı / uyumlu
- **BİA:** Giriş ekranında şifre sıfırlama görülmedi.
- **MD:** Yöneticiden yeni şifre istenir.
- **Sistemde şu an:** Yönetici, Tanımlar > Kullanıcılar'dan şifre değiştirir; kullanıcı Hesabım'dan kendi şifresini değiştirir.
- **Karar:** Karar bekliyor

### Sadece BİA'da olanlar

#### B01 · İş emri yönetimi

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** İş Emri Oluştur, Bekleyen / Tamamlanan İş Emri Talepleri; tamamlanan iş emri araçlara maliyet yazar.
- **MD:** Yok.
- **Sistemde şu an:** Var.
- **Karar:** Karar bekliyor

#### B02 · Saha operasyon iş takibi

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Girilen ek hizmetlerin (maliyetlerin) takip listesi.
- **MD:** Yok.
- **Sistemde şu an:** Var.
- **Karar:** Karar bekliyor

#### B03 · Toplu işlemler (Excel)

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Toplu stok girişi, toplu ek maliyet girişi, toplu lokasyon güncelleme; Excel çıktıları.
- **MD:** Yok.
- **Sistemde şu an:** Var (CSV şablonu veya elle satır).
- **Karar:** Karar bekliyor

#### B04 · Araç tanımları

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Marka, seri, model, renk, kasa/araç tipi, yakıt, vites, envanter, donanım, hareket nedeni tanımları.
- **MD:** Bahsedilmiyor.
- **Sistemde şu an:** Var.
- **Karar:** Karar bekliyor

#### B05 · İK ve destek talepleri

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** İK Dashboard, Destek Talepleri.
- **MD:** Yok.
- **Sistemde şu an:** Var.
- **Karar:** Karar bekliyor

#### B06 · İlan yönetimi (2. el satış)

- **Fark türü:** BİA'da var, MD'de yok
- **BİA:** Stoktaki araçlar için "İlan Ekle", ilan performansı ve dashboard kartları görüldü.
- **MD:** Yok.
- **Sistemde şu an:** Yok (otopark kapsamı dışında bırakıldı).
- **Karar:** Karar bekliyor
