<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helper\Security;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['login'])) {
    http_response_code(403);
    exit('Bu işlem için oturum açmanız gerekmektedir.');
}

if (!permtrue('data_export_offers') && !permtrue('offeredit') && !permtrue('offerview') && !permtrue('offerlist')) {
    http_response_code(403);
    exit('Bu işlem için Excel dışa aktarma yetkiniz bulunmamaktadır.');
}

$rawId = $_GET['id'] ?? '';
$id = 0;
if (!empty($rawId)) {
    $decrypted = Security::decrypt($rawId);
    if ($decrypted !== false && is_numeric($decrypted)) {
        $id = (int)$decrypted;
    } elseif (is_numeric($rawId)) {
        $id = (int)$rawId;
    }
}

if ($id <= 0) {
    http_response_code(404);
    exit('Geçersiz veya eksik teklif ID.');
}

// Teklif ve teklif ürünlerini al
$sql = $ac->prepare("SELECT * FROM offers WHERE id = ?");
$sql->execute([$id]);
$offer = $sql->fetch(PDO::FETCH_OBJ);

if (!$offer) {
    http_response_code(404);
    exit('Teklif bulunamadı.');
}

// Firma Bilgilerini getir
$customer = null;
if (!empty($offer->cid)) {
    $sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
    $sql->execute([$offer->cid]);
    $customer = $sql->fetch(PDO::FETCH_OBJ);
}

// Ürünleri Getir
$sql = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
$sql->execute([$id]);
$offerProducts = $sql->fetchAll(PDO::FETCH_OBJ);

// Oluşturan Bilgileri
$creator = null;
if (!empty($offer->creativer)) {
    $crtquery = $ac->prepare('SELECT * FROM users WHERE id = ?');
    $crtquery->execute([(int)$offer->creativer]);
    $creator = $crtquery->fetch(PDO::FETCH_OBJ);
}

// Yeni bir Spreadsheet nesnesi oluşturun
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlıkları ekleyin
$sheet->setCellValue('B7', 'FİYAT TEKLİF FORMU');
$sheet->mergeCells('B7:O7');
$sheet->getStyle('B7:O7')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B7:O7')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getRowDimension('7')->setRowHeight(30);
$sheet->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('B7')->getFont()->setSize(20)->setBold(true);

$companyName = (string)set('company_name');
$sheet->setCellValue('O2', mb_strtoupper($companyName, 'UTF-8'));
$sheet->getStyle('O2')->getFont()->setSize(14)->setBold(true);

$sheet->setCellValue('O3', (string)set('company_address'));
$sheet->setCellValue('O4', set('company_phone1') . ' / ' . set('company_phone2'));
$sheet->setCellValue('O5', set('admin_mail') . ' / ' . set('panel_url'));

