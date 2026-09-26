<?php
// Standalone Print and PDF Generator for Price Requests
use App\Helper\Security;
use App\Model\PurchaseModel;
use Dompdf\Dompdf;
use Dompdf\Options;

// Bootstrap yukleme
if (!class_exists('App\Helper\Security')) {
    require_once dirname(__DIR__, 3) . "/bootstrap.php";
}

$userId = (int)($_SESSION['lid'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? 0);

// Oturum Kontrolu
if (empty($_SESSION['login']) || $userId <= 0) {
    while (ob_get_level()) { ob_end_clean(); }
    header("Location: login.php");
    exit;
}

$rawId = $_GET['id'] ?? 0;
$id = 0;
if (is_numeric($rawId)) {
    $id = (int)$rawId;
} else {
    try {
        $id = (int)Security::decrypt($rawId);
    } catch (\Throwable $e) {
        $id = 0;
    }
}

$Purchase = new PurchaseModel();
$purchase = $Purchase->find($id);

if (!$purchase) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Kayit Bulunamadi</title><link rel="stylesheet" href="/vendors/styles/style.css"></head><body class="p-4 text-center"><h4>Kayit bulunamadi!</h4><p>Istenen fiyat talebi kaydina ulasilamadi veya silinmis olabilir.</p><button onclick="window.close()" class="btn btn-secondary mt-2">Pencereyi Kapat</button></body></html>';
    exit;
}

$isCreator = ((int)($purchase->creator ?? 0) === $userId);
$isSuperAdmin = ($userId === 1);
$hasAllPerm = false;
try {
    $hasAllPerm = permtrue('tum_fiyat_taleplerini_gor');
} catch (\Throwable $e) {}

if (!$isSuperAdmin && !$isCreator && !$hasAllPerm) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Yetkisiz Erisim</title><link rel="stylesheet" href="/vendors/styles/style.css"></head><body class="p-4 text-center"><h4>Yetkisiz Erisim</h4><p>Bu fiyat talebini goruntuleme yetkiniz bulunmamaktadir.</p><button onclick="window.close()" class="btn btn-secondary mt-2">Pencereyi Kapat</button></body></html>';
    exit;
}

$items = $Purchase->getPurchaseItems($id);
$customer_name = getCustomerName($purchase->companyID);
$creator_name = getUserName($purchase->creator);

