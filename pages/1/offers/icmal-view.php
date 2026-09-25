<?php
/**
 * Resmi Fiyat Teklif Formu (İcmal Raporu)
 * Standart Teklif ile birebir aynı DejaVu Sans yazı tipi, logo başlığı, müşteri meta blokları ve tablo yapısındadır.
 */
ini_set('display_errors', 'Off');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . '/vendor/autoload.php';

use App\Model\OfferModel;
use Dompdf\Dompdf;
use Dompdf\Options;

function toBase64($image)
{
    if (file_exists($image)) {
        $data = base64_encode(file_get_contents($image));
        return 'data:' . mime_content_type($image) . ';base64,' . $data;
    }
    return '';
}

$cid = (int)($_POST['cid'] ?? $_GET['cid'] ?? 0);
$selectedOfferIds = [];

$offersParam = $_POST['offers'] ?? $_GET['offers'] ?? null;
if (!empty($offersParam)) {
    if (is_array($offersParam)) {
        $selectedOfferIds = array_map('intval', $offersParam);
    } else {
        $selectedOfferIds = array_filter(array_map('intval', explode(',', (string)$offersParam)));
    }
}

if (!$cid && empty($selectedOfferIds)) {
    die('Geçersiz parametre!');
}

// Müşteri bilgilerini çek
if ($cid > 0) {
    $custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
    $custquery->execute([$cid]);
    $customer = $custquery->fetch(PDO::FETCH_ASSOC);
} else {
    $customer = [];
}

// Teklifleri çek
$offers = [];
if (!empty($selectedOfferIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedOfferIds), '?'));
    $stmt = $ac->prepare("SELECT * FROM offers WHERE id IN ($placeholders) ORDER BY offer_date ASC, id ASC");
    $stmt->execute($selectedOfferIds);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($customer) && !empty($offers)) {
        $cid = (int)$offers[0]['cid'];
        $custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
        $custquery->execute([$cid]);
        $customer = $custquery->fetch(PDO::FETCH_ASSOC);
    }
} else if ($cid > 0) {
    $stmt = $ac->prepare("SELECT * FROM offers WHERE cid = ? ORDER BY offer_date ASC, id ASC");
    $stmt->execute([$cid]);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($offers)) {
    die('Yazdırılacak teklif bulunamadı!');
}

// Oluşturan Bilgileri
$user_id = $_SESSION['lid'] ?? ($offers[0]['creativer'] ?? 1);
$crtquery = $ac->prepare('SELECT * FROM users WHERE id = ?');
$crtquery->execute([$user_id]);
$creator = $crtquery->fetch(PDO::FETCH_ASSOC);

$pagehead = 'FİYAT TEKLİF FORMU';
$dateToday = date('d.m.Y');
$firstOfferNumber = $offers[0]['offerNumber'] ?? ('TK' . date('Ymd'));
$offerSubject = count($offers) > 1 ? ($customer['company'] . ' - İCMAL DOSYASI') : ($offers[0]['offer_subject'] ?? 'Fiyat Teklifi');

// Özel veya şablon üst ve alt bilgi metinleri
$custom_header_content = $_POST['header_content'] ?? $_GET['header_content'] ?? null;
$custom_footer_content = $_POST['footer_content'] ?? $_GET['footer_content'] ?? null;

if ($custom_header_content !== null && trim($custom_header_content) !== '') {
    $offer_header_content = $custom_header_content;
} else {
    $offer_header_content = 'Sayın talep etmiş olduğunuz ürün/hizmetlere ilişkin fiyat teklifimiz aşağıda bilgilerinize sunulmuştur. Fiyatlarımızın makul gelmesini umut eder, iyi çalışmalar dileriz.';
}

if ($custom_footer_content !== null && trim($custom_footer_content) !== '') {
    $offer_footer_content = $custom_footer_content;
} else {
    $offer_footer_content = $offers[0]['offer_footer_content'] ?? '<p><strong>1.</strong> Fiyatlarımıza KDV dahildir.</p><p><strong>2.</strong> Ödeme Vadesi: Belirlenen ödeme planına göredir.</p><p><strong>3.</strong> Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.</p>';
}

// Kalem Satırları HTML'i Oluştur
$items_html = '';
$sira = 1;
$total_tl_ara_toplam = 0;
$total_kdv_tutari = 0;
$total_kdv_dahil = 0;

