<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['login']) || !permtrue('data_export_offers')) {
    http_response_code(403);
    exit('Bu işlem için Excel dışa aktarma yetkiniz bulunmamaktadır.');
}

$customerId = (int)($_POST['cid'] ?? 0);
$offerIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['offers'] ?? [])))));

if ($customerId <= 0 || !$offerIds) {
    http_response_code(422);
    exit('Excel dosyası için en az bir teklif seçilmelidir.');
}

$customerStmt = $ac->prepare('SELECT * FROM customers WHERE id = ?');
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    exit('Firma kaydı bulunamadı.');
}

$placeholders = implode(',', array_fill(0, count($offerIds), '?'));
$offerStmt = $ac->prepare("SELECT * FROM offers WHERE cid = ? AND id IN ($placeholders) ORDER BY offer_date ASC, id ASC");
$offerStmt->execute(array_merge([$customerId], $offerIds));
$offers = $offerStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$offers) {
    http_response_code(404);
    exit('Dışa aktarılacak teklif bulunamadı.');
}

function icmalPlainText($html)
{
    $text = preg_replace('/<br\s*\/?\s*>/i', "\n", (string)$html);
    $text = preg_replace('/<\/p\s*>/i', "\n", $text);
    return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

$headerText = icmalPlainText($_POST['header_content'] ?? '');
$footerText = icmalPlainText($_POST['footer_content'] ?? '');
$headerText = $headerText ?: 'Sayın talep etmiş olduğunuz ürün/hizmetlere ilişkin fiyat teklifimiz aşağıda bilgilerinize sunulmuştur. Fiyatlarımızın makul gelmesini umut eder, iyi çalışmalar dileriz.';
$footerText = $footerText ?: "1. Fiyatlarımıza KDV dahildir.\n2. Ödeme Vadesi: Belirlenen ödeme planına göredir.\n3. Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Teklif İcmali');
$sheet->getParent()->getDefaultStyle()->getFont()->setName('DejaVu Sans')->setSize(10);

$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(55);
$sheet->getColumnDimension('C')->setWidth(13);
$sheet->getColumnDimension('D')->setWidth(22);
$sheet->getColumnDimension('E')->setWidth(22);

$logoPath = dirname(__DIR__, 3) . '/src/images/logo.png';
if (is_file($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('Aydınoğulları Logo');
    $drawing->setPath($logoPath);
    $drawing->setCoordinates('A1');
    $drawing->setWidth(180);
    $drawing->setWorksheet($sheet);
}

$sheet->mergeCells('C1:E1')->setCellValue('C1', mb_strtoupper((string)set('company_name'), 'UTF-8'));
$sheet->mergeCells('C2:E2')->setCellValue('C2', (string)set('company_address'));
$sheet->mergeCells('C3:E3')->setCellValue('C3', 'Tel: ' . set('company_phone1') . ' / ' . set('company_phone2'));
$sheet->mergeCells('C4:E4')->setCellValue('C4', set('admin_mail') . ' / ' . set('panel_url'));
$sheet->getStyle('C1:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('C1')->getFont()->setBold(true);

$sheet->mergeCells('A6:E6')->setCellValue('A6', 'FİYAT TEKLİF FORMU');
$sheet->getStyle('A6:E6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A6:E6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A6')->getFont()->setBold(true)->setSize(16);
$sheet->getRowDimension(6)->setRowHeight(28);

$meta = [
    8 => ['Firma :', $customer['company'] ?? '', 'Teklif No :', 'İCMAL DOSYASI'],
    9 => ['Telefon :', $customer['gsm'] ?? ($customer['phone'] ?? ''), 'Tarih :', date('d.m.Y')],
    10 => ['E Posta :', $customer['email'] ?? '', 'Referans :', 'İCMAL DOSYASI'],
    11 => ['İlgili :', $customer['yetkili'] ?? '', 'Teklif Konusu :', ($customer['company'] ?? '') . ' - İCMAL DOSYASI'],
];
foreach ($meta as $row => $values) {
    $sheet->setCellValue("A{$row}", $values[0]);
    $sheet->setCellValue("B{$row}", $values[1]);
    $sheet->setCellValue("C{$row}", $values[2]);
    $sheet->mergeCells("D{$row}:E{$row}")->setCellValue("D{$row}", $values[3]);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->getStyle("C{$row}")->getFont()->setBold(true);
    $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getRowDimension($row)->setRowHeight(20);
}

$sheet->mergeCells('A14:E14')->setCellValue('A14', $headerText);
$sheet->getStyle('A14')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(14)->setRowHeight(55);

$tableRow = 17;
$headers = ['NO', 'ÜRÜN / HİZMET AÇIKLAMASI', 'MİKTAR', 'BİRİM FİYAT', 'TUTAR'];
foreach ($headers as $index => $label) {
    $column = chr(ord('A') + $index);
    $sheet->setCellValue($column . $tableRow, $label);
}
$sheet->getStyle("A{$tableRow}:E{$tableRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFBBBBBB');
$sheet->getStyle("A{$tableRow}:E{$tableRow}")->getFont()->setBold(true);
$sheet->getStyle("C{$tableRow}:E{$tableRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$row = $tableRow + 1;
$subTotal = 0.0;
$taxTotal = 0.0;
$grandTotal = 0.0;
foreach ($offers as $index => $offer) {
    $amount = (float)($offer['tl_toplam_karsilik'] ?? ($offer['total_price'] ?? 0));
    $taxRate = (float)($offer['Kdv'] ?? ($offer['kdv'] ?? 20));
    $taxRate = $taxRate > 0 ? $taxRate : 20;
    $itemSubTotal = $amount / (1 + ($taxRate / 100));
    $subTotal += $itemSubTotal;
    $taxTotal += $amount - $itemSubTotal;
    $grandTotal += $amount;

    $title = trim((string)($offer['offer_subject'] ?? '')) ?: 'TEKLİF ' . ($offer['offerNumber'] ?? '');
    $sheet->setCellValue("A{$row}", $index + 1);
    $sheet->setCellValue("B{$row}", mb_strtoupper($title, 'UTF-8'));
    $sheet->setCellValue("C{$row}", '1 SET');
    $sheet->setCellValue("D{$row}", $amount);
    $sheet->setCellValue("E{$row}", $amount);
    $sheet->getStyle("D{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00 "TRY"');
    $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getRowDimension($row)->setRowHeight(30);
    $row++;
}

$totalStart = $row + 2;
$totals = [
    ['ARA TOPLAM', $subTotal, false],
    ['KDV %20', $taxTotal, false],
    ['KDV DAHİL', $grandTotal, false],
    ['GENEL TOPLAM', $grandTotal, true],
];
foreach ($totals as $offset => [$label, $value, $isGrand]) {
    $totalRow = $totalStart + $offset;
    $sheet->setCellValue("D{$totalRow}", $label);
    $sheet->setCellValue("E{$totalRow}", $value);
    $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("E{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00 "TRY"');
    $sheet->getRowDimension($totalRow)->setRowHeight(22);
    if ($isGrand) {
        $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFBBBBBB');
        $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getFont()->setBold(true);
    }
}

$footerRow = $totalStart + 6;
$sheet->mergeCells("A{$footerRow}:E{$footerRow}")->setCellValue("A{$footerRow}", $footerText);
$sheet->getStyle("A{$footerRow}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
$sheet->getRowDimension($footerRow)->setRowHeight(70);

$signatureRow = $footerRow + 3;
$sheet->mergeCells("A{$signatureRow}:B{$signatureRow}")->setCellValue("A{$signatureRow}", 'Oluşturan');
$sheet->mergeCells("D{$signatureRow}:E{$signatureRow}")->setCellValue("D{$signatureRow}", 'Sipariş Onayı');
$sheet->mergeCells('A' . ($signatureRow + 1) . ':B' . ($signatureRow + 1))->setCellValue('A' . ($signatureRow + 1), $_SESSION['name'] ?? 'Yetkili');
$sheet->mergeCells('D' . ($signatureRow + 1) . ':E' . ($signatureRow + 1))->setCellValue('D' . ($signatureRow + 1), 'Firma Kaşesi / İmza');
$sheet->getStyle("A{$signatureRow}:E" . ($signatureRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$signatureRow}:E{$signatureRow}")->getFont()->setBold(true);

$sheet->freezePane('A18');
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
$sheet->getPageSetup()->setPrintArea("A1:E" . ($signatureRow + 2));
$sheet->getPageMargins()->setTop(0.55)->setRight(0.45)->setBottom(0.55)->setLeft(0.45);
$sheet->setShowGridlines(false);

$safeCompany = preg_replace('/[^\pL\pN_-]+/u', '_', (string)($customer['company'] ?? 'firma'));
$filename = 'teklif_icmali_' . trim($safeCompany, '_') . '_' . date('Y-m-d') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
