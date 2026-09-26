<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!function_exists('toBase64')) {
    function toBase64($image) {
        if(empty($image) || !file_exists($image)) return "";
        $data = base64_encode(file_get_contents($image));
        $mime = @mime_content_type($image) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . $data;
    }
}

if (!function_exists('drawBox')) {
    function drawBox($checked) {
        return $checked ? '<span style="font-family: DejaVu Sans, sans-serif; font-size: 10px; color:#000;">&#9745;</span>' : '<span style="font-family: DejaVu Sans, sans-serif; font-size: 10px; color:#000;">&#9744;</span>';
    }
}


if (isset($_POST["report_number"]) || isset($_GET["preview"])) {
    $report = array("report_number" => $_POST["report_number"] ?? "TASLAK", "control_date" => $_POST["control_date"] ?? date("d.m.Y"), "next_control_date" => $_POST["next_control_date"] ?? "", "isemrino" => $_POST["isemrino"] ?? "", "customer_id" => $_POST["customer"] ?? 0, "photos" => $_POST["photos_json"] ?? "[]");
    $matters = array(
        "header_extra" => array("kontrol_adresi" => $_POST["kontrol_adresi"] ?? "", "isg_katip_id" => $_POST["isg_katip_id"] ?? "", "sgk_sicil" => $_POST["sgk_sicil"] ?? "", "metot_kapsam" => $_POST["metot_kapsam"] ?? "", "test_degerleri" => $_POST["test_degerleri"] ?? "", "kusur_aciklamalari" => $_POST["kusur_aciklamalari"] ?? "", "notlar" => $_POST["notlar"] ?? "", "sonuc_kanaat" => $_POST["sonuc_kanaat"] ?? ""),
        "tesis_detay" => array("algilama_tipi" => $_POST["algilama_tipi"] ?? "", "uyari_sistemi" => $_POST["uyari_sistemi"] ?? "", "calisma_tipi" => $_POST["calisma_tipi"] ?? "", "kontrol_nedeni" => $_POST["kontrol_nedeni"] ?? "", "proje_onay_kurum" => $_POST["proje_onay_kurum"] ?? "", "proje_onay_tarih" => $_POST["proje_onay_tarih"] ?? "", "panel_marka" => $_POST["panel_marka"] ?? "", "panel_seri_no" => $_POST["panel_seri_no"] ?? "", "panel_gerilim" => $_POST["panel_gerilim"] ?? "", "panel_yeri" => $_POST["panel_yeri"] ?? "", "ilk_kontrol_tarihi" => $_POST["ilk_kontrol_tarihi"] ?? "", "last_control_date" => $_POST["last_control_date"] ?? "", "algilama_ekipmanlari" => (array)($_POST["algilama_ekipmanlari"] ?? []), "uyari_ekipmanlari" => (array)($_POST["uyari_ekipmanlari"] ?? []), "sondurme_ekipmanlari" => (array)($_POST["sondurme_ekipmanlari"] ?? [])),
        "bina_tespitleri" => array("tesisat_degisiklik" => $_POST["tesisat_degisiklik"] ?? "", "etiket_varmi" => $_POST["etiket_varmi"] ?? "", "tehlike_sinifi" => $_POST["tehlike_sinifi"] ?? "", "tehlike_kategorisi" => $_POST["tehlike_kategorisi"] ?? "", "alan" => $_POST["bina_alan"] ?? "", "kat" => $_POST["bina_kat"] ?? "", "yukseklik" => $_POST["bina_yukseklik"] ?? "", "izin_tarihi" => $_POST["bina_izin_tarihi"] ?? "", "bolum_sayisi" => $_POST["bina_bolum_sayisi"] ?? "", "diger" => $_POST["bina_diger"] ?? "", "bina_sinifi" => (array)($_POST["bina_sinifi"] ?? [])),
        "olcum_cihazlari" => array(
            array("ad" => $_POST["cihaz1_ad"] ?? "", "seri" => $_POST["cihaz1_seri"] ?? "", "kal_no" => $_POST["cihaz1_kal_no"] ?? "", "kal_tar" => $_POST["cihaz1_kal_tar"] ?? "", "gec_tar" => $_POST["cihaz1_gec_tar"] ?? ""),
            array("ad" => $_POST["cihaz2_ad"] ?? "", "seri" => $_POST["cihaz2_seri"] ?? "", "kal_no" => $_POST["cihaz2_kal_no"] ?? "", "kal_tar" => $_POST["cihaz2_kal_tar"] ?? "", "gec_tar" => $_POST["cihaz2_gec_tar"] ?? "")
        ),
        "inspections" => array()
    );
    for ($i = 1; $i <= 50; $i++) { $matters["inspections"]["madde$i"] = (isset($_POST["madde$i"])) ? "UYGUN" : "UYGUN DEĞİL"; }
    $dedectors = isset($_POST["equipment_data_json"]) ? json_decode($_POST["equipment_data_json"], true) : array();
    $controller_peak = array("name" => $_POST["controller_peak"] ?? "", "diploma" => $_POST["controller_peak_diploma"] ?? "", "emo" => $_POST["controller_peak_emo"] ?? "", "ekipnet" => $_POST["controller_peak_ekipnet"] ?? "");
    $cust_id = $_POST["customer"] ?? 0;
} else {
    $id = $_GET["id"];
    $query = $ac->prepare("SELECT * from reports where id = ?"); $query->execute(array($id)); $report = $query->fetch(PDO::FETCH_ASSOC);
    $matters = is_array($report) && !empty($report["report_matters"]) ? json_decode($report["report_matters"], true) : [];
    if (!is_array($matters)) { $matters = []; }
    $dedectors = is_array($report) && !empty($report["dedektor_info"]) ? json_decode($report["dedektor_info"], true) : [];
    if (!is_array($dedectors)) { $dedectors = []; }
    $controller_peak = is_array($report) && !empty($report["controller_peak_info"]) ? json_decode($report["controller_peak_info"], true) : [];
    if (!is_array($controller_peak)) { $controller_peak = []; }
    $cust_id = $report["customer_id"] ?? 0;
}

