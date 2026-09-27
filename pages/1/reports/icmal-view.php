<?php
/**
 * Resmi Firma Rapor & Ekipman İcmal Formu (PDF / Görüntüleme)
 */
ini_set('display_errors', 'Off');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!function_exists('toBase64')) {
    function toBase64($image)
    {
        if (!empty($image) && file_exists($image)) {
            $data = base64_encode(file_get_contents($image));
            $mime = @mime_content_type($image) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . $data;
        }
        return '';
    }
}

$cid = (int)($_POST['cid'] ?? $_GET['cid'] ?? 0);
$selectedReportIds = [];

$reportsParam = $_POST['reports'] ?? $_GET['reports'] ?? null;
if (!empty($reportsParam)) {
    if (is_array($reportsParam)) {
        $selectedReportIds = array_filter(array_map('intval', $reportsParam));
    } else {
        $selectedReportIds = array_filter(array_map('intval', explode(',', (string)$reportsParam)));
    }
}

if (!$cid && empty($selectedReportIds)) {
    die('Geçersiz parametre!');
}

// Müşteri bilgileri
if ($cid > 0) {
    $custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
    $custquery->execute([$cid]);
    $customer = $custquery->fetch(PDO::FETCH_ASSOC);
} else {
    $customer = [];
}