// Musteri Iletisim Bilgileri
$cust_extra = null;
if (!empty($purchase->companyID)) {
    try {
        $cust_sql = $ac->prepare("SELECT yetkili, email, telefon, gsm, city, address FROM customers WHERE id = ?");
        $cust_sql->execute([$purchase->companyID]);
        $cust_extra = $cust_sql->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

if (!function_exists('parseCurrencyNumber')) {
    function parseCurrencyNumber($val): float {
        if (is_numeric($val)) return (float)$val;
        if (is_string($val)) {
            $val = trim($val);
            if ($val === '') return 0.0;
            if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (strpos($val, ',') !== false) {
                $val = str_replace(',', '.', $val);
            }
            return is_numeric($val) ? (float)$val : 0.0;
        }
        return 0.0;
    }
}

function getLogoBase64(): string {
    $rootDir = dirname(__DIR__, 3);
    $candidates = [
        $rootDir . '/src/images/logo.png',
        $rootDir . '/vendors/images/logo.png',
    ];
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/src/images/logo.png';
        $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/vendors/images/logo.png';
    }
    foreach ($candidates as $path) {
        if (file_exists($path) && is_readable($path)) {
            $data = base64_encode(file_get_contents($path));
            $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'image/png';
            return 'data:' . $mime . ';base64,' . $data;
        }
    }
    return '';
}

// Log Kaydi
try {
    $logger = \getLogger("Fiyat Talepleri");
    $logger->info("Fiyat talebi yazdirildi / PDF istendi.", [
        'id' => $id,
        'siparisNo' => $purchase->siparisNo,
        'pdf_mode' => isset($_GET['pdf']),
        'user_id' => $userId
    ]);
} catch (\Throwable $e) {}

$logoSrc = getLogoBase64();
$companyHeader = format_company_header_title(set('company_name'));
$companyAddress = set('company_address');
$companyPhone = set('company_phone1');
$companyMail = set('admin_mail');

$createDateFormatted = !empty($purchase->create_time) ? date('d.m.Y H:i', strtotime($purchase->create_time)) : '-';
$deadlineFormatted = !empty($purchase->deadline) ? date('d.m.Y', strtotime($purchase->deadline)) : '-';

$altToplam = parseCurrencyNumber($purchase->altToplam ?? 0);
$tlTotal = parseCurrencyNumber($purchase->TLTotal ?? 0);
if ($tlTotal <= 0 && $altToplam > 0) {
    $tlTotal = $altToplam;
}

$isPdf = isset($_GET['pdf']) && $_GET['pdf'] == '1';

// HTML Ciktisi Hazirlama
ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fiyat Talebi - <?php echo htmlspecialchars($purchase->siparisNo ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: "DejaVu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: <?php echo $isPdf ? '#ffffff' : '#f1f5f9'; ?>;
            margin: 0;
            padding: <?php echo $isPdf ? '0' : '20px'; ?>;
            line-height: 1.4;
        }
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 15mm 10mm;
        }
        
        /* Print Toolbar */
        .print-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 800px;
            margin: 0 auto 16px auto;
            background: #1e293b;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .print-toolbar-title {
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-toolbar-actions {
            display: flex;
            gap: 8px;
        }
        .toolbar-btn {
            background: #ffffff;
            color: #1e293b;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .toolbar-btn:hover {
            background: #f1f5f9;
        }
        .toolbar-btn-primary {
            background: #7c3aed;
            color: #ffffff;
        }
        .toolbar-btn-primary:hover {
            background: #6d28d9;
            color: #ffffff;
        }
        .toolbar-btn-danger {
            background: #dc2626;
            color: #ffffff;
        }
        .toolbar-btn-danger:hover {
            background: #b91c1c;
            color: #ffffff;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
                background: #ffffff !important;
            }
            .document-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }

        .document-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            <?php if (!$isPdf): ?>
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            padding: 30px;
            <?php endif; ?>
        }

        .doc-header {
            width: 100%;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .doc-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-logo {
            max-height: 48px;
            max-width: 160px;
        }
        .company-info {
            text-align: right;
            font-size: 9.5px;
            color: #475569;
            line-height: 1.35;
        }
        .company-info strong {
            color: #0f172a;
            font-size: 11px;
        }

        .doc-title-bar {
            text-align: center;
            margin: 12px 0 16px 0;
            position: relative;
        }
        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #7c3aed;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .info-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .info-grid-table td {
            padding: 6px 10px;
            font-size: 10.5px;
            vertical-align: top;
            border: 0.5px solid #e2e8f0;
        }
        .info-label {
            font-weight: 700;
            color: #475569;
            width: 16%;
            background: #f1f5f9;
        }
        .info-val {
            color: #0f172a;
            width: 34%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) {
            background: #fbfcfe;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: 700; }

        .total-wrapper {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .total-wrapper table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        .total-wrapper td {
            padding: 6px 10px;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
        }
        .total-wrapper tr.grand-total {
            background: #f3e8ff;
            font-weight: 800;
            font-size: 12px;
            color: #7c3aed;
            border-top: 2px solid #7c3aed;
        }

        .note-section {
            background: #f8fafc;
            border-left: 3px solid #7c3aed;
            padding: 8px 12px;
            margin-bottom: 24px;
            font-size: 10.5px;
        }
        .note-section strong {
            display: block;
            margin-bottom: 4px;
            color: #7c3aed;
            font-size: 11px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 45%;
            vertical-align: top;
            text-align: center;
            font-size: 10.5px;
            padding: 10px;
        }
        .sig-box {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            margin-top: 45px;
        }

        .doc-footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="print-toolbar no-print">
    <div class="print-toolbar-title">
        Fiyat Talebi: <?php echo htmlspecialchars($purchase->siparisNo ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div class="print-toolbar-actions">
        <button type="button" class="toolbar-btn toolbar-btn-primary" onclick="window.print()">
            Yazdır
        </button>
        <a href="index.php?p=purchases/price-request-print&id=<?php echo $id; ?>&pdf=1" class="toolbar-btn toolbar-btn-danger" target="_blank">
            PDF İndir
        </a>
        <button type="button" class="toolbar-btn" onclick="window.close()">
            Kapat
        </button>
    </div>
</div>
<?php endif; ?>

<div class="document-container">
    <!-- Header -->
    <div class="doc-header">
        <table>
            <tr>
                <td style="width: 40%; vertical-align: middle;">
                    <?php if (!empty($logoSrc)): ?>
                        <img src="<?php echo $logoSrc; ?>" alt="Logo" class="doc-logo">
                    <?php else: ?>
                        <span style="font-size: 18px; font-weight: bold; color: #7c3aed;"><?php echo htmlspecialchars($companyHeader ?: 'AYDINOĞULLARI', ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </td>
                <td style="width: 60%; vertical-align: middle;" class="company-info">
                    <strong><?php echo htmlspecialchars($companyHeader, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <?php if (!empty($companyAddress)): ?><?php echo htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                    <?php if (!empty($companyPhone)): ?>Tel: <?php echo htmlspecialchars($companyPhone, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                    <?php if (!empty($companyMail)): ?> | E-posta: <?php echo htmlspecialchars($companyMail, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Title -->
    <div class="doc-title-bar">
        <h2 class="doc-title">FİYAT TALEP FORMU</h2>
    </div>

    <!-- Metadata Grid -->
    <table class="info-grid-table">
        <tr>
            <td class="info-label">Talep No:</td>
            <td class="info-val text-bold"><?php echo htmlspecialchars($purchase->siparisNo ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="info-label">Tedarikçi/Firma:</td>
            <td class="info-val text-bold"><?php echo htmlspecialchars($customer_name ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="info-label">Kayıt Tarihi:</td>
            <td class="info-val"><?php echo htmlspecialchars($createDateFormatted, ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="info-label">İlgili Kişi:</td>
            <td class="info-val"><?php echo htmlspecialchars($cust_extra['yetkili'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="info-label">Termin Tarihi:</td>
            <td class="info-val"><?php echo htmlspecialchars($deadlineFormatted, ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="info-label">İletişim / E-posta:</td>
            <td class="info-val">
                <?php 
                $contactInfo = [];
                if (!empty($cust_extra['email'])) $contactInfo[] = $cust_extra['email'];
                if (!empty($cust_extra['gsm'])) $contactInfo[] = $cust_extra['gsm'];
                elseif (!empty($cust_extra['telefon'])) $contactInfo[] = $cust_extra['telefon'];
                echo htmlspecialchars(!empty($contactInfo) ? implode(' / ', $contactInfo) : '-', ENT_QUOTES, 'UTF-8');
                ?>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">#</th>
                <th style="width: 90px;">Stok Kodu</th>
                <th>Ürün / Hizmet Açıklaması</th>
                <th style="width: 55px;" class="text-center">Miktar</th>
                <th style="width: 50px;" class="text-center">Birim</th>
                <th style="width: 80px;" class="text-right">B.Fiyat</th>
                <th style="width: 90px;" class="text-right">Toplam</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">Kayıtlı ürün veya hizmet kalemi bulunamadı.</td>
                </tr>
            <?php else: ?>
                <?php 
                $i = 0;
                $calculatedTotal = 0.0;
                foreach ($items as $item): 
                    $i++;
                    $amount = parseCurrencyNumber($item->amount ?? 0);
                    $price = parseCurrencyNumber($item->price ?? 0);
                    $rowTotal = $amount * $price;
                    $calculatedTotal += $rowTotal;
                    $curr = !empty($item->currency) ? htmlspecialchars($item->currency, ENT_QUOTES, 'UTF-8') : 'TRY';
                    $unit = !empty($item->unit) ? htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') : 'Adet';
                    $stokKodu = !empty($item->stokKodu) ? htmlspecialchars($item->stokKodu, ENT_QUOTES, 'UTF-8') : '-';
                ?>
                <tr>
                    <td class="text-center"><?php echo $i; ?></td>
                    <td><?php echo $stokKodu; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($item->product ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if (!empty($item->description)): ?>
                            <br><small style="color:#64748b; font-size: 9.5px;"><?php echo nl2br(htmlspecialchars($item->description, ENT_QUOTES, 'UTF-8')); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo rtrim(rtrim(number_format($amount, 2, ',', '.'), '0'), ','); ?></td>
                    <td class="text-center"><?php echo $unit; ?></td>
                    <td class="text-right"><?php echo number_format($price, 2, ',', '.') . ' ' . $curr; ?></td>
                    <td class="text-right text-bold"><?php echo number_format($rowTotal, 2, ',', '.') . ' ' . $curr; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="total-wrapper">
        <table>
            <tr>
                <td style="width: 55%;" class="text-bold">Ara Toplam:</td>
                <td style="width: 45%;" class="text-right"><?php echo number_format($tlTotal > 0 ? $tlTotal : $calculatedTotal, 2, ',', '.'); ?> ₺</td>
            </tr>
            <tr class="grand-total">
                <td>GENEL TOPLAM:</td>
                <td class="text-right"><?php echo number_format($altToplam > 0 ? $altToplam : ($tlTotal > 0 ? $tlTotal : $calculatedTotal), 2, ',', '.'); ?> ₺</td>
            </tr>
        </table>
    </div>

    <!-- Notes / Description -->
    <?php if (!empty($purchase->description1)): ?>
    <div class="note-section">
        <strong>Açıklama / Özel Notlar:</strong>
        <?php echo nl2br(htmlspecialchars($purchase->description1, ENT_QUOTES, 'UTF-8')); ?>
    </div>
    <?php endif; ?>

    <!-- Signatures -->
    <table class="signatures-table">
        <tr>
            <td>
                <strong>Talep Eden / Hazırlayan</strong><br>
                <div class="sig-box">
                    <strong><?php echo htmlspecialchars($creator_name ?: 'Yetkili', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    İmza
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td>
                <strong>Tedarikçi / Firma Onayı</strong><br>
                <div class="sig-box">
                    Kaşe / Yetkili İmza
                </div>
            </td>
        </tr>
    </table>

    <div class="doc-footer">
        Bu belge <?php echo date('d.m.Y H:i'); ?> tarihinde sistem üzerinden üretilmiştir.
    </div>
</div>

<?php if (!$isPdf): ?>
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 350);
    });
</script>
<?php endif; ?>

</body>
</html>
<?php
$htmlContent = ob_get_clean();

while (ob_get_level()) {
    ob_end_clean();
}

if ($isPdf) {
    try {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $fileName = ($purchase->siparisNo ? $purchase->siparisNo : 'Fiyat_Talebi') . ".pdf";
        $dompdf->stream($fileName, ["Attachment" => 0]);
        exit;
    } catch (\Throwable $ex) {
        error_log("PDF generation error: " . $ex->getMessage());
        http_response_code(500);
        echo "PDF oluşturulurken bir hata meydana geldi: " . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
} else {
    echo $htmlContent;
    exit;
}