$custquery = $ac->prepare("SELECT * FROM customers WHERE id = ?"); $custquery->execute(array($cust_id)); $customer = $custquery->fetch(PDO::FETCH_ASSOC);
$header_extra = is_array($matters) ? ($matters["header_extra"] ?? array()) : array();
$tesis_detay = is_array($matters) ? ($matters["tesis_detay"] ?? array()) : array();
$bina_tespitleri = is_array($matters) ? ($matters["bina_tespitleri"] ?? array()) : array();
$olcum_cihazlari = is_array($matters) ? ($matters["olcum_cihazlari"] ?? array()) : array();


$html = '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><style>
@page { margin: 100px 20px 40px 20px; font-size: 7px; }
header { position: fixed; top: -85px; left: 0; right: 0; height: 75px; border: 0.5pt solid #000; }
body { font-family: "Dejavu Sans", sans-serif; color: #333; line-height: 1.1; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 0.5pt solid #000; padding: 2px 4px; vertical-align: middle; }
.bg-grey { background-color: #f2f2f2; font-weight: bold; }
.bg-blue { background-color: #d9e1f2; }
.text-center { text-align: center; }
.title-main { font-size: 13px; font-weight: bold; text-align: center; }
.section-header { background-color: #e4d5d5; font-weight: bold; font-size: 8px; padding: 2.5px; border: 0.5pt solid #000; margin-top:2px; }
.page-break { page-break-after: always; }
.rich-content { min-height: 20px; border: 0.5pt solid #000; padding: 4px; background:#fff; font-size: 6.8px; }
</style></head><body>
<header><table><tr><td width="30%" style="border:none; text-align:center;"><img src="'.toBase64('src/images/logo.png').'" width="120"></td><td width="70%" style="border:none; border-left:0.5pt solid #000;" class="text-center"><div class="title-main">YANGIN ALGILAMA SİSTEMLERİ<br>KONTROL RAPORU</div></td></tr></table></header>

<div class="section-header">1.FİRMA BİLGİLERİ</div>
<table>
<tr><td width="20%" class="bg-grey">Firma Adı</td><td width="40%"><b>'.$customer["company"].'</b></td><td width="20%" class="bg-grey">Rapor Numarası</td><td width="20%"><b>'.$report["report_number"].'</b></td></tr>
<tr><td class="bg-grey">Periyodik Kontrol Adresi</td><td>'.($header_extra["kontrol_adresi"] ?: $customer["address"]).'</td><td class="bg-grey">Rapor Tarihi</td><td>'.$report["control_date"].'</td></tr>
<tr><td class="bg-grey">SGK Sicil Numarası</td><td>'.$header_extra["sgk_sicil"].'</td><td class="bg-grey">İSG-KATİP Sözleşme ID</td><td>'.$header_extra["isg_katip_id"].'</td></tr>
<tr><td class="bg-grey" rowspan="2">Periyodik Kontrol Metodu ve Kapsamı</td><td rowspan="2" style="font-size:6px;">'.nl2br($header_extra["metot_kapsam"] ?: "TSE CEN/TS 54-14: Yangın Algılama...\nİş Ekipmanları Yönetmeliği...").'</td><td class="bg-grey">Bir Sonraki Periyodik Kontrol Tarihi</td><td>'.$report["next_control_date"].'</td></tr>
<tr><td class="bg-grey">İş Emri No</td><td>'.$report["isemrino"].'</td></tr>
</table>

<div class="section-header">2.TESİS BİLGİLERİ</div>
<div style="background:#f2f2f2; font-weight:bold; border:0.5pt solid #000; padding:2px;">2.1. SİSTEM DETAY BİLGİLERİ</div>
<table>
<tr><td width="15%" class="bg-grey">Yangın algılama</td><td width="25%">'.drawBox($tesis_detay["algilama_tipi"]=="Otomatik").' Otomatik '.drawBox($tesis_detay["algilama_tipi"]=="Manuel").' Manuel</td><td width="15%" class="bg-grey">Yangın uyarı sistemi</td><td width="45%">'.drawBox(in_array("Işıklı", (array)$tesis_detay["uyari_sistemi"])).' Işıklı '.drawBox(in_array("Sesli", (array)$tesis_detay["uyari_sistemi"])).' Sesli '.drawBox(in_array("Anons", (array)$tesis_detay["uyari_sistemi"])).' Anons</td></tr>
<tr><td class="bg-grey">Sistem çalışma tipi</td><td>'.drawBox($tesis_detay["calisma_tipi"]=="Adresli").' Adresli '.drawBox($tesis_detay["calisma_tipi"]=="Konvansiyonel").' Konvansiyonel</td><td class="bg-grey">Proje onay kurumu</td><td>'.$tesis_detay["proje_onay_kurum"].'</td></tr>
<tr><td class="bg-grey">Kontrol nedeni</td><td>'.drawBox($tesis_detay["kontrol_nedeni"]=="Periyodik Kontrol").' Periyodik '.drawBox($tesis_detay["kontrol_nedeni"]=="İlk Kontrol").' İlk Kontrol</td><td class="bg-grey">Proje onay tarih ve sayısı</td><td>'.$tesis_detay["proje_onay_tarih"].'</td></tr>
<tr><td class="bg-grey">Kontrol paneli marka/model</td><td>'.$tesis_detay["panel_marka"].'</td><td class="bg-grey">İlk kontrol/devreye alma tarihi</td><td>'.$tesis_detay["ilk_kontrol_tarihi"].'</td></tr>
<tr><td class="bg-grey">Panel seri no/imal yılı</td><td>'.$tesis_detay["panel_seri_no"].'</td><td class="bg-grey">Panel çalışma gerilimi</td><td>'.$tesis_detay["panel_gerilim"].'</td></tr>
<tr><td class="bg-grey">Panel yeri</td><td>'.$tesis_detay["panel_yeri"].'</td><td class="bg-grey">Algılama ekipmanları</td><td>'.drawBox(in_array("Duman", (array)$tesis_detay["algilama_ekipmanlari"])).' Duman '.drawBox(in_array("Isı", (array)$tesis_detay["algilama_ekipmanlari"])).' Isı '.drawBox(in_array("Buton", (array)$tesis_detay["algilama_ekipmanlari"])).' Buton</td></tr>
<tr><td class="bg-grey">Söndürme ekipmanları</td><td colspan="3">'.drawBox(in_array("Otomatik", (array)$tesis_detay["sondurme_ekipmanlari"])).' Otomatik '.drawBox(in_array("KKT", (array)$tesis_detay["sondurme_ekipmanlari"])).' KKT '.drawBox(in_array("CO2", (array)$tesis_detay["sondurme_ekipmanlari"])).' CO2 '.drawBox(in_array("Hidrant", (array)$tesis_detay["sondurme_ekipmanlari"])).' Hidrant</td></tr>
</table>

<div style="background:#f2f2f2; font-weight:bold; border:0.5pt solid #000; padding:2px;">2.2. BİNA İLE İLGİLİ TESPİT EDİLEN BİLGİLER</div>
<table>
<tr><td width="20%" class="bg-grey">Tesisatta kapsamlı değişiklik</td><td width="30%">'.drawBox($bina_tespitleri["tesisat_degisiklik"]=="Belirlenemedi").' Belirlenemedi '.drawBox($bina_tespitleri["tesisat_degisiklik"]=="Var").' Var '.drawBox($bina_tespitleri["tesisat_degisiklik"]=="Yok").' Yok</td><td width="20%" class="bg-grey">Bir önceki periyodik kontrol etiketi var mı?</td><td width="30%">'.drawBox($bina_tespitleri["etiket_varmi"]=="Var").' Var '.drawBox($bina_tespitleri["etiket_varmi"]=="Yok").' Yok</td></tr>
<tr><td class="bg-grey" rowspan="2">Bina kullanma sınıfı</td><td colspan="2" style="font-size:5.5px;">'.drawBox(in_array("Konut", (array)$bina_tespitleri["bina_sinifi"])).' Konut '.drawBox(in_array("Toplanma amaçlı bina", (array)$bina_tespitleri["bina_sinifi"])).' Toplanma '.drawBox(in_array("Depolama amaçlı tesis", (array)$bina_tespitleri["bina_sinifi"])).' Depolama '.drawBox(in_array("Yüksek tehlikeli bina", (array)$bina_tespitleri["bina_sinifi"])).' Yüksek Tehlikeli<br>'.drawBox(in_array("Endüstriyel yapı", (array)$bina_tespitleri["bina_sinifi"])).' Endüstriyel '.drawBox(in_array("Konaklama amaçlı bina", (array)$bina_tespitleri["bina_sinifi"])).' Konaklama '.drawBox(in_array("Kurumsal bina", (array)$bina_tespitleri["bina_sinifi"])).' Kurumsal '.drawBox(in_array("Ticari", (array)$bina_tespitleri["bina_sinifi"])).' Ticari</td><td rowspan="2"><div class="bg-grey text-center">Bina tehlike sınıfı</div>'.drawBox($bina_tespitleri["tehlike_sinifi"]=="Düşük Tehlike").' Düşük '.drawBox($bina_tespitleri["tehlike_sinifi"]=="Orta Tehlike").' Orta '.drawBox($bina_tespitleri["tehlike_sinifi"]=="Yüksek Tehlike").' Yüksek<hr><div class="bg-grey text-center">Tehlike kategorisi</div>'.drawBox($bina_tespitleri["tehlike_kategorisi"]=="1").' 1 '.drawBox($bina_tespitleri["tehlike_kategorisi"]=="2").' 2 '.drawBox($bina_tespitleri["tehlike_kategorisi"]=="3").' 3 '.drawBox($bina_tespitleri["tehlike_kategorisi"]=="4").' 4</td></tr>
<tr><td colspan="2" style="font-size:5.5px;">'.drawBox(in_array("Büro binası", (array)$bina_tespitleri["bina_sinifi"])).' Büro binası '.drawBox(in_array("Karışık kullanım amaçlı bina", (array)$bina_tespitleri["bina_sinifi"])).' Karışık Kullanım</td></tr>
<tr><td class="bg-grey">Bina toplam kullanım alanı (m²)</td><td>'.$bina_tespitleri["alan"].'</td><td class="bg-grey">Kat sayısı</td><td>'.$bina_tespitleri["kat"].'</td></tr>
<tr><td class="bg-grey">Bina yüksekliği (m)</td><td>'.$bina_tespitleri["yukseklik"].'</td><td class="bg-grey">Yapı kullanım izin tarihi</td><td>'.$bina_tespitleri["izin_tarihi"].'</td></tr>
<tr><td class="bg-grey">Bölüm sayısı</td><td>'.$bina_tespitleri["bolum_sayisi"].'</td><td class="bg-grey">Varsa diğer tespitler</td><td>'.$bina_tespitleri["diger"].'</td></tr>
</table>

<div class="section-header">3. TEST DEĞERLERİ</div>
<div class="rich-content" style="height:30px;">'.nl2br($header_extra["test_degerleri"]).'</div>

<div class="section-header">4. ÖLÇÜM ALETLERİ BİLGİLERİ</div>
<table>
<tr><td width="20%" class="bg-blue">Cihaz adı</td><td width="30%">'.($olcum_cihazlari[0]["ad"] ?? "").'</td><td width="20%" class="bg-blue">Cihaz adı</td><td width="30%">'.($olcum_cihazlari[1]["ad"] ?? "").'</td></tr>
<tr><td class="bg-blue">Kalibrasyon tarihi</td><td>'.($olcum_cihazlari[0]["kal_tar"] ?? "").'</td><td class="bg-blue">Kalibrasyon tarihi</td><td>'.($olcum_cihazlari[1]["kal_tar"] ?? "").'</td></tr>
<tr><td class="bg-blue">Kalibrasyon geçerlilik tarihi</td><td>'.($olcum_cihazlari[0]["gec_tar"] ?? "").'</td><td class="bg-blue">Kalibrasyon geçerlilik tarihi</td><td>'.($olcum_cihazlari[1]["gec_tar"] ?? "").'</td></tr>
<tr><td class="bg-blue">Seri numarası</td><td>'.($olcum_cihazlari[0]["seri"] ?? "").'</td><td class="bg-blue">Seri numarası</td><td>'.($olcum_cihazlari[1]["seri"] ?? "").'</td></tr>
<tr><td class="bg-blue">Kalibrasyon numarası</td><td>'.($olcum_cihazlari[0]["kal_no"] ?? "").'</td><td class="bg-blue">Kalibrasyon numarası</td><td>'.($olcum_cihazlari[1]["kal_no"] ?? "").'</td></tr>
</table>

<div class="page-break"></div>
<div class="section-header">5. TESPİT VE DEĞERLENDİRMELER</div>
<div style="background:#f2f2f2; font-weight:bold; border:0.5pt solid #000; padding:2px;">5.1. GÖZLE MUAYENELER VE BELGE KONTROLLERİ</div>
<table>
<tr class="bg-grey text-center"><td width="40%">Muayene Kriteri</td><td width="10%">Sonuç</td><td width="40%">Muayene Kriteri</td><td width="10%">Sonuç</td></tr>';
$m_items = ["Yetkili personel var mı?","Acil durum anons sistemi","Bakım kayıtları tutuluyor mu?","Panel çalışma durumu","Dedektör uygunluğu","Buton yerleşimi","Kablo tesisatı","Akülerin durumu","Sesli uyarı yeterliliği","Işıklı uyarı yeterliliği","Duman damperleri izleme","Havalandırma sinyal kontrolü","Söndürme sistemleri entegrasyonu","Akış anahtarları izlenebilirliği","Bina otomasyonu bağlantısı","Basınçlandırma kontrolleri","Asansörlerin davranış kontrolü","Yangın kapıları tutucuları","Gaz dağıtım sistemleri kontrolü","Geçiş kontrol sistemleri","Kablo uygunluğu","Güvenlik devre ayrılması","Acil aydınlatma armatürleri","Panel önü aydınlatma (Lux)","Çıkış yönlendirme işaretleri","Aydınlatma süreleri","Otomatik devreye girme testi","Sistem kütüğü belgesi","Kullanma talimatı var mı?","Adresleme/Harita durumu","Paralel ihbar lambaları","Kısa/Açık devre koruması","Gaz kesme valfleri","Yedek enerji kaynağı","Sistem temizliği","Genel değerlendirme"];
$inspectionsArr = is_array($matters["inspections"] ?? null) ? $matters["inspections"] : [];
for($i=0; $i<count($m_items); $i+=2){
    $val1 = $inspectionsArr["madde".($i+1)] ?? "UYGUN";
    $val2 = $inspectionsArr["madde".($i+2)] ?? "UYGUN";
    $html .= '<tr><td>'.($i+1).'. '.$m_items[$i].'</td><td class="text-center"><b>'.$val1.'</b></td>';
    if(isset($m_items[$i+1])){ $html .= '<td>'.($i+2).'. '.$m_items[$i+1].'</td><td class="text-center"><b>'.$val2.'</b></td></tr>'; } else { $html .= '<td></td><td></td></tr>'; }
}
$html .= '</table>

<div style="background:#f2f2f2; font-weight:bold; border:0.5pt solid #000; padding:2px; margin-top:5px;">5.2. ÜRÜN LİSTESİ</div>
<table style="font-size:6.5px;"><tr class="bg-grey text-center"><td>Kod</td><td>Bölüm</td><td>Ekipman</td><td>Yer</td><td>Eriş.</td><td>Mont.</td><td>Test</td><td>Sesli</td><td>Işıklı</td><td>Adr.</td></tr>';
if (is_array($dedectors)) {
    foreach($dedectors as $d){
        if (!is_array($d)) continue;
        $html .= '<tr class="text-center"><td>'.($d["kod"] ?? '').'</td><td>'.($d["bolum"] ?? '').'</td><td>'.($d["ekipman"] ?? '').'</td><td>'.($d["yer"] ?? '').'</td><td>'.($d["erisim"] ?? '').'</td><td>'.($d["montaj"] ?? '').'</td><td>'.($d["test"] ?? '').'</td><td>'.($d["sesli"] ?? '').'</td><td>'.($d["isikli"] ?? '').'</td><td>'.($d["adresleme"] ?? '').'</td></tr>';
    }
}
$html .= '</table>

<div class="page-break"></div>
<div class="section-header">6. KUSUR AÇIKLAMALARI</div>
<div class="rich-content" style="height:60px;">'.nl2br($header_extra["kusur_aciklamalari"] ?? '').'</div>
<div style="font-size:6px; margin:2px;">Kusur derecesi; (*) hafif kusurlu ve (**) ağır kusurlu anlamında kullanılmaktadır.</div>

<div class="section-header">FOTOĞRAFLAR</div>
<div class="rich-content" style="min-height:80px; text-align:center;">';
$photos = json_decode($report["photos"] ?? "[]", true);
if(is_array($photos) && count($photos) > 0){
    foreach($photos as $p){ $html .= '<img src="'.toBase64($p).'" style="max-width:180px; max-height:120px; margin:5px; border:0.5pt solid #ccc;">'; }
} else { $html .= '<div style="color:#ccc; padding-top:30px;">Fotoğraf eklenmedi</div>'; }
$html .= '</div>

<div class="section-header">7. NOTLAR</div>
<div class="rich-content" style="height:50px;">'.nl2br($header_extra["notlar"] ?? '').'</div>
<div class="section-header">8. SONUÇ VE KANAAT</div>
<div class="rich-content" style="min-height:120px; font-size:7.5px;">'.nl2br($header_extra["sonuc_kanaat"] ?? '').'</div>
<div class="section-header">9. ONAY</div>
<table><tr><td class="bg-grey" width="30%">Adı Soyadı</td><td width="40%" class="text-center"><b>'.($controller_peak["name"] ?? '').'</b></td><td width="30%" class="text-center" rowspan="3" style="vertical-align:top;">İmza / Mühür</td></tr><tr><td class="bg-grey">Mesleği</td><td class="text-center">ELEKTRONİK VE HABERLEŞME MÜHENDİSİ</td></tr><tr><td class="bg-grey">Yetki Numarası</td><td class="text-center" style="font-size:6.5px;">Diploma No: '.($controller_peak["diploma"] ?? '').'<br>EMO Sicil No: '.($controller_peak["emo"] ?? '').'<br>Ekipnet No: '.($controller_peak["ekipnet"] ?? '').'</td></tr></table>
</body></html>';


$options = new Options();
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if (empty($_GET['send-mail']) || $_GET['send-mail'] !== 'true') {
    if (ob_get_length()) {
        ob_end_clean();
    }
    $dompdf->stream($report["report_number"] . ".pdf", array("Attachment" => false));
} else {
    $pdf_content = $dompdf->output();
    send_pdf_email_attachment(
        $pdf_content,
        $report["report_number"] . '.pdf',
        'index.php?p=report-send-as-mail&id=' . urlencode((string)($report['id'] ?? $id)) . '&type=yas&st=success-mail',
        'index.php?p=report-send-as-mail&id=' . urlencode((string)($report['id'] ?? $id)) . '&type=yas&st=unsuccessful',
        'Yangın Algılama Sistemi Kontrol Raporu - ' . ($report['report_number'] ?? ''),
        'Yangın algılama sistemi kontrol raporunuz ekte sunulmuştur.'
    );
}
?>