foreach ($offers as $of) {
    $offerAmount = (float)($of['tl_toplam_karsilik'] ?? ($of['total_price'] ?? 0));
    $kdvRate = (float)($of['kdv'] ?? ($of['Kdv'] ?? 20));
    if ($kdvRate <= 0) $kdvRate = 20;

    $itemSub = $offerAmount / (1 + ($kdvRate / 100));
    $itemKdv = $offerAmount - $itemSub;

    $total_tl_ara_toplam += $itemSub;
    $total_kdv_tutari += $itemKdv;
    $total_kdv_dahil += $offerAmount;

    $subTitle = trim($of['offer_subject'] ?? '');
    if (empty($subTitle)) {
        $subTitle = 'TEKLİF ' . ($of['offerNumber'] ?? '');
    }

    $birimfiyat = tlFormat($offerAmount) . ' TRY';
    $toplamfiyat = tlFormat($offerAmount) . ' TRY';

    $items_html .= '<tr class="rows">
        <td style="width: 5%; text-align: center;">
            ' . $sira . '
        </td>
        <td style="width: 45%; text-align: left; text-transform: uppercase;">
            ' . htmlspecialchars($subTitle, ENT_QUOTES, 'UTF-8') . '
        </td>
        <td style="width: 10%; text-align: right;">1 SET</td>
        <td style="width: 20%; text-align: right;">' . $birimfiyat . '</td>
        <td style="width: 20%; text-align: right;">' . $toplamfiyat . '</td>
    </tr>';

    $sira++;
}

// HTML ÇIKTISI
$html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($customer['company'] ?? 'Müşteri', ENT_QUOTES, 'UTF-8') . ' - TEKLİF İCMALİ</title>
</head>
<style>
    body {
        font-family: "DejaVu Sans", sans-serif;
        margin: 0;
        padding: 0;
        font-size: 10px;
        color: #000000;
        line-height: 1.55;
    }

    @page {
        margin: 40px;
        font-size: 8px !important;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        max-width: 790px;
    }

    td, th {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .brand {
        text-align: right;
    }

    .brand strong {
        display: block;
        line-height: 1.55;
        margin-bottom: 4px;
    }

    .brand p {
        margin: 0;
    }

    .header-box {
        text-align: center;
        border-bottom: 2px solid #808080;
        border-top: 2px solid #808080;
        padding: 5px;
        font-size: 16px;
        margin: 10px 0;
        font-weight: bold;
    }

    .meta-table td {
        font-size: 10px;
        vertical-align: top;
        height: 19px;
    }

    .table-header th {
        background-color: #bbbbbb;
        border-bottom: 1px solid #808080;
        font-weight: bold;
        font-size: 10px;
    }

    .rows td {
        border-top: 1px solid #808080;
        border-bottom: 1px solid #808080;
        font-size: 10px;
        height: 30px;
        line-height: 1.55;
        padding-top: 3px;
        padding-bottom: 3px;
        vertical-align: middle;
    }

    #alt_toplam_table tr {
        border-bottom: 1px solid #808080;
    }

    #alt_toplam_table td {
        height: 22px;
        vertical-align: middle;
    }

    .border-bottom-1 {
        border-bottom: 1px solid #808080;
    }

    .border-none {
        border: none !important;
    }

    .text-right {
        text-align: right !important;
    }

    .text-center {
        text-align: center !important;
    }
</style>