// Raporları çek
$reports = [];
if (!empty($selectedReportIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedReportIds), '?'));
    $stmt = $ac->prepare("SELECT 
            r.*,
            COALESCE(rt.reportName, 'Rapor') as report_name,
            COALESCE(rt.page_link, 'ysc') as page_link,
            u_co.username as controller_name,
            u_co.imza_file as controller_imza,
            u_of.username as official_name,
            u_of.imza_file as official_imza
        FROM reports r
        LEFT JOIN report_types rt ON r.report_type = rt.id
        LEFT JOIN users u_co ON r.controller_id = u_co.id
        LEFT JOIN users u_of ON r.company_official = u_of.id
        WHERE r.id IN ($placeholders)
        ORDER BY r.id DESC");
    $stmt->execute($selectedReportIds);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($customer) && !empty($reports)) {
        $cid = (int)$reports[0]['customer_id'];
        $custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
        $custquery->execute([$cid]);
        $customer = $custquery->fetch(PDO::FETCH_ASSOC);
    }
} elseif ($cid > 0) {
    $stmt = $ac->prepare("SELECT 
            r.*,
            COALESCE(rt.reportName, 'Rapor') as report_name,
            COALESCE(rt.page_link, 'ysc') as page_link,
            u_co.username as controller_name,
            u_co.imza_file as controller_imza,
            u_of.username as official_name,
            u_of.imza_file as official_imza
        FROM reports r
        LEFT JOIN report_types rt ON r.report_type = rt.id
        LEFT JOIN users u_co ON r.controller_id = u_co.id
        LEFT JOIN users u_of ON r.company_official = u_of.id
        WHERE r.customer_id = ?
        ORDER BY r.id DESC");
    $stmt->execute([$cid]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($reports)) {
    die('Yazdırılacak rapor bulunamadı!');
}

$custom_notes = $_POST['notes'] ?? $_GET['notes'] ?? '';
$documentTitle = ($customer['company'] ?? 'Musteri') . ' - Rapor İcmali';

// Hazırlıklı alt kalem sorguları
$stmtYsc = $ac->prepare("SELECT * FROM report_ysc_content WHERE report_id = ? ORDER BY id ASC");
$stmtHst = $ac->prepare("SELECT * FROM report_hst_content WHERE report_id = ? ORDER BY id ASC");
$stmtMet = $ac->prepare("SELECT * FROM report_met_content WHERE report_id = ? ORDER BY id ASC");
$stmtGen = $ac->prepare("SELECT * FROM report_contents WHERE report_id = ? ORDER BY id ASC");

$html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') . '</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            margin: 0;
            padding: 0;
            font-size: 9px;
            color: #1e293b;
            line-height: 1.4;
        }
        @page {
            margin: 20px 25px 30px 25px;
            size: A4 portrait;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .doc-brand {
            text-align: right;
            font-size: 8.5px;
            line-height: 1.35;
        }
        .doc-brand strong {
            font-size: 10.5px;
            color: #0f172a;
            display: block;
            margin-bottom: 2px;
        }
        .doc-brand p {
            margin: 0;
            color: #475569;
        }
        .doc-title-bar {
            border-top: 2px solid #334155;
            border-bottom: 2px solid #334155;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            padding: 6px 0;
            margin: 8px 0 10px 0;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .meta-table {
            margin-bottom: 12px;
            font-size: 8.5px;
            border: 1px solid #cbd5e1;
        }
        .meta-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-label {
            font-weight: bold;
            color: #334155;
            background: #f8fafc;
            width: 15%;
        }
        .meta-val {
            color: #0f172a;
            width: 35%;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            background: #e2e8f0;
            padding: 4px 8px;
            margin-top: 10px;
            margin-bottom: 4px;
            border-left: 3px solid #3b82f6;
        }
        .data-table {
            margin-bottom: 10px;
            font-size: 8px;
            page-break-inside: auto;
        }
        .data-table th {
            background: #334155;
            color: #ffffff;
            font-weight: bold;
            padding: 4px 5px;
            text-align: left;
            border: 1px solid #1e293b;
            font-size: 7.5px;
        }
        .data-table td {
            padding: 3.5px 5px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .font-weight-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-ysc { background: #fee2e2; color: #991b1b; }
        .badge-hst { background: #e0f2fe; color: #075985; }
        .badge-met { background: #f3e8ff; color: #6b21a8; }
        .badge-other { background: #ecfdf5; color: #065f46; }
        .footer-signatures {
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            font-size: 8.5px;
            width: 45%;
        }
    </style>
</head>
<body>

    <!-- Header / Logo & Firma Bilgileri -->
    <table class="header-table">
        <tr>
            <td style="width: 40%;">
                <img src="' . toBase64('src/images/logo.png') . '" style="max-width: 170px; height: auto;" alt="Logo">
            </td>
            <td style="width: 60%;" class="doc-brand">
                <strong>' . htmlspecialchars((string)set('company_name'), ENT_QUOTES, 'UTF-8') . '</strong>
                <p>' . htmlspecialchars((string)set('company_address'), ENT_QUOTES, 'UTF-8') . '</p>
                <p>Tel: ' . htmlspecialchars((string)set('company_phone1'), ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars((string)set('company_phone2'), ENT_QUOTES, 'UTF-8') . '</p>
                <p>' . htmlspecialchars((string)set('admin_mail'), ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars((string)set('panel_url'), ENT_QUOTES, 'UTF-8') . '</p>
            </td>
        </tr>
    </table>

    <!-- Belge Başlığı -->
    <div class="doc-title-bar">
        FİRMA KONTROL VE MUAYENE RAPORLARI İCMAL FORMU
    </div>

    <!-- Müşteri Meta Bilgileri -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Firma Adı :</td>
            <td class="meta-val" style="font-weight: bold;">' . htmlspecialchars($customer['company'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
            <td class="meta-label">İcmal Tarihi :</td>
            <td class="meta-val">' . date('d.m.Y H:i') . '</td>
        </tr>
        <tr>
            <td class="meta-label">Telefon :</td>
            <td class="meta-val">' . htmlspecialchars($customer['gsm'] ?? ($customer['phone'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>
            <td class="meta-label">Rapor Sayısı :</td>
            <td class="meta-val" style="font-weight: bold;">' . count($reports) . ' Adet Rapor</td>
        </tr>
        <tr>
            <td class="meta-label">E-Posta :</td>
            <td class="meta-val">' . htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
            <td class="meta-label">İlgili Yetkili :</td>
            <td class="meta-val">' . htmlspecialchars($customer['yetkili'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td class="meta-label">Adres :</td>
            <td class="meta-val" colspan="3">' . htmlspecialchars(($customer['address'] ?? '') . ' ' . ($customer['city'] ?? '') . '/' . ($customer['ilce'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
    </table>

    <!-- 1. RAPORLAR LİSTESİ ÖZETİ -->
    <div class="section-title">1. SEÇİLEN KONTROL VE MUAYENE RAPORLARI ÖZETİ</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 16%;">Rapor No</th>
                <th style="width: 26%;">Rapor Türü</th>
                <th style="width: 12%;" class="text-center">İş Emri No</th>
                <th style="width: 12%;" class="text-center">Kontrol Tarihi</th>
                <th style="width: 12%;" class="text-center">Geçerlilik Tarihi</th>
                <th style="width: 18%;">Kontrol Eden</th>
            </tr>
        </thead>
        <tbody>';

$totalConsolidatedItems = 0;
foreach ($reports as $index => $rep) {
    $html .= '<tr>
        <td class="text-center">' . ($index + 1) . '</td>
        <td class="font-weight-bold">' . htmlspecialchars($rep['report_number'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($rep['report_name'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-center">' . htmlspecialchars($rep['isemrino'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-center">' . htmlspecialchars($rep['control_date'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-center">' . htmlspecialchars($rep['validity_date'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($rep['controller_name'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
    </tr>';
}

$html .= '</tbody>
    </table>

    <!-- 2. DETAYLI EKİPMAN VE TEST KALEMLERİ DÖKÜMÜ -->
    <div class="section-title">2. DETAYLI EKİPMAN, CİHAZ VE TEST KALEMLERİ DÖKÜMÜ</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 14%;">Rapor No</th>
                <th style="width: 12%;">Cihaz / Test No</th>
                <th style="width: 22%;">Cihaz Cinsi / Açıklama</th>
                <th style="width: 18%;">Bulunduğu Bölge</th>
                <th style="width: 10%;" class="text-center">Dolum / İmal</th>
                <th style="width: 10%;" class="text-center">Son Kull.</th>
                <th style="width: 10%;" class="text-center">Sonuç</th>
            </tr>
        </thead>
        <tbody>';

$itemSeq = 1;
foreach ($reports as $rep) {
    $rid = (int)$rep['id'];
    $pageLink = strtolower(trim((string)($rep['page_link'] ?? '')));
    $reportType = (int)($rep['report_type'] ?? 1);
    $items = [];

    if ($pageLink === 'ysc' || $reportType === 1) {
        $stmtYsc->execute([$rid]);
        $items = $stmtYsc->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($pageLink === 'hst' || $reportType === 2) {
        $stmtHst->execute([$rid]);
        $items = $stmtHst->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($pageLink === 'met' || $reportType === 3) {
        $stmtMet->execute([$rid]);
        $items = $stmtMet->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmtGen->execute([$rid]);
        $items = $stmtGen->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($items)) {
        foreach ($items as $item) {
            $cihazNo = '';
            $cinsi = '';
            $bolge = '';
            $dolumTrh = '';
            $sonKullTrh = '';
            $sonuc = 'Uygun';

            if ($pageLink === 'ysc' || $reportType === 1) {
                $cihazNo = $item['cihaz_no'] ?? '-';
                $cinsi = $item['cinsi'] ?? '-';
                $bolge = $item['bulundugu_bolge'] ?? '-';
                $dolumTrh = $item['cihaz_dolum_tarihi'] ?? '-';
                $sonKullTrh = $item['cihaz_sonkullanma_tarihi'] ?? '-';
                $sonuc = 'Uygun';
            } elseif ($pageLink === 'hst' || $reportType === 2) {
                $cihazNo = $item['testno'] ?? ($item['serino'] ?? '-');
                $cinsi = ($item['cinsi'] ?? '') . (!empty($item['kg']) ? ' (' . $item['kg'] . ' KG)' : '');
                $bolge = $item['imalatci_firma'] ?? '-';
                $dolumTrh = $item['imal_tarihi'] ?? '-';
                $sonKullTrh = $item['serino'] ?? '-';
                $sonuc = ($item['sizdirmazlik_deneyi'] == '1' && $item['esneme_deneyi'] == '1') ? 'Başarılı' : 'Kusurlu';
            } elseif ($pageLink === 'met' || $reportType === 3) {
                $cihazNo = 'MET-' . ($item['id'] ?? '-');
                $cinsi = $item['cinsi'] ?? '-';
                $bolge = $item['bulundugu_kisim'] ?? '-';
                $dolumTrh = $item['control_date_closet'] ?? '-';
                $sonKullTrh = $item['next_control_date_closet'] ?? '-';
                $sonuc = ($item['basinc_degeri'] ?? '-') . ' Bar';
            } else {
                $cihazNo = 'SYS-' . ($item['id'] ?? '-');
                $cinsi = $item['algilama_cinsi'] ?? 'Sistem Cihazı';
                $bolge = $item['bulundugu_bolge'] ?? '-';
                $dolumTrh = '-';
                $sonKullTrh = '-';
                $sonuc = ($item['calisabilirlik_testi'] ?? 1) == 1 ? 'Çalışır' : 'Kusurlu';
            }

            $html .= '<tr>
                <td class="text-center">' . $itemSeq . '</td>
                <td class="font-weight-bold">' . htmlspecialchars($rep['report_number'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center">' . htmlspecialchars($cihazNo, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($cinsi, ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . htmlspecialchars($bolge, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center">' . htmlspecialchars($dolumTrh, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center">' . htmlspecialchars($sonKullTrh, ENT_QUOTES, 'UTF-8') . '</td>
                <td class="text-center font-weight-bold">' . htmlspecialchars($sonuc, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>';
            $itemSeq++;
            $totalConsolidatedItems++;
        }
    }
}

if ($totalConsolidatedItems === 0) {
    $html .= '<tr><td colspan="8" class="text-center" style="padding: 10px; color: #64748b;">Seçilen raporlarda kayıtlı alt ekipman kalemi bulunmamaktadır.</td></tr>';
}

$html .= '</tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="3" class="text-right">TOPLAM KALEM:</td>
                <td colspan="5">' . $totalConsolidatedItems . ' Adet Ekipman / Cihaz / Test Kalemi (' . count($reports) . ' Rapor)</td>
            </tr>
        </tfoot>
    </table>';

// Dipnot / Şartlar
if (!empty($custom_notes)) {
    $html .= '<div style="margin-top: 10px; padding: 6px 8px; border: 1px dashed #cbd5e1; background: #f8fafc; font-size: 8px;">
        <strong>İCMAL NOTLARI / AÇIKLAMALAR:</strong><br>' . nl2br(htmlspecialchars($custom_notes, ENT_QUOTES, 'UTF-8')) . '
    </div>';
}

// İmzalar
$html .= '<div class="footer-signatures">
        <table style="width: 100%; border: none;">
            <tr>
                <td class="signature-box" style="border: none;">
                    <strong>KONTROLÜ YAPAN / TEKNİSYEN</strong><br>
                    <span style="font-size: 8px; color: #64748b;">' . htmlspecialchars($reports[0]['controller_name'] ?? 'Yetkili Personel', ENT_QUOTES, 'UTF-8') . '</span><br><br>';
if (!empty($reports[0]['controller_imza']) && file_exists('files/imzalar/' . $reports[0]['controller_imza'])) {
    $html .= '<img src="' . toBase64('files/imzalar/' . $reports[0]['controller_imza']) . '" style="max-height: 45px; max-width: 120px;" alt="İmza"><br>';
} else {
    $html .= '<br><br>';
}
$html .= '<span>İmza / Kaşe</span>
                </td>
                <td style="width: 10%; border: none;"></td>
                <td class="signature-box" style="border: none;">
                    <strong>FİRMA YETKİLİSİ</strong><br>
                    <span style="font-size: 8px; color: #64748b;">' . htmlspecialchars($customer['yetkili'] ?? ($reports[0]['official_name'] ?? 'Müşteri Temsilcisi'), ENT_QUOTES, 'UTF-8') . '</span><br><br>';
if (!empty($reports[0]['official_imza']) && file_exists('files/imzalar/' . $reports[0]['official_imza'])) {
    $html .= '<img src="' . toBase64('files/imzalar/' . $reports[0]['official_imza']) . '" style="max-height: 45px; max-width: 120px;" alt="İmza"><br>';
} else {
    $html .= '<br><br>';
}
$html .= '<span>Teslim Alan İmza</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Firma_Rapor_Icmali_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $customer['company'] ?? 'Musteri') . '.pdf';
$dompdf->stream($filename, ['Attachment' => 0]);
exit;
