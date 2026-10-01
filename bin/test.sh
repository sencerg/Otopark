#!/usr/bin/env bash
# Uçtan uca test: giriş, yetki, bayi izolasyonu, yazma akışları, hesaplamalar ve güvenlik.
# Veritabanına test kayıtları yazar. --reset verilirse önce ve sonra demo veri yeniden kurulur (sadece APP_ENV=local).
# Kullanım: bin/test.sh [--reset]      Gereksinim: çalışan sunucu (php -S localhost:8000 -t public), curl, jq, psql
set -u
cd "$(dirname "$0")/.."
BASE=${BASE:-http://localhost:8000}
RESET=0; [[ "${1:-}" == "--reset" ]] && RESET=1

env_get() { sed -n "s/^$1=\"\{0,1\}\([^\"]*\)\"\{0,1\}$/\1/p" .env | tail -1; }
export PGPASSWORD=$(env_get DB_PASSWORD)
q() { psql -h "$(env_get DB_HOST)" -p "$(env_get DB_PORT)" -U "$(env_get DB_USERNAME)" -d "$(env_get DB_DATABASE)" -Atq -c "$1"; }

GECEN=0; KALAN=0; HATALAR=()
bolum() { echo; echo "── $1"; }
ok() { GECEN=$((GECEN + 1)); echo "  ✓ $1"; }
no() { KALAN=$((KALAN + 1)); HATALAR+=("$1"); echo "  ✗ $1"; [[ -n "${2:-}" ]] && echo "      → ${2:0:300}"; }
eq() { [[ "$2" == "$3" ]] && ok "$1" || no "$1" "beklenen: $2 | gelen: $3"; }
has() { grep -qF -- "$2" <<<"$3" && ok "$1" || no "$1" "'$2' yok: ${3:0:200}"; }
hasnt() { grep -qF -- "$2" <<<"$3" && no "$1" "'$2' olmamalıydı" || ok "$1"; }

# req JAR METHOD URL [curl args] → CODE, BODY, LOC. AJAX=0 ile X-Requested-With gönderilmez.
req() {
    local jar=$1 m=$2 url=$3; shift 3
    local h=(-H 'X-Requested-With: XMLHttpRequest'); [[ "${AJAX:-1}" == 0 ]] && h=()
    local out; out=$(curl -sg -b "$jar" -c "$jar" -X "$m" ${h[@]+"${h[@]}"} -w $'\n%{http_code} %{redirect_url}' "$@" "$BASE$url")
    local last=${out##*$'\n'}; BODY=${out%$'\n'*}; CODE=${last%% *}; LOC=${last#* }
}
page() { AJAX=0 req "$@"; }
js() { jq -r "$1" <<<"$BODY" 2>/dev/null; }
csrf_login() { curl -s -b "$1" -c "$1" "$BASE/login" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1; }
csrf() { curl -s -b "$1" "$BASE/kullanici/hesabim" | sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p' | head -1; }
login() { local t; t=$(csrf_login "$1"); page "$1" POST /login --data-urlencode "_csrf=$t" --data-urlencode "email=$2" --data-urlencode "password=$3"; }
dt() { echo "draw=1&start=0&length=${2:-10}&$1"; }

if [[ $RESET == 1 ]]; then php bin/reset.php >/dev/null || exit 1; fi
curl -s -o /dev/null "$BASE/login" || { echo "Sunucu çalışmıyor: $BASE"; exit 1; }
: > storage/logs/php-error.log

A=$(mktemp); K=$(mktemp); S=$(mktemp); G=$(mktemp)
trap 'rm -f "$A" "$K" "$S" "$G" /tmp/otopark_test_*' EXIT

ANK=$(q "SELECT id FROM bayiler WHERE ad='BİA ANKARA'")
SEY=$(q "SELECT id FROM bayiler WHERE ad='BİA SEYRANTEPE'")
MUS=$(q "SELECT id FROM musteriler WHERE ad='DOĞUŞ OTO (FİLO 0 ARAÇLAR)'")
TIP=$(q "SELECT id FROM arac_tipleri ORDER BY id LIMIT 1")
MARKA=$(q "SELECT id FROM markalar WHERE ad='Renault'")
SERI=$(q "SELECT id FROM seriler WHERE marka_id=$MARKA ORDER BY id LIMIT 1")
RUN=$(date +%H%M%S)

# ───────────────────────────────────────────────────────────────
bolum "1. Giriş ve oturum"
page "$G" GET /login
eq "Giriş sayfası açılıyor" 200 "$CODE"
has "Hızlı giriş bölümü görünüyor" "Hızlı giriş" "$BODY"
for e in admin@otopark.local ankara@otopark.local seyrantepe@otopark.local; do has "Hızlı giriş: $e" "data-demo-email=\"$e\"" "$BODY"; done

page "$G" POST /login --data-urlencode "email=admin@otopark.local" --data-urlencode "password=admin123"
eq "CSRF anahtarı olmadan giriş reddediliyor (419)" 419 "$CODE"
login "$G" admin@otopark.local yanlis
eq "Yanlış şifre giriş sayfasına döndürüyor" "$BASE/login" "$LOC"
page "$G" GET /login
has "Yanlış şifre mesajı gösteriliyor" "Kullanıcı adı veya şifre hatalı" "$BODY"
login "$G" "' OR 1=1 --" "x"
eq "SQL enjeksiyonlu kullanıcı adı giriş yapamıyor" "$BASE/login" "$LOC"

page "$G" GET /arac_yonetimi
eq "Oturumsuz sayfa isteği girişe yönleniyor" "$BASE/login" "$LOC"
req "$G" GET "/arac_yonetimi/liste?$(dt x=1)"
eq "Oturumsuz AJAX isteği 401 dönüyor" 401 "$CODE"

page "$G" GET /login
ONCE=$(awk '/PHPSESSID/{print $7}' "$G")
login "$G" admin@otopark.local admin123
SONRA=$(awk '/PHPSESSID/{print $7}' "$G")
eq "Doğru şifre ana sayfaya yönlendiriyor" "$BASE/" "$LOC"
[[ -n "$ONCE" && "$ONCE" != "$SONRA" ]] && ok "Girişte oturum kimliği yenileniyor (session fixation)" || no "Girişte oturum kimliği yenilenmiyor"
page "$G" GET /login
eq "Girişliyken /login ana sayfaya yönleniyor" "$BASE/" "$LOC"
T=$(csrf "$G")
page "$G" POST /logout --data-urlencode "_csrf=$T"
page "$G" GET /
eq "Çıkıştan sonra oturum kapanıyor" "$BASE/login" "$LOC"

login "$A" admin@otopark.local admin123; eq "Yönetici girişi" "$BASE/" "$LOC"
login "$K" ankara@otopark.local ankara123; eq "Ankara bayi girişi" "$BASE/" "$LOC"
login "$S" seyrantepe@otopark.local seyrantepe123; eq "Seyrantepe bayi girişi" "$BASE/" "$LOC"
TA=$(csrf "$A"); TK=$(csrf "$K"); TS=$(csrf "$S")
page "$K" GET /
has "Bayi üst barda kendi lokasyonunu görüyor" "BİA ANKARA" "$BODY"

# ───────────────────────────────────────────────────────────────
bolum "2. BİA ↔ MD karşılaştırma sayfası"
MADDE_SAYI=$(php -r 'define("BASE_PATH", getcwd()); echo count((require "app/karsilastirma.php")["maddeler"]);')
KOD_TEKIL=$(php -r 'define("BASE_PATH", getcwd()); $k=array_column((require "app/karsilastirma.php")["maddeler"], "kod"); echo count($k)===count(array_unique($k)) ? "evet" : "hayir";')
eq "Madde kodları benzersiz" evet "$KOD_TEKIL"
page "$A" GET /bia_md_karsilastirma
eq "Yönetici sayfayı açıyor" 200 "$CODE"
eq "Tüm maddeler listeleniyor" "$MADDE_SAYI" "$(grep -o 'class="card mb-3 madde"' <<<"$BODY" | wc -l | tr -d ' ')"
eq "Yönetici her madde için karar formu görüyor" "$MADDE_SAYI" "$(grep -o 'class="row g-2 align-items-start karar-form"' <<<"$BODY" | wc -l | tr -d ' ')"
has "Sözlük sekmesi var" "MD'deki terim" "$BODY"
has "İş akışı sekmesi var" "BİA akışı" "$BODY"
page "$K" GET /bia_md_karsilastirma
eq "Bayi sayfayı açıyor" 200 "$CODE"
hasnt "Bayi karar formu görmüyor" 'class="row g-2 align-items-start karar-form"' "$BODY"
page "$A" GET /
has "Menüde karşılaştırma linki var" "/bia_md_karsilastirma" "$BODY"

XSS='<script>alert(1)</script>'
req "$A" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TA" -d kod=K04 -d karar=md --data-urlencode "notlar=Kapasite eklenecek $XSS"
eq "Yönetici karar kaydediyor" true "$(js .success)"
eq "Karar veritabanında" "md" "$(q "SELECT karar FROM karsilastirma_kararlari WHERE kod='K04'")"
req "$A" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TA" -d kod=K04 -d karar=birlestir -d notlar=ikinci
eq "Aynı madde tekrar kaydedilince tek kayıt kalıyor" "1|birlestir" "$(q "SELECT count(*)||'|'||max(karar) FROM karsilastirma_kararlari WHERE kod='K04'")"
req "$A" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TA" -d kod=K04 -d karar=md --data-urlencode "notlar=Kapasite eklenecek $XSS"
page "$A" GET /bia_md_karsilastirma
hasnt "Not alanındaki script çalıştırılabilir halde basılmıyor (XSS)" "$XSS" "$BODY"
has "Not kaçışlanarak gösteriliyor" "&lt;script&gt;alert(1)&lt;/script&gt;" "$BODY"
req "$K" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TK" -d kod=K01 -d karar=md
eq "Bayi karar kaydedemiyor" false "$(js .success)"
eq "Bayinin denemesi veritabanına yazılmadı" 0 "$(q "SELECT count(*) FROM karsilastirma_kararlari WHERE kod='K01'")"
req "$A" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TA" -d kod=ZZ99 -d karar=md
eq "Olmayan madde kodu reddediliyor" false "$(js .success)"
req "$A" POST /bia_md_karsilastirma/kaydet --data-urlencode "_csrf=$TA" -d kod=K02 -d "karar=hack"
eq "Geçersiz karar değeri reddediliyor" false "$(js .success)"
req "$A" POST /bia_md_karsilastirma/kaydet -d kod=K02 -d karar=md
eq "CSRF anahtarı olmadan karar kaydı reddediliyor" 419 "$CODE"
curl -s -D /tmp/otopark_test_h -b "$A" "$BASE/bia_md_karsilastirma/indir" -o /tmp/otopark_test_md
has "Markdown indirme dosya olarak geliyor" "bia-md-karsilastirma.md" "$(cat /tmp/otopark_test_h)"
has "İndirilen dosyada karar yazıyor" "MD'ye göre değişecek" "$(cat /tmp/otopark_test_md)"
eq "İndirilen dosyada tüm maddeler var" "$MADDE_SAYI" "$(grep -c '^#### ' /tmp/otopark_test_md)"
q "DELETE FROM karsilastirma_kararlari" >/dev/null

# ───────────────────────────────────────────────────────────────
bolum "3. Bayi izolasyonu (Ankara / Seyrantepe / Yönetici)"
toplam() { req "$1" GET "$2"; js "${3:-.recordsTotal}"; }
for liste in "/arac_hareketleri/giris/liste" "/arac_hareketleri/cikis/liste" "/arac_yonetimi/liste" "/is_takibi/liste" "/gorev_yonetimi/liste"; do
    a=$(toplam "$A" "$liste?$(dt x=1)"); k=$(toplam "$K" "$liste?$(dt x=1)"); s=$(toplam "$S" "$liste?$(dt x=1)")
    if [[ "$a" =~ ^[0-9]+$ && "$k" =~ ^[1-9][0-9]*$ && "$s" =~ ^[1-9][0-9]*$ ]]; then ok "$liste: ankara=$k seyrantepe=$s yönetici=$a"; else no "$liste sayıları okunamadı" "a=$a k=$k s=$s"; continue; fi
    [[ "$liste" == "/gorev_yonetimi/liste" ]] && continue
    eq "$liste: yönetici = ankara + seyrantepe" "$a" "$((k + s))"
done
eq "Stoktaki araç sayısı veritabanıyla aynı (Ankara)" "$(q "SELECT count(*) FROM araclar WHERE stokta AND NOT arsiv AND bayi_id=$ANK")" "$(toplam "$K" "/arac_hareketleri/giris/liste?$(dt x=1)")"

req "$K" GET "/arac_hareketleri/giris/liste?export=1"
hasnt "Ankara Excel çıktısında İstanbul (34) plakası yok" ";34 " "$(tr ',' ';' <<<"$BODY")"
has "Ankara Excel çıktısında kendi (06) plakaları var" "06 " "$BODY"
req "$S" GET "/arac_hareketleri/giris/liste?export=1"
hasnt "Seyrantepe Excel çıktısında Ankara (06) plakası yok" "06 " "$BODY"

for r in "/depolama_raporu/liste?$(dt 'baslangic=2026-01-01&bitis=2026-12-31')" "/ek_hizmet_raporu/liste?$(dt 'baslangic=2026-01-01&bitis=2026-12-31')"; do
    a=$(toplam "$A" "$r" .toplam); k=$(toplam "$K" "$r" .toplam); s=$(toplam "$S" "$r" .toplam)
    eq "${r%%\?*} tutarı: yönetici = ankara + seyrantepe" "$(php -r "echo round($a,2);")" "$(php -r "echo round($k + $s,2);")"
done

S_ARAC=$(q "SELECT id FROM araclar WHERE bayi_id=$SEY AND stokta ORDER BY id LIMIT 1")
S_SASE=$(q "SELECT sase FROM araclar WHERE id=$S_ARAC")
S_HAR=$(q "SELECT id FROM arac_hareketleri WHERE arac_id=$S_ARAC ORDER BY id LIMIT 1")
S_EKS=$(q "SELECT id FROM arac_ekstreleri WHERE arac_id=$S_ARAC ORDER BY id LIMIT 1")
S_IE=$(q "SELECT x.is_emri_id FROM is_emri_araclari x JOIN araclar a ON a.id=x.arac_id WHERE a.bayi_id=$SEY LIMIT 1")
for u in "/arac_yonetimi/duzenle/$S_ARAC" "/arac_yonetimi/tesellum_formu/$S_ARAC" "/arac_hareketleri/hareket_view/$S_HAR" \
         "/arac_hareketleri/hareket_duzenle/$S_HAR" "/arac_hareketleri/stoktan_cikar/$S_ARAC" "/gorev_yonetimi/duzenle/$S_IE"; do
    page "$K" GET "$u"; eq "Ankara başka bayinin sayfasını açamıyor: ${u%/*}" 404 "$CODE"
done
page "$K" GET "/arac_yonetimi/qr_toplu?ids[]=$S_ARAC"
hasnt "Ankara QR etiketinde başka bayinin şasisi yok" "$S_SASE" "$BODY"
req "$K" GET "/api/araclar?q=$S_SASE"; eq "Ankara araç aramasında başka bayinin aracı çıkmıyor" 0 "$(js '.results | length')"
page "$K" GET "/arama?q=$S_SASE"; hasnt "Ankara genel aramada başka bayinin aracına gidemiyor" "duzenle/$S_ARAC" "$LOC"
req "$K" GET "/arac_ekstreleri/maliyet_getir/$S_EKS"; eq "Ankara başka bayinin maliyetini okuyamıyor" false "$(js .success)"

TUTAR_ONCE=$(q "SELECT tutar FROM arac_ekstreleri WHERE id=$S_EKS")
req "$K" POST "/arac_ekstreleri/maliyet_guncelle/$S_EKS" -F "_csrf=$TK" -F maliyet_tipi_id=1 -F tutar=1
req "$K" POST "/arac_ekstreleri/maliyet_sil/$S_EKS" -F "_csrf=$TK"
eq "Ankara başka bayinin maliyetini değiştiremiyor/silemiyor" "$TUTAR_ONCE" "$(q "SELECT tutar FROM arac_ekstreleri WHERE id=$S_EKS")"
req "$K" POST /arac_ekstreleri/ek_maliyet_save_modal -F "_csrf=$TK" -F arac_id=$S_ARAC -F maliyet_tipi_modal=1 -F tutar_ek=100
eq "Ankara başka bayinin aracına maliyet ekleyemiyor" false "$(js .success)"
page "$K" POST "/arac_hareketleri/stoktan_cikar/$S_ARAC" -F "_csrf=$TK" -F hareket_nedeni=2
eq "Ankara başka bayinin aracını stoktan çıkaramıyor" t "$(q "SELECT stokta FROM araclar WHERE id=$S_ARAC")"
req "$K" POST /arac_yonetimi/multiple_arsiv -F "_csrf=$TK" -F "ids[]=$S_ARAC"
req "$K" POST /arac_hareketleri/multiple_arsiv -F "_csrf=$TK" -F "ids[]=$S_HAR"
eq "Ankara başka bayinin kaydını arşivleyemiyor" "false|false" "$(q "SELECT a.arsiv||'|'||h.arsiv FROM araclar a, arac_hareketleri h WHERE a.id=$S_ARAC AND h.id=$S_HAR")"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=$S_SASE -F arac_tipi=$TIP -F hareket_tipi=1 -F musteri_id=$MUS
has "Ankara başka bayinin stoğundaki aracı giriş yapamıyor" "başka bir lokasyonun" "$(js .message)"
S_CIKAN=$(q "SELECT id FROM araclar WHERE bayi_id=$SEY AND NOT stokta ORDER BY id LIMIT 1")
S_CIKAN_SASE=$(q "SELECT sase FROM araclar WHERE id=$S_CIKAN")
page "$K" POST /arac_yonetimi/save -F "_csrf=$TK" -F sase=$S_CIKAN_SASE -F arac_tipi=$TIP -F marka_id=$MARKA -F seri_id=$SERI -F musteri_id=$MUS -F plaka="06 CAL 01"
eq "Ankara, başka bayideki (stok dışı) aracı hareketsiz kendine alamıyor" "$SEY" "$(q "SELECT bayi_id FROM araclar WHERE id=$S_CIKAN")"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=$S_CIKAN_SASE -F arac_tipi=$TIP -F hareket_tipi=2 -F musteri_id=$MUS
eq "Stokta olmayan araca çıkış hareketi girilemiyor" "false|$SEY" "$(js .success)|$(q "SELECT bayi_id FROM araclar WHERE id=$S_CIKAN")"
req "$K" GET "/arac_yonetimi/tanimlar/personeller/data?$(dt x=1 100)"
eq "Ankara personel listesinde yalnızca kendi personeli var" "$(q "SELECT count(*) FROM personeller WHERE bayi_id=$ANK")" "$(js .recordsTotal)"
S_PER=$(q "SELECT id FROM personeller WHERE bayi_id=$SEY ORDER BY id LIMIT 1")
req "$K" GET "/arac_yonetimi/tanimlar/personeller/getir/$S_PER"; eq "Ankara başka bayinin personelini okuyamıyor" false "$(js .success)"
req "$K" POST "/arac_yonetimi/tanimlar/personeller/sil/$S_PER" -F "_csrf=$TK"
eq "Ankara başka bayinin personelini silemiyor" 1 "$(q "SELECT count(*) FROM personeller WHERE id=$S_PER")"
req "$K" POST /arac_yonetimi/tanimlar/personeller/kaydet -F "_csrf=$TK" -F id=$S_PER -F ad_soyad="Ele Geçirildi"
hasnt "Ankara başka bayinin personelini güncelleyemiyor" "Ele Geçirildi" "$(q "SELECT ad_soyad FROM personeller WHERE id=$S_PER")"
req "$K" GET "/arac_yonetimi/tanimlar/depolama_fiyatlari/data?$(dt x=1 500)"
eq "Ankara yalnızca kendi lokasyonunun depolama fiyatlarını görüyor" "$(q "SELECT count(*) FROM depolama_fiyatlari WHERE bayi_id=$ANK")" "$(js .recordsTotal)"
page "$K" GET "/"
page "$K" GET "/?bayi_id=$SEY"; B1=$(sed -e 's/csrf-token" content="[^"]*"//' -e 's/name="_csrf" value="[^"]*"//' <<<"$BODY" | md5)
page "$K" GET "/";               B2=$(sed -e 's/csrf-token" content="[^"]*"//' -e 's/name="_csrf" value="[^"]*"//' <<<"$BODY" | md5)
eq "Ankara dashboard'da bayi_id parametresiyle başka bayiyi göremiyor" "$B2" "$B1"
req "$S" GET "/destek_talepleri/getir/1"; eq "Seyrantepe yöneticinin destek talebini göremiyor" false "$(js .success)"

# ───────────────────────────────────────────────────────────────
bolum "4. Yetki (yönetici işlemleri)"
page "$K" GET /arac_yonetimi/tanimlar/kullanicilar; eq "Bayi kullanıcı tanımlarını açamıyor" 403 "$CODE"
req "$K" POST /arac_yonetimi/tanimlar/musteriler/kaydet -F "_csrf=$TK" -F ad="Yetkisiz Firma $RUN"
eq "Bayi müşteri (firma) tanımı ekleyemiyor" 0 "$(q "SELECT count(*) FROM musteriler WHERE ad='Yetkisiz Firma $RUN'")"
req "$K" POST /arac_yonetimi/tanimlar/depolama_fiyatlari/kaydet -F "_csrf=$TK" -F musteri_id=$MUS -F bayi_id=$ANK -F gunluk_fiyat=1
eq "Bayi depolama fiyatı ekleyemiyor" 0 "$(q "SELECT count(*) FROM depolama_fiyatlari WHERE gunluk_fiyat=1")"
K_ARAC=$(q "SELECT id FROM araclar WHERE bayi_id=$ANK AND NOT stokta ORDER BY id LIMIT 1")
req "$K" POST /arac_yonetimi/multiple_delete -F "_csrf=$TK" -F "ids[]=$K_ARAC"
eq "Bayi araç silemiyor (sadece yönetici)" 1 "$(q "SELECT count(*) FROM araclar WHERE id=$K_ARAC")"
req "$K" POST /arac_yonetimi/tanimlar/renkler/kaydet -F "_csrf=$TK" -F ad="Test Rengi $RUN"
eq "Bayi araç tanımı (renk) ekleyebiliyor" true "$(js .success)"
req "$K" POST /arac_yonetimi/tanimlar/personeller/kaydet -F "_csrf=$TK" -F ad_soyad="Test Personel $RUN" -F bayi_id=$SEY
eq "Bayinin eklediği personel kendi lokasyonuna yazılıyor" "$ANK" "$(q "SELECT bayi_id FROM personeller WHERE ad_soyad='Test Personel $RUN'")"
req "$A" POST /arac_yonetimi/tanimlar/arac_markalari/sil/$MARKA -F "_csrf=$TA"
eq "Kullanılan marka silinemiyor (anlaşılır mesaj)" false "$(js .success)"
hasnt "Silme hatasında ham SQL mesajı yok" "SQLSTATE" "$BODY"

req "$A" POST /arac_yonetimi/tanimlar/kullanicilar/kaydet -F "_csrf=$TA" -F name="Lokasyonsuz $RUN" -F email="lokasyonsuz$RUN@otopark.local" -F password=Test1234 -F role=bayi
if [[ "$(js .success)" == true ]]; then
    login "$G" "lokasyonsuz$RUN@otopark.local" Test1234
    req "$G" GET "/arac_yonetimi/liste?$(dt x=1)"
    [[ "$(js .recordsTotal)" == "0" || "$CODE" != 200 ]] && ok "Lokasyonu olmayan bayi kullanıcısı hiçbir aracı görmüyor" || no "Lokasyonu olmayan bayi kullanıcısı TÜM araçları görüyor" "recordsTotal=$(js .recordsTotal)"
else
    ok "Lokasyonu seçilmeden bayi kullanıcısı oluşturulamıyor"
fi
req "$A" POST /arac_yonetimi/tanimlar/kullanicilar/kaydet -F "_csrf=$TA" -F name="Yeni Bayi $RUN" -F email="yeni$RUN@otopark.local" -F password=Test1234 -F role=bayi -F bayi_id=$SEY -F is_active=1
eq "Yönetici yeni bayi kullanıcısı oluşturuyor" true "$(js .success)"
login "$G" "yeni$RUN@otopark.local" Test1234; eq "Yeni kullanıcı giriş yapabiliyor" "$BASE/" "$LOC"
eq "Şifre düz metin saklanmıyor" 0 "$(q "SELECT count(*) FROM users WHERE password_hash='Test1234'")"

# ───────────────────────────────────────────────────────────────
bolum "5. Araç giriş / maliyet / çıkış akışı"
SASE="TST${RUN}AB12345"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=$SASE -F plaka="06 tst $RUN" -F arac_tipi=$TIP -F marka_id=$MARKA -F seri_id=$SERI \
    -F hareket_tipi=1 -F musteri_id=$MUS -F bayi_id=$SEY -F lokasyon_turu=1 -F lokasyon_detay='"><script>x()</script>' \
    -F hareket_tarihi_tarih=2026-09-01 -F hareket_tarihi_saat=10:00 -F "arac_envanterleri[]=1"
eq "Hızlı araç ekle çalışıyor" true "$(js .success)"
AID=$(q "SELECT id FROM araclar WHERE sase='$SASE'")
eq "Bayinin eklediği araç kendi lokasyonuna yazılıyor (bayi_id zorlanamıyor)" "$ANK" "$(q "SELECT bayi_id FROM araclar WHERE id=$AID")"
eq "Araç stokta ve 1 giriş hareketi var" "true|1" "$(q "SELECT stokta||'|'||(SELECT count(*) FROM arac_hareketleri WHERE arac_id=$AID AND hareket_tipi=1) FROM araclar WHERE id=$AID")"
eq "Plaka büyük harfe çevriliyor" "06 TST $RUN" "$(q "SELECT plaka FROM araclar WHERE id=$AID")"
req "$K" GET "/arac_hareketleri/giris/liste?$(dt "q=$SASE")"; eq "Araç Stoktaki Araçlar listesinde bulunuyor" 1 "$(js .recordsFiltered)"
HID=$(q "SELECT id FROM arac_hareketleri WHERE arac_id=$AID ORDER BY id LIMIT 1")
page "$K" GET "/arac_hareketleri/hareket_view/$HID"
hasnt "Hareket detayında lokasyon detay alanı kaçışlanıyor (XSS)" "<script>x()</script>" "$BODY"

req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=$SASE -F arac_tipi=$TIP -F hareket_tipi=1 -F musteri_id=$MUS
has "Stoktaki araç ikinci kez giriş yapamıyor" "zaten stokta" "$(js .message)"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=AB -F arac_tipi=$TIP -F hareket_tipi=1 -F musteri_id=$MUS
eq "Kısa şasi reddediliyor" false "$(js .success)"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase="TSTNOMUS$RUN" -F arac_tipi=$TIP -F hareket_tipi=1
eq "Müşterisiz giriş reddediliyor" false "$(js .success)"
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase="TSTNOTIP$RUN" -F hareket_tipi=1 -F musteri_id=$MUS
eq "Araç tipsiz giriş reddediliyor" false "$(js .success)"
YABANCI_SERI=$(q "SELECT id FROM seriler WHERE marka_id<>$MARKA ORDER BY id LIMIT 1")
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase="TSTSERI$RUN" -F arac_tipi=$TIP -F marka_id=$MARKA -F seri_id=$YABANCI_SERI -F hareket_tipi=1 -F musteri_id=$MUS
has "Markaya ait olmayan seri reddediliyor" "bu markaya ait değil" "$(js .message)"
YABANCI_MODEL=$(q "SELECT id FROM modeller WHERE seri_id<>$SERI ORDER BY id LIMIT 1")
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase="TSTMODL$RUN" -F arac_tipi=$TIP -F marka_id=$MARKA -F seri_id=$SERI -F model_id=$YABANCI_MODEL -F hareket_tipi=1 -F musteri_id=$MUS
has "Seriye ait olmayan model reddediliyor" "bu seriye ait değil" "$(js .message)"
eq "Uyumsuz seri/model ile araç oluşmuyor" 0 "$(q "SELECT count(*) FROM araclar WHERE sase IN ('TSTSERI$RUN','TSTMODL$RUN')")"

req "$K" POST /arac_ekstreleri/ek_maliyet_save_modal -F "_csrf=$TK" -F arac_id=$AID -F maliyet_tipi_modal=1 -F tutar_ek="1.250,50" -F islem_tarihi=2026-09-02
eq "Maliyet ekleniyor" true "$(js .success)"
EID=$(q "SELECT id FROM arac_ekstreleri WHERE arac_id=$AID ORDER BY id DESC LIMIT 1")
eq "Türkçe tutar (1.250,50) doğru okunuyor" "1250.50" "$(q "SELECT tutar FROM arac_ekstreleri WHERE id=$EID")"
req "$K" POST /arac_ekstreleri/ek_maliyet_save_modal -F "_csrf=$TK" -F arac_id=$AID -F maliyet_tipi_modal=1 -F tutar_ek="1.250" -F islem_tarihi=2026-09-02
eq "Bin ayraçlı tutar (1.250) 1250 olarak okunuyor" "1250.00" "$(q "SELECT tutar FROM arac_ekstreleri WHERE arac_id=$AID ORDER BY id DESC LIMIT 1")"
SIL_ID=$(q "SELECT id FROM arac_ekstreleri WHERE arac_id=$AID ORDER BY id DESC LIMIT 1")
req "$K" POST "/arac_ekstreleri/maliyet_sil/$SIL_ID" -F "_csrf=$TK"
eq "Maliyet siliniyor" 0 "$(q "SELECT count(*) FROM arac_ekstreleri WHERE id=$SIL_ID")"
req "$K" POST /arac_ekstreleri/ek_maliyet_save_modal -F "_csrf=$TK" -F arac_id=$AID -F maliyet_tipi_modal=1 -F tutar_ek="abc"
eq "Geçersiz tutar reddediliyor" false "$(js .success)"
req "$K" POST /arac_ekstreleri/ek_maliyet_save_modal -F "_csrf=$TK" -F arac_id=$AID -F maliyet_tipi_modal=1 -F tutar_ek="-50"
eq "Negatif tutar reddediliyor" false "$(js .success)"
req "$K" GET "/arac_ekstreleri/maliyet_getir/$EID"; eq "Maliyet okunuyor" true "$(js .success)"
req "$K" POST "/arac_ekstreleri/maliyet_guncelle/$EID" -F "_csrf=$TK" -F maliyet_tipi_id=1 -F tutar=300 -F islem_tarihi=2026-09-03
eq "Maliyet güncelleniyor" "300.00|2026-09-03" "$(q "SELECT tutar||'|'||islem_tarihi FROM arac_ekstreleri WHERE id=$EID")"

page "$K" POST "/arac_hareketleri/stoktan_cikar/$AID" -F "_csrf=$TK" -F hareket_nedeni=2 -F hareket_tarihi=2026-08-20 -F hareket_saati=10:00
eq "Girişten önceki tarihle çıkış reddediliyor" t "$(q "SELECT stokta FROM araclar WHERE id=$AID")"
page "$K" POST "/arac_hareketleri/stoktan_cikar/$AID" -F "_csrf=$TK" -F hareket_nedeni=2 -F hareket_tarihi=2026-09-10 -F hareket_saati=18:00 -F sevkiyat_tipi=2 -F sevkiyat_durumu=3
eq "Stoktan çıkar çalışıyor" f "$(q "SELECT stokta FROM araclar WHERE id=$AID")"
req "$K" GET "/arac_hareketleri/cikis/liste?$(dt "q=$SASE")"; eq "Araç Stoktan Çıkanlar listesinde" 1 "$(js .recordsFiltered)"
page "$K" POST "/arac_hareketleri/stoktan_cikar/$AID" -F "_csrf=$TK" -F hareket_nedeni=2 -F hareket_tarihi=2026-09-11 -F hareket_saati=10:00
eq "Stokta olmayan araç ikinci kez çıkarılamıyor" 1 "$(q "SELECT count(*) FROM arac_hareketleri WHERE arac_id=$AID AND hareket_tipi=2")"

bolum "6. Depolama hesabı"
FIYAT=$(q "SELECT gunluk_fiyat FROM depolama_fiyatlari WHERE musteri_id=$MUS AND bayi_id=$ANK AND arac_tipi_id=$TIP")
dep() { req "$K" GET "/depolama_raporu/liste?$(dt "q=$SASE&baslangic=$1&bitis=$2")"; echo "$(js '.data[0].gun // .data[0].depolama_suresi')|$(js .toplam)"; }
eq "1-10 Eylül konaklaması: 10 gün × $FIYAT ₺" "10|$(php -r "echo (float)(10*$FIYAT);")" "$(dep 2026-09-01 2026-09-30)"
eq "Rapor aralığı 5-7 Eylül: 3 gün (aralığa kırpılıyor)" "3|$(php -r "echo (float)(3*$FIYAT);")" "$(dep 2026-09-05 2026-09-07)"
eq "Konaklama dışındaki aralıkta kayıt yok" "0" "$(req "$K" GET "/depolama_raporu/liste?$(dt "q=$SASE&baslangic=2026-09-15&bitis=2026-09-20")"; js .recordsFiltered)"

req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase=$SASE -F arac_tipi=$TIP -F hareket_tipi=1 -F musteri_id=$MUS \
    -F hareket_tarihi_tarih=2026-09-20 -F hareket_tarihi_saat=09:00
eq "Çıkan araç tekrar stoğa girebiliyor" "true|t" "$(js .success)|$(q "SELECT stokta FROM araclar WHERE id=$AID")"
eq "İki ayrı konaklama oluşuyor" 2 "$(q "SELECT count(*) FROM arac_konaklamalari WHERE arac_id=$AID")"
eq "İki konaklama tek raporda: 10 gün + 3 gün (20-22 Eylül)" "2|$(php -r "echo (float)(13*$FIYAT);")" "$(req "$K" GET "/depolama_raporu/liste?$(dt "q=$SASE&baslangic=2026-09-01&bitis=2026-09-22")"; echo "$(js .recordsFiltered)|$(js .toplam)")"

GENEL_MUS=$(q "SELECT musteri_id FROM depolama_fiyatlari WHERE bayi_id=$ANK AND arac_tipi_id IS NULL ORDER BY musteri_id LIMIT 1")
GENEL_FIYAT=$(q "SELECT gunluk_fiyat FROM depolama_fiyatlari WHERE bayi_id=$ANK AND musteri_id=$GENEL_MUS AND arac_tipi_id IS NULL")
req "$K" POST /arac_yonetimi/hizli_arac_save -F "_csrf=$TK" -F sase="TSTGNL$RUN" -F arac_tipi=$TIP -F hareket_tipi=1 -F musteri_id=$GENEL_MUS \
    -F hareket_tarihi_tarih=2026-09-01 -F hareket_tarihi_saat=09:00
req "$K" GET "/depolama_raporu/liste?$(dt "q=TSTGNL$RUN&baslangic=2026-09-01&bitis=2026-09-04")"
eq "Tipli fiyatı olmayan firmada genel (araç tipi boş) fiyat kullanılıyor" "4|$(php -r "echo (float)(4*$GENEL_FIYAT);")|false" "$(js '.data[0].gun')|$(js .toplam)|$(js '.data[0].fiyat_yok')"
q "DELETE FROM depolama_fiyatlari WHERE bayi_id=$ANK AND musteri_id=$GENEL_MUS" >/dev/null
req "$K" GET "/depolama_raporu/liste?$(dt "q=TSTGNL$RUN&baslangic=2026-09-01&bitis=2026-09-04")"
eq "Fiyatı tanımsız firmada uyarı bayrağı dönüyor" "true|0" "$(js '.data[0].fiyat_yok')|$(js .toplam)"

bolum "7. İş emri"
AR1=$(q "SELECT id FROM araclar WHERE bayi_id=$ANK AND stokta ORDER BY id DESC LIMIT 1")
AR2=$(q "SELECT id FROM araclar WHERE bayi_id=$ANK AND stokta ORDER BY id DESC LIMIT 1 OFFSET 1")
MT=$(q "SELECT id FROM maliyet_tipleri WHERE varsayilan_tutar > 0 ORDER BY id LIMIT 1")
MT_TUTAR=$(q "SELECT varsayilan_tutar FROM maliyet_tipleri WHERE id=$MT")
SON_IE=$(q "SELECT max(id) FROM is_emirleri")
page "$K" POST /gorev_yonetimi/save -F "_csrf=$TK" -F gorev_turu=$MT -F musteri_id=$MUS -F talep_tarihi=2026-09-30 \
    -F "arac_id[]=$AR1" -F "a_durum[]=1" -F "arac_id[]=$AR2" -F "a_durum[]=1" -F durum=4 -F detaylar="test"
IE=$(q "SELECT max(id) FROM is_emirleri")
[[ "$IE" -gt "$SON_IE" ]] && ok "Bayi iş emri oluşturuyor" || no "İş emri oluşmadı" "$LOC"
[[ "$(q "SELECT kod FROM is_emirleri WHERE id=$IE")" =~ ^IE-[0-9]{4}-[0-9]{5}$ ]] && ok "İş emri kodu IE-YYYY-NNNNN biçiminde" || no "İş emri kodu biçimi yanlış" "$(q "SELECT kod FROM is_emirleri WHERE id=$IE")"
eq "Kodlar benzersiz" 0 "$(q "SELECT count(*) FROM (SELECT kod FROM is_emirleri GROUP BY kod HAVING count(*)>1) x")"
eq "Tamamlanan iş emri 2 araca maliyet yazıyor" "2|$(php -r "printf('%.2f', 2*$MT_TUTAR);")" "$(q "SELECT count(*)||'|'||sum(tutar) FROM arac_ekstreleri WHERE is_emri_id=$IE")"
page "$K" POST "/gorev_yonetimi/update/$IE" -F "_csrf=$TK" -F gorev_turu=$MT -F musteri_id=$MUS -F "arac_id[]=$AR1" -F "a_durum[]=4" -F "arac_id[]=$AR2" -F "a_durum[]=4" -F durum=4
eq "Tekrar kaydedince maliyet mükerrer yazılmıyor" 2 "$(q "SELECT count(*) FROM arac_ekstreleri WHERE is_emri_id=$IE")"
page "$K" POST /gorev_yonetimi/save -F "_csrf=$TK" -F gorev_turu=$MT -F musteri_id=$MUS -F talep_tarihi=2026-09-30 -F "arac_id[]=$S_ARAC" -F "a_durum[]=1" -F durum=4
eq "Bayi iş emrine başka bayinin aracını ekleyemiyor" 0 "$(q "SELECT count(*) FROM arac_ekstreleri WHERE arac_id=$S_ARAC AND is_emri_id IS NOT NULL AND is_emri_id > $IE")"
page "$S" GET "/gorev_yonetimi/duzenle/$IE"; eq "Seyrantepe, Ankara'nın iş emrini açamıyor" 404 "$CODE"

bolum "8. Destek ve hesap"
req "$K" POST /destek_talepleri/save -F "_csrf=$TK" -F konu="Test $RUN" -F tur=2 -F aciklama="deneme"
eq "Destek talebi oluşuyor" true "$(js .success)"
DKOD=$(q "SELECT kod FROM destek_talepleri WHERE konu='Test $RUN'"); DID=$(q "SELECT id FROM destek_talepleri WHERE konu='Test $RUN'")
[[ "$DKOD" =~ ^DT-[0-9]{5}$ ]] && ok "Destek kodu DT-NNNNN biçiminde ($DKOD)" || no "Destek kodu biçimi yanlış" "$DKOD"
req "$S" GET "/destek_talepleri/getir/$DID"; eq "Seyrantepe, Ankara'nın destek talebini göremiyor" false "$(js .success)"
page "$K" POST /kullanici/hesabim -F "_csrf=$TK" -F ad=Ankara -F soyad=Bayi -F mail=ankara@otopark.local -F sifre=abc12345 -F sifre_tekrari=farkli
login "$G" ankara@otopark.local ankara123; eq "Şifre tekrarı uyuşmayınca şifre değişmiyor" "$BASE/" "$LOC"

bolum "9. Toplu işlemler"
printf 'sase;plaka;marka;seri;lokasyon_detay;sevkiyat_kodu;irsaliye_kodu;proje_adi\nCSV%s00000001;06 CSV 01;Renault;Clio;B-1;;;\nCSV%s00000002;06 CSV 02;Fiat;Egea;B-2;;;\n;;;;;;;\n%s;;;;;;;\n' "$RUN" "$RUN" "$S_SASE" > /tmp/otopark_test_toplu.csv
page "$K" POST /arac_yonetimi/excel_toplu_giris -F "_csrf=$TK" -F musteri_id=$MUS -F bayi_id=$ANK -F arac_tipi=$TIP -F "import_file=@/tmp/otopark_test_toplu.csv"
eq "CSV ile 2 geçerli araç stoğa giriyor" 2 "$(q "SELECT count(*) FROM araclar WHERE sase LIKE 'CSV$RUN%' AND stokta AND bayi_id=$ANK")"
eq "CSV'de başka bayinin stoğundaki araç atlanıyor" "$SEY" "$(q "SELECT bayi_id FROM araclar WHERE id=$S_ARAC")"
eq "CSV'de marka/seri adları eşleşiyor" "Clio" "$(q "SELECT s.ad FROM araclar a JOIN seriler s ON s.id=a.seri_id WHERE a.sase='CSV${RUN}00000001'")"

bolum "10. Sağlamlık ve güvenlik"
for p in "search=abc" "search[value][]=x" "order[0]=x" "order[0][column]=abc&order[0][dir]=asc" "order[0][column]=1&order[0][dir][]=x&columns[1][data]=sase" \
         "order[0][column]=1&columns[1][data]=sase%3BDROP%20TABLE%20araclar" "length=-1" "start=-50&length=99999" "q=%27%20OR%201%3D1%20--" "q=%00%27" \
         "baslangic=abc&bitis=2026-99-99" "stok[]=x" "bayi_id[]=1" "draw[]=1&start[]=1"; do
    req "$A" GET "/arac_yonetimi/liste?draw=1&$p"
    [[ "$CODE" == 200 ]] && jq -e . >/dev/null 2>&1 <<<"$BODY" && ok "Bozuk parametre güvenli: $p" || no "Bozuk parametre hata veriyor: $p" "$CODE ${BODY:0:150}"
done
req "$A" GET "/arac_yonetimi/liste?$(dt "q=%27%20OR%201%3D1%20--")"; eq "SQL enjeksiyonu araması hiçbir şey döndürmüyor" 0 "$(js .recordsFiltered)"
eq "Tablolar yerinde" 1 "$(q "SELECT count(*) FROM information_schema.tables WHERE table_name='araclar'")"
for p in "/depolama_raporu/liste?search=abc&baslangic=x" "/ek_hizmet_raporu/liste?maliyet_tipi[]=abc" "/is_takibi/liste?order[0][column]=x" \
         "/gorev_yonetimi/liste?durum=abc" "/destek_talepleri/liste?search[value][]=1" "/arac_yonetimi/tanimlar/renkler/data?order=x" "/api/seriler?marka_id[]=1" "/api/araclar?q[]=x"; do
    req "$A" GET "$p"; [[ "$CODE" =~ ^(200|404|422)$ ]] && ok "Bozuk parametre güvenli: ${p%%\?*}" || no "Bozuk parametre hata veriyor: $p" "$CODE ${BODY:0:150}"
done
page "$A" GET "/arac_yonetimi/sablon/..%2F..%2F.env"; hasnt "Şablon adresinden .env okunamıyor" "DB_PASSWORD" "$BODY"
page "$A" GET "/.env"; hasnt ".env dosyası web'den okunamıyor" "DB_PASSWORD" "$BODY"
page "$A" GET "/../bootstrap.php"; hasnt "Proje dosyaları web'den okunamıyor" "declare(strict_types" "$BODY"
printf '<?php echo "pwned";' > /tmp/otopark_test_shell.php
page "$A" POST "/arac_yonetimi/update/$AID" -F "_csrf=$TA" -F sase=$SASE -F arac_tipi=$TIP -F marka_id=$MARKA -F seri_id=$SERI -F musteri_id=$MUS -F "fotograf[]=@/tmp/otopark_test_shell.php"
eq "PHP dosyası yüklenemiyor" 0 "$(find public/uploads -name '*.php' | wc -l | tr -d ' ')"
page "$A" PUT /arac_yonetimi; [[ "$CODE" =~ ^(404|405)$ ]] && ok "Tanımsız HTTP metodu reddediliyor ($CODE)" || no "Tanımsız HTTP metodu" "$CODE"
page "$A" GET /olmayan_sayfa; eq "Olmayan sayfa 404" 404 "$CODE"
req "$A" POST /arac_yonetimi/hizli_arac_save -F "_csrf=yanlis" -F sase=X; eq "Yanlış CSRF anahtarı reddediliyor" 419 "$CODE"
req "$A" POST /arac_yonetimi/hizli_arac_save -F "_csrf[]=x" -F sase=X; eq "Dizi biçimli CSRF anahtarı reddediliyor" 419 "$CODE"
: > "$G"; T=$(csrf_login "$G"); page "$G" POST /login --data-urlencode "_csrf=$T" -d "email[]=x" -d "password[]=y"
eq "Dizi biçimli giriş alanları hata vermiyor" "$BASE/login" "$LOC"
req "$A" POST /gorev_yonetimi/toplu_durum -F "_csrf=$TA" -F "ids[]=1"; eq "Durumsuz toplu iş emri güncellemesi reddediliyor" false "$(js .success)"

bolum "11. Sayfa taraması (3 kullanıcı)"
for kim in "admin@otopark.local admin123" "ankara@otopark.local ankara123" "seyrantepe@otopark.local seyrantepe123"; do
    out=$(bin/smoke.sh $kim 2>&1); h=$(grep -c '^HATA' <<<"$out"); o=$(grep -c '^OK' <<<"$out")
    # Bayi için sabit id'li sayfalar (başka bayinin kaydı olabilir) ve yönetici sayfaları 403/404/422 dönebilir
    beklenen=0; [[ "$kim" != admin* ]] && beklenen=$(grep -cE '^HATA +(403|404|422) +/\S*(/[0-9]+|kullanicilar\S*)$' <<<"$out")
    eq "smoke ${kim%% *}: $o sayfa OK, beklenmeyen hata yok" "$beklenen" "$h"
    [[ "$h" != "$beklenen" ]] && grep '^HATA' <<<"$out" | sed 's/^/      /'
done
page "$A" GET /bia_md_karsilastirma; hasnt "Karşılaştırma sayfasında PHP hatası yok" "Warning:" "$BODY"

bolum "12. PHP hata günlüğü"
LOG=$(grep -vE 'SQLSTATE\[23' storage/logs/php-error.log)
[[ -z "$LOG" ]] && ok "Test boyunca PHP hatası/uyarısı oluşmadı" || no "PHP hata günlüğünde kayıt var" "$(head -5 <<<"$LOG")"

[[ $RESET == 1 ]] && php bin/reset.php >/dev/null
echo; echo "════ SONUÇ: $GECEN geçti, $KALAN kaldı"
for h in ${HATALAR[@]+"${HATALAR[@]}"}; do echo "   ✗ $h"; done
[[ $KALAN == 0 ]]