<body>
    <!-- 1. Header (Logo & Firma Başlığı) -->
    <table style="width: 100%; margin-bottom: 6px;">
        <tr>
            <td style="width: 45%; vertical-align: middle;">
                <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
            </td>
            <td style="width: 55%; vertical-align: middle;" class="brand">
                <strong>' . format_company_header_title(set('company_name')) . '</strong>
                <p>' . htmlspecialchars(set('company_address') ?? '', ENT_QUOTES, 'UTF-8') . '</p>
                <p>Tel: ' . htmlspecialchars(set('company_phone1') ?? '', ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars(set('company_phone2') ?? '', ENT_QUOTES, 'UTF-8') . '</p>
                <p>' . htmlspecialchars(set('admin_mail') ?? '', ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars(set('panel_url') ?? '', ENT_QUOTES, 'UTF-8') . '</p>
            </td>
        </tr>
    </table>

    <!-- 2. Form Başlığı -->
    <div class="header-box">
        ' . $pagehead . '
    </div>

    <!-- 3. Müşteri & Teklif Meta Bilgileri -->
    <table class="meta-table" style="width: 100%; margin-bottom: 12px;">
        <tr>
            <td style="width: 10%; font-weight: bold;">Firma :</td>
            <td style="width: 50%;">' . htmlspecialchars($customer['company'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="width: 15%; font-weight: bold;">Teklif No :</td>
            <td style="width: 25%;">' . htmlspecialchars($firstOfferNumber, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Telefon :</td>
            <td>' . htmlspecialchars($customer['gsm'] ?? ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="font-weight: bold;">Tarih :</td>
            <td>' . $dateToday . '</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">E Posta :</td>
            <td>' . htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="font-weight: bold;">Referans :</td>
            <td>İCMAL DOSYASI</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">İlgili :</td>
            <td>' . htmlspecialchars($customer['yetkili'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="font-weight: bold;">Teklif Konusu :</td>
            <td>' . htmlspecialchars($offerSubject, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
    </table>

    <!-- 4. Giriş Açıklama Metni -->
    <div style="padding: 30px 0; font-size: 10px;">
        ' . $offer_header_content . '
    </div>

    <!-- 5. Teklif Kalemleri Tablosu -->
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <colgroup>
            <col style="width: 5%;">
            <col style="width: 45%;">
            <col style="width: 10%;">
            <col style="width: 20%;">
            <col style="width: 20%;">
        </colgroup>
        <tbody>
            <tr class="table-header" style="background-color: #bbbbbb; font-weight: bold; border-bottom: 1px solid #808080;">
                <td style="width: 5%; text-align: left; font-weight: bold; border-bottom: 1px solid #808080;">NO</td>
                <td style="width: 45%; text-align: left; font-weight: bold; border-bottom: 1px solid #808080;">ÜRÜN / HİZMET AÇIKLAMASI</td>
                <td style="width: 10%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">MİKTAR</td>
                <td style="width: 20%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">BİRİM FİYAT</td>
                <td style="width: 20%; text-align: right; font-weight: bold; border-bottom: 1px solid #808080;">TUTAR</td>
            </tr>
            ' . $items_html . '
        </tbody>
    </table>

    <!-- 6. Dip Toplamlar Tablosu -->
    <table id="alt_toplam_table" style="width: 100%; margin-top: 15px; border-collapse: collapse;">
        <tr class="border-none text-right">
            <td style="width: 60%;"></td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">ARA TOPLAM</td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">' . tlFormat($total_tl_ara_toplam) . ' TRY</td>
        </tr>
        <tr class="border-none text-right">
            <td style="width: 60%;"></td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">KDV %20</td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">' . tlFormat($total_kdv_tutari) . ' TRY</td>
        </tr>
        <tr class="border-none text-right">
            <td style="width: 60%;"></td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">KDV DAHİL</td>
            <td style="width: 20%; padding: 4px 6px;" class="border-bottom-1">' . tlFormat($total_kdv_dahil) . ' TRY</td>
        </tr>
        <tr class="border-none text-right">
            <td style="width: 60%;"></td>
            <td style="width: 20%; padding: 5px 6px; background-color: #bbbbbb; font-weight: bold;" class="border-bottom-1">GENEL TOPLAM</td>
            <td style="width: 20%; padding: 5px 6px; background-color: #bbbbbb; font-weight: bold;" class="border-bottom-1">' . tlFormat($total_kdv_dahil) . ' TRY</td>
        </tr>
    </table>

    <!-- 7. Dipnot & Şartlar -->
    <div style="padding: 15px 0 20px 0; font-size: 8.5px; color: #475569;">
        ' . (!empty($offer_footer_content) ? $offer_footer_content : '<p><strong>1.</strong> Fiyatlarımıza KDV dahildir.</p><p><strong>2.</strong> Ödeme Vadesi: Belirlenen ödeme planına göredir.</p><p><strong>3.</strong> Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.</p>') . '
    </div>

    <!-- 8. İmzalar -->
    <table style="width: 100%; text-align: center; margin-top: 10px;">
        <tr>
            <td style="width: 50%;"><strong>Oluşturan</strong></td>
            <td style="width: 50%;"><strong>Sipariş Onayı</strong></td>
        </tr>
        <tr>
            <td style="width: 50%; padding-top: 5px;">' . htmlspecialchars($creator['username'] ?? ($_SESSION['name'] ?? 'Yetkili'), ENT_QUOTES, 'UTF-8') . '</td>
            <td style="width: 50%; padding-top: 5px;">Firma Kaşesi / İmza</td>
        </tr>
        <tr>
            <td style="width: 50%; color: #64748b; font-size: 9px;">' . htmlspecialchars($creator['Unvan'] ?? 'Müşteri Temsilcisi', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="width: 50%; color: #64748b; font-size: 9px;">.</td>
        </tr>
    </table>
</body>
</html>';

// Dompdf Render
$options = new Options();
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

$pdf_file = ($customer['company'] ?? 'Musteri') . ' - Teklif Icmali.pdf';
$dompdf->render();

if (ob_get_length()) {
    ob_end_clean();
}

$canvas = $dompdf->getCanvas();
$canvas->page_script('
    $text = "$PAGE_NUM / $PAGE_COUNT";
    $pdf->text(80, 806.89, $text, \'Helvetica\', 8, array(0,0,0));
');

// PDF Output
$dompdf->stream($pdf_file, ['Attachment' => false]);