foreach (['O'] as $columnID) {
    $sheet->getStyle($columnID)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

$sheet->setCellValue('B9', 'Firma Adı :')->getStyle('B9')->getFont()->setBold(true);
$sheet->setCellValue('D9', $customer ? (string)$customer->company : '');

$sheet->setCellValue('B10', 'Telefon :')->getStyle('B10')->getFont()->setBold(true);
$sheet->setCellValue('D10', $customer ? (string)$customer->gsm : '');
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getStyle('D10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$sheet->setCellValue('B11', 'Eposta :')->getStyle('B11')->getFont()->setBold(true);
$sheet->setCellValue('D11', $customer ? (string)$customer->email : '');

$sheet->setCellValue('B12', 'İlgili :')->getStyle('B12')->getFont()->setBold(true);
$sheet->setCellValue('D12', (string)($offer->yetkili ?? ''));

$sheet->setCellValue('N9', 'Teklif No :')->getStyle('N9')->getFont()->setBold(true);
$sheet->setCellValue('O9', (string)($offer->offerNumber ?? ''));

$sheet->setCellValue('N10', 'Tarih :')->getStyle('N10')->getFont()->setBold(true);
$sheet->setCellValue('O10', (string)($offer->offer_date ?? ''));

$sheet->setCellValue('N11', 'Referans :')->getStyle('N11')->getFont()->setBold(true);
$sheet->setCellValue('O11', (string)($offer->authors ?? ''));

$sheet->setCellValue('N12', 'Teklif Konusu :')->getStyle('N12')->getFont()->setBold(true);
$sheet->setCellValue('O12', (string)($offer->offer_subject ?? ''));
$sheet->getStyle('O12')->getAlignment()->setWrapText(true);

// Teklif üst açıklama
$sheet->setCellValue('B14', (string)($offer->offer_header_content ?? ''));
$sheet->getStyle('B14')->getAlignment()->setWrapText(true);
$sheet->getRowDimension('14')->setRowHeight(50);
$sheet->mergeCells('B14:O14');

// Ürünler tablosunu oluştur
$sheet->setCellValue('B17', 'NO');
$sheet->setCellValue('C17', 'ÜRÜN/HİZMET AÇIKLAMA');
$sheet->mergeCells('C17:L17');

$sheet->setCellValue('M17', 'MİKTAR');
$sheet->setCellValue('N17', 'BİRİM FİYAT');
$sheet->setCellValue('O17', 'TUTAR');

$sheet->getStyle('B17:O17')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
$sheet->getStyle('B17:O17')->getFont()->setBold(true);

// Verileri ekleyin
$row = 18;
$itemIndex = 1;
foreach ($offerProducts as $product) {
    $sheet->getStyle('B' . $row . ':O' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $sheet->setCellValue('B' . $row, $itemIndex);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('C' . $row, (string)($product->title ?? ''));
    $sheet->mergeCells('C' . $row . ':L' . $row);

    $sheet->setCellValue('M' . $row, ($product->amount ?? '') . ' ' . ($product->unit ?? ''));
    $sheet->setCellValue('N' . $row, number_format((float)($product->saleprice ?? 0), 2, ',', '.') . ' ' . ($product->salecur ?? ''));
    $sheet->setCellValue('O' . $row, number_format((float)($product->total_price ?? 0), 2, ',', '.') . ' ' . ($product->salecur ?? ''));
    $row++;
    $itemIndex++;
}

$sheet->getStyle('M:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getColumnDimension('M')->setWidth(15);
$sheet->getColumnDimension('N')->setWidth(15);
$sheet->getColumnDimension('O')->setWidth(15);

// Toplam tablosu
$currRow = $row + 1;
$sheet->setCellValue('I' . $currRow, 'PARA BİRİMİ')
    ->getStyle('I' . $currRow)->getFont()->setBold(true);
$sheet->setCellValue('K' . $currRow, 'TL')
    ->getStyle('K' . $currRow)->getFont()->setBold(true);
$sheet->setCellValue('M' . $currRow, 'EURO')
    ->getStyle('M' . $currRow)->getFont()->setBold(true);
$sheet->setCellValue('O' . $currRow, 'DOLAR')
    ->getStyle('O' . $currRow)->getFont()->setBold(true);

$sheet->getStyle('I' . $currRow . ':O' . $currRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

$currRow++;
$sheet->setCellValue('I' . $currRow, 'ARA TOPLAM');
$sheet->setCellValue('K' . $currRow, tlFormat($offer->tl_alt_toplam ?? 0));
$sheet->setCellValue('M' . $currRow, tlFormat($offer->euro_ara_toplam ?? 0));
$sheet->setCellValue('O' . $currRow, tlFormat($offer->dolar_ara_toplam ?? 0));

if (($offer->tl_iskonto ?? 0) > 0 || ($offer->euro_iskonto ?? 0) > 0 || ($offer->dolar_iskonto ?? 0) > 0) {
    $currRow++;
    $sheet->setCellValue('I' . $currRow, 'İSKONTO');
    $sheet->setCellValue('K' . $currRow, tlFormat($offer->tl_iskonto ?? 0));
    $sheet->setCellValue('M' . $currRow, tlFormat($offer->euro_iskonto ?? 0));
    $sheet->setCellValue('O' . $currRow, tlFormat($offer->dolar_iskonto ?? 0));
}

$currRow++;
$sheet->setCellValue('I' . $currRow, 'KDV 20%');
$sheet->setCellValue('K' . $currRow, tlFormat($offer->tl_kdv ?? 0));
$sheet->setCellValue('M' . $currRow, tlFormat($offer->euro_kdv ?? 0));
$sheet->setCellValue('O' . $currRow, tlFormat($offer->dolar_kdv ?? 0));

$currRow++;
$sheet->setCellValue('I' . $currRow, 'KDV DAHİL');
$sheet->setCellValue('K' . $currRow, tlFormat($offer->tl_kdvli_toplam ?? 0));
$sheet->setCellValue('M' . $currRow, tlFormat($offer->euro_kdvli_toplam ?? 0));
$sheet->setCellValue('O' . $currRow, tlFormat($offer->dolar_kdvli_toplam ?? 0));

$currRow++;
$sheet->setCellValue('I' . $currRow, 'GENEL TOPLAM');
$sheet->setCellValue('J' . $currRow, tlFormat($offer->tl_toplam_karsilik ?? 0) . ' TRY');

$sheet->mergeCells('J' . $currRow . ':O' . $currRow)
    ->getStyle('J' . $currRow)->getFont()->setBold(true);
$sheet->getStyle('I' . $currRow . ':O' . $currRow)
    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

$sheet->getStyle('J' . $currRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// I kolonunu bold yap ve sağa yasla
$sheet->getStyle('I' . ($row + 1) . ':I' . $currRow)->getFont()->setBold(true);
$sheet->getStyle('I' . ($row + 1) . ':I' . $currRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getColumnDimension('I')->setAutoSize(true);

// Alt Açıklama
$plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '&nbsp;'], ["\n", "\n", "\n", ' '], (string)($offer->offer_footer_content ?? '')));
$footerRow = $currRow + 3;
$sheet->setCellValue('B' . $footerRow, $plainText);
$sheet->getStyle('B' . $footerRow)->getAlignment()->setWrapText(true);
$sheet->mergeCells('B' . $footerRow . ':O' . $footerRow);
$sheet->getRowDimension($footerRow)->setRowHeight(112);

// Oluşturan & İmza
$signRow = $footerRow + 3;
$sheet->setCellValue('E' . $signRow, 'Oluşturan')
      ->getStyle('E' . $signRow)->getFont()->setBold(true);
$sheet->setCellValue('E' . ($signRow + 1), (string)($creator->username ?? ''));
$sheet->getStyle('E' . ($signRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->setCellValue('E' . ($signRow + 2), (string)($creator->Unvan ?? ''));
$sheet->getStyle('E' . ($signRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('M' . $signRow, 'Firma Kaşe İmza');

// Yazdırma ayarları
$sheet->getPageSetup()->setFitToPage(true);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageMargins()->setTop(0.64);
$sheet->getPageMargins()->setRight(0.64);
$sheet->getPageMargins()->setLeft(0.64);
$sheet->getPageMargins()->setBottom(0.64);

// Resim ekle
$logoPath = dirname(__DIR__, 3) . '/src/images/logo.png';
if (!is_file($logoPath)) {
    $logoPath = dirname(__DIR__, 3) . '/files/46_logo.png';
}
if (is_file($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Şirket Logosu');
    $drawing->setPath($logoPath);
    $drawing->setCoordinates('B2');
    $drawing->setWidth(180);
    $drawing->setHeight(60);
    $drawing->setWorksheet($sheet);
}

audit_log('export', 'offers', 'Teklif Excel olarak dışa aktarıldı: ' . ($offer->offerNumber ?? ('ID: ' . $id)), 'offers', (string)$id);

// Çıktı tamponunu temizle
if (ob_get_length()) {
    ob_end_clean();
}

$offerNumberClean = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($offer->offerNumber ?? 'Teklif'));
$filename = 'Teklif_' . $offerNumberClean . '_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;