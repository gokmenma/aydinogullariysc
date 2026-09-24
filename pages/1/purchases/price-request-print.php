<?php
require_once dirname(__DIR__, 3) . "/bootstrap.php";

use App\Helper\Helper;
use App\Model\PurchaseModel;
use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? 0;
$Purchase = new PurchaseModel();
$purchase = $Purchase->find($id);

if (!$purchase) {
    echo "Kayıt bulunamadı!";
    exit;
}

$items = $Purchase->getPurchaseItems($id);
$customer_name = getCustomerName($purchase->companyID);
$creator_name = getUserName($purchase->creator);

// Fetch customer extra info
$cust_sql = $ac->prepare("SELECT yetkili, email FROM customers WHERE id = ?");
$cust_sql->execute([$purchase->companyID]);
$cust_extra = $cust_sql->fetch(PDO::FETCH_ASSOC);

function toBase64($image) {
    if (file_exists($image)) {
        $data = base64_encode(file_get_contents($image));
        return 'data:' . mime_content_type($image) . ';base64,' . $data;
    }
    return '';
}

$html = '
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fiyat Talebi - ' . $purchase->siparisNo . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 150px; }
        .company-info { text-align: right; }
        .title { text-align: center; font-size: 18px; font-weight: bold; color: #3b82f6; margin: 20px 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        .items-table td { border: 1px solid #cbd5e1; padding: 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 50px; width: 100%; }
        .signature-box { width: 45%; display: inline-block; text-align: center; border-top: 1px solid #333; padding-top: 10px; }
        .total-section { margin-top: 20px; text-align: right; }
        .total-box { display: inline-block; width: 250px; }
        .total-row { padding: 5px; border-bottom: 1px solid #eee; }
        .total-label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width:100%">
            <tr>
                <td><img src="' . toBase64('src/images/logo.png') . '" class="logo"></td>
                <td class="company-info">
                    <strong>' . format_company_header_title(set('company_name')) . '</strong><br>
                    ' . set('company_address') . '<br>
                    Tel: ' . set('company_phone1') . '<br>
                    ' . set('admin_mail') . '
                </td>
            </tr>
        </table>
    </div>

    <div class="title">FİYAT TALEBİ</div>

    <table class="info-table">
        <tr>
            <td style="width:15%"><strong>Talep No:</strong></td>
            <td style="width:35%">' . $purchase->siparisNo . '</td>
            <td style="width:15%"><strong>Firma:</strong></td>
            <td style="width:35%">' . $customer_name . '</td>
        </tr>
        <tr>
            <td><strong>Tarih:</strong></td>
            <td>' . $purchase->create_time . '</td>
            <td><strong>Yetkili:</strong></td>
            <td>' . ($cust_extra['yetkili'] ?? '-') . '</td>
        </tr>
        <tr>
            <td><strong>Termin:</strong></td>
            <td>' . $purchase->deadline . '</td>
            <td><strong>E-Posta:</strong></td>
            <td>' . ($cust_extra['email'] ?? '-') . '</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th style="width:100px">Stok Kodu</th>
                <th>Ürün / Hizmet Açıklaması</th>
                <th style="width:60px" class="text-center">Miktar</th>
                <th style="width:60px" class="text-center">Birim</th>
                <th style="width:80px" class="text-right">B.Fiyat</th>
                <th style="width:90px" class="text-right">Toplam</th>
            </tr>
        </thead>
        <tbody>';

$i = 0;
foreach ($items as $item) {
    $i++;
    $rowTotal = $item->amount * $item->price;
    $html .= '
            <tr>
                <td class="text-center">' . $i . '</td>
                <td>' . $item->stokKodu . '</td>
                <td>' . $item->product . (!empty($item->description) ? '<br><small style="color:#666">' . $item->description . '</small>' : '') . '</td>
                <td class="text-center">' . $item->amount . '</td>
                <td class="text-center">' . $item->unit . '</td>
                <td class="text-right">' . number_format($item->price, 2, ',', '.') . ' ' . $item->currency . '</td>
                <td class="text-right">' . number_format($rowTotal, 2, ',', '.') . ' ' . $item->currency . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-box">
            <div class="total-row">
                <span class="total-label">ARA TOPLAM:</span>
                <span>' . number_format($purchase->TLTotal, 2, ',', '.') . ' ₺</span>
            </div>
            <div class="total-row" style="background:#f1f5f9; font-size:12px; font-weight:bold; border-bottom: 2px solid #3b82f6;">
                <span class="total-label">GENEL TOPLAM:</span>
                <span>' . number_format($purchase->altToplam, 2, ',', '.') . ' ₺</span>
            </div>
        </div>
    </div>

    <div style="margin-top:20px">
        <strong>Açıklama:</strong><br>
        ' . nl2br($purchase->description1) . '
    </div>

    <div class="footer">
        <div class="signature-box" style="float:left">
            <strong>Hazırlayan</strong><br><br><br>
            ' . $creator_name . '
        </div>
        <div class="signature-box" style="float:right">
            <strong>Firma Onayı</strong><br><br><br>
            Kaşe / İmza
        </div>
    </div>
</body>
</html>';

if (isset($_GET['pdf'])) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($purchase->siparisNo . ".pdf", array("Attachment" => false));
} else {
    echo $html;
    echo '<script>window.print();</script>';
}
