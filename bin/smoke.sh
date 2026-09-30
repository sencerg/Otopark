#!/usr/bin/env bash
# Tüm sayfaları ve JSON uçlarını çağırıp HTTP durumunu ve PHP hatalarını kontrol eder.
# Kullanım: bin/smoke.sh [email] [şifre]
set -u
BASE=${BASE:-http://localhost:8000}
EMAIL=${1:-admin@otopark.local}
PASS=${2:-admin123}
JAR=$(mktemp)
trap 'rm -f "$JAR"' EXIT

token() { curl -s -b "$JAR" -c "$JAR" "$BASE/login" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1; }
T=$(token)
curl -s -b "$JAR" -c "$JAR" -o /dev/null -X POST "$BASE/login" --data-urlencode "_csrf=$T" --data-urlencode "email=$EMAIL" --data-urlencode "password=$PASS"

fail=0
check() {
    local url=$1 expect=${2:-200}
    local out code
    out=$(curl -sg -b "$JAR" -H 'X-Requested-With: XMLHttpRequest' -w '\n%{http_code}' "$BASE$url")
    code=${out##*$'\n'}
    body=${out%$'\n'*}
    if [[ "$code" != "$expect" ]] || grep -qiE 'Fatal error|Warning:|Notice:|Deprecated:|Uncaught|SQLSTATE' <<<"$body"; then
        echo "HATA  $code  $url"
        grep -oiE '(Fatal error|Warning|Notice|Deprecated|Uncaught|SQLSTATE)[^<]{0,200}' <<<"$body" | head -3
        fail=1
    else
        echo "OK    $code  $url"
    fi
}

DT='draw=1&start=0&length=10&order[0][column]=1&order[0][dir]=desc&columns[1][data]=sase'
for u in / "/?baslangic=2026-01-01&bitis=2026-09-30" /saha_dashboard /ik_dashboard \
    /arac_hareketleri/giris /arac_hareketleri/cikis "/arac_hareketleri/giris/liste?$DT" "/arac_hareketleri/cikis/liste?$DT" \
    /arac_yonetimi "/arac_yonetimi/liste?$DT" "/arac_yonetimi/liste?$DT&stok=stokta&q=a" /arac_yonetimi/ekle \
    /arac_yonetimi/duzenle/1 /arac_yonetimi/tesellum_formu/1 "/arac_yonetimi/qr_toplu?ids[]=1&ids[]=2" \
    /arac_yonetimi/toplu_stok_girisi /arac_yonetimi/toplu_ek_maliyet_girisi /arac_yonetimi/toplu_kayit_guncelleme_lokasyon \
    /arac_yonetimi/sablon/stok /arac_hareketleri/hareket_view/1 /arac_hareketleri/hareket_duzenle/1 /arac_hareketleri/stoktan_cikar/1 \
    /is_takibi "/is_takibi/liste?draw=1&start=0&length=10&baslangic=2026-01-01" \
    /depolama_raporu "/depolama_raporu/liste?draw=1&start=0&length=10&baslangic=2026-01-01&bitis=2026-09-30" \
    /ek_hizmet_raporu "/ek_hizmet_raporu/liste?draw=1&start=0&length=10" "/ek_hizmet_raporu/ozet?baslangic=2026-01-01" \
    /gorev_yonetimi "/gorev_yonetimi?durum=1" "/gorev_yonetimi/liste?draw=1&start=0&length=10&durum=1" /gorev_yonetimi/ekle /gorev_yonetimi/duzenle/1 \
    /destek_talepleri "/destek_talepleri/liste?draw=1&start=0&length=10" /destek_talepleri/getir/1 \
    /arac_yonetimi/tanimlar /arac_yonetimi/tanimlar/arac_serileri "/arac_yonetimi/tanimlar/arac_serileri/data?draw=1&start=0&length=10" \
    /arac_yonetimi/tanimlar/depolama_fiyatlari "/arac_yonetimi/tanimlar/depolama_fiyatlari/data?draw=1&start=0&length=10" \
    /arac_yonetimi/tanimlar/kullanicilar "/arac_yonetimi/tanimlar/kullanicilar/data?draw=1&start=0&length=10" /arac_yonetimi/tanimlar/maliyet_tipleri/getir/1 \
    /kullanici/hesabim "/api/seriler?marka_id=6" "/api/araclar?q=VF" "/api/personeller?departman[]=9"; do
    check "$u"
done
check /olmayan_sayfa 404
check /arac_yonetimi/duzenle/999999 404

exit $fail
