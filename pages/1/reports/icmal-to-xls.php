<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['login']) || !permtrue('reportview')) {
    http_response_code(403);
    exit('Bu işlem için rapor görüntüleme ve dışa aktarma yetkiniz bulunmamaktadır.');
}

$customerId = (int)($_POST['cid'] ?? 0);
$reportIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['reports'] ?? [])))));

if ($customerId <= 0 || empty($reportIds)) {
    http_response_code(422);
    exit('Excel dosyası için en az bir rapor seçilmelidir.');
}

$customerStmt = $ac->prepare('SELECT * FROM customers WHERE id = ?');
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    http_response_code(404);
    exit('Firma kaydı bulunamadı.');
}

$placeholders = implode(',', array_fill(0, count($reportIds), '?'));
$reportStmt = $ac->prepare("SELECT 
        r.*,
        COALESCE(rt.reportName, 'Rapor') as report_name,
        COALESCE(rt.page_link, 'ysc') as page_link,
        u_co.username as controller_name
    FROM reports r
    LEFT JOIN report_types rt ON r.report_type = rt.id
    LEFT JOIN users u_co ON r.controller_id = u_co.id
    WHERE r.customer_id = ? AND r.id IN ($placeholders)
    ORDER BY r.id DESC");
$reportStmt->execute(array_merge([$customerId], $reportIds));
$reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($reports)) {
    http_response_code(404);
    exit('Dışa aktarılacak rapor bulunamadı.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rapor İcmali');
$sheet->getParent()->getDefaultStyle()->getFont()->setName('DejaVu Sans')->setSize(10);

// Sütun genişlikleri
$sheet->getColumnDimension('A')->setWidth(6);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(28);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(14);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(16);
$sheet->getColumnDimension('H')->setWidth(30);
$sheet->getColumnDimension('I')->setWidth(25);
$sheet->getColumnDimension('J')->setWidth(16);
$sheet->getColumnDimension('K')->setWidth(16);
$sheet->getColumnDimension('L')->setWidth(30);

// Başlık ve Logo
$logoPath = dirname(__DIR__, 3) . '/src/images/logo.png';
if (is_file($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('Aydınoğulları Logo');
    $drawing->setPath($logoPath);
    $drawing->setCoordinates('A1');
    $drawing->setWidth(160);
    $drawing->setWorksheet($sheet);
}

$sheet->mergeCells('E1:L1')->setCellValue('E1', mb_strtoupper((string)set('company_name'), 'UTF-8'));
$sheet->mergeCells('E2:L2')->setCellValue('E2', (string)set('company_address'));
$sheet->mergeCells('E3:L3')->setCellValue('E3', 'Tel: ' . set('company_phone1') . ' / ' . set('company_phone2'));
$sheet->mergeCells('E4:L4')->setCellValue('E4', set('admin_mail') . ' / ' . set('panel_url'));
$sheet->getStyle('E1:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('E1')->getFont()->setBold(true)->setSize(11);

// Belge Başlığı
$sheet->mergeCells('A6:L6')->setCellValue('A6', 'FİRMA KONTROL VE MUAYENE RAPORLARI İCMAL DÖKÜMÜ');
$sheet->getStyle('A6:L6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A6:L6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A6')->getFont()->setBold(true)->setSize(14);
$sheet->getRowDimension(6)->setRowHeight(26);

// Firma Meta Bilgileri
$meta = [
    8 => ['Firma :', $customer['company'] ?? '', 'İcmal Tarihi :', date('d.m.Y H:i')],
    9 => ['Telefon :', $customer['gsm'] ?? ($customer['phone'] ?? ''), 'Rapor Sayısı :', count($reports) . ' Adet'],
    10 => ['E-Posta :', $customer['email'] ?? '', 'Yetkili :', $customer['yetkili'] ?? ''],
    11 => ['Adres :', $customer['address'] ?? '', 'İl / İlçe :', ($customer['city'] ?? '') . ' / ' . ($customer['ilce'] ?? '')],
];
foreach ($meta as $row => $values) {
    $sheet->setCellValue("A{$row}", $values[0]);
    $sheet->setCellValue("B{$row}", $values[1]);
    $sheet->setCellValue("E{$row}", $values[2]);
    $sheet->setCellValue("F{$row}", $values[3]);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet->getStyle("E{$row}")->getFont()->setBold(true);
    $sheet->mergeCells("B{$row}:D{$row}");
    $sheet->mergeCells("F{$row}:L{$row}");
}

// Tablo Başlıkları
$tableHeaderRow = 13;
$headers = [
    'A' => '#',
    'B' => 'Rapor No',
    'C' => 'Rapor Türü',
    'D' => 'İş Emri No',
    'E' => 'Kontrol Tarihi',
    'F' => 'Geçerlilik Tarihi',
    'G' => 'Cihaz / Test No',
    'H' => 'Cihaz Cinsi / Açıklama',
    'I' => 'Bulunduğu Bölge / Kısım',
    'J' => 'Dolum / İmal Trh',
    'K' => 'Son Kull. Trh',
    'L' => 'Kontrol / Deney Sonucu'
];

foreach ($headers as $col => $title) {
    $sheet->setCellValue("{$col}{$tableHeaderRow}", $title);
}

$sheet->getStyle("A{$tableHeaderRow}:L{$tableHeaderRow}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A{$tableHeaderRow}:L{$tableHeaderRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
$sheet->getStyle("A{$tableHeaderRow}:L{$tableHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($tableHeaderRow)->setRowHeight(24);

$currentRow = $tableHeaderRow + 1;
$itemIndex = 1;

// Hazırlıklı sorgular
$stmtYsc = $ac->prepare("SELECT * FROM report_ysc_content WHERE report_id = ? ORDER BY id ASC");
$stmtHst = $ac->prepare("SELECT * FROM report_hst_content WHERE report_id = ? ORDER BY id ASC");
$stmtMet = $ac->prepare("SELECT * FROM report_met_content WHERE report_id = ? ORDER BY id ASC");
$stmtGen = $ac->prepare("SELECT * FROM report_contents WHERE report_id = ? ORDER BY id ASC");

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

    if (empty($items)) {
        // Kalemsiz rapor satırı
        $sheet->setCellValue("A{$currentRow}", $itemIndex);
        $sheet->setCellValue("B{$currentRow}", $rep['report_number'] ?? '-');
        $sheet->setCellValue("C{$currentRow}", $rep['report_name'] ?? '-');
        $sheet->setCellValue("D{$currentRow}", $rep['isemrino'] ?? '-');
        $sheet->setCellValue("E{$currentRow}", $rep['control_date'] ?? '-');
        $sheet->setCellValue("F{$currentRow}", $rep['validity_date'] ?? '-');
        $sheet->setCellValue("G{$currentRow}", '-');
        $sheet->setCellValue("H{$currentRow}", 'Ekipman/kalem kaydı bulunmamaktadır.');
        $sheet->setCellValue("I{$currentRow}", '-');
        $sheet->setCellValue("J{$currentRow}", '-');
        $sheet->setCellValue("K{$currentRow}", '-');
        $sheet->setCellValue("L{$currentRow}", 'Uygun');

        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
        $currentRow++;
        $itemIndex++;
    } else {
        foreach ($items as $item) {
            $cihazNo = '';
            $cinsi = '';
            $bolge = '';
            $dolumTrh = '';
            $sonKullTrh = '';
            $sonuc = 'Uygun';

            if ($pageLink === 'ysc' || $reportType === 1) {
                $cihazNo = $item['cihaz_no'] ?? '';
                $cinsi = $item['cinsi'] ?? '';
                $bolge = $item['bulundugu_bolge'] ?? '';
                $dolumTrh = $item['cihaz_dolum_tarihi'] ?? '';
                $sonKullTrh = $item['cihaz_sonkullanma_tarihi'] ?? '';
                $sonuc = '1.Knt:' . ($item['kontrol_tarihi_1'] ?? '-') . ' | 2.Knt:' . ($item['kontrol_tarihi_2'] ?? '-');
            } elseif ($pageLink === 'hst' || $reportType === 2) {
                $cihazNo = $item['testno'] ?? ($item['serino'] ?? '');
                $cinsi = ($item['cinsi'] ?? '') . (!empty($item['kg']) ? ' (' . $item['kg'] . ' KG)' : '');
                $bolge = $item['imalatci_firma'] ?? '';
                $dolumTrh = $item['imal_tarihi'] ?? '';
                $sonKullTrh = $item['serino'] ?? '';
                $sonuc = 'Sızdırmazlık: ' . ($item['sizdirmazlik_deneyi'] == '1' ? 'Geçti' : 'Kaldı') . ' | Esneme: ' . ($item['esneme_deneyi'] == '1' ? 'Geçti' : 'Kaldı');
            } elseif ($pageLink === 'met' || $reportType === 3) {
                $cihazNo = $item['id'] ?? '';
                $cinsi = $item['cinsi'] ?? '';
                $bolge = $item['bulundugu_kisim'] ?? '';
                $dolumTrh = $item['control_date_closet'] ?? '';
                $sonKullTrh = $item['next_control_date_closet'] ?? '';
                $sonuc = 'Basınç: ' . ($item['basinc_degeri'] ?? '-') . ' Bar';
            } else {
                $cihazNo = $item['id'] ?? '';
                $cinsi = $item['algilama_cinsi'] ?? 'Sistem Elemanı';
                $bolge = $item['bulundugu_bolge'] ?? '';
                $dolumTrh = '-';
                $sonKullTrh = '-';
                $sonuc = ($item['calisabilirlik_testi'] ?? 1) == 1 ? 'Çalışır Durumda' : 'Kusurlu';
            }

            $sheet->setCellValue("A{$currentRow}", $itemIndex);
            $sheet->setCellValue("B{$currentRow}", $rep['report_number'] ?? '-');
            $sheet->setCellValue("C{$currentRow}", $rep['report_name'] ?? '-');
            $sheet->setCellValue("D{$currentRow}", $rep['isemrino'] ?? '-');
            $sheet->setCellValue("E{$currentRow}", $rep['control_date'] ?? '-');
            $sheet->setCellValue("F{$currentRow}", $rep['validity_date'] ?? '-');
            $sheet->setCellValue("G{$currentRow}", $cihazNo ?: '-');
            $sheet->setCellValue("H{$currentRow}", $cinsi ?: '-');
            $sheet->setCellValue("I{$currentRow}", $bolge ?: '-');
            $sheet->setCellValue("J{$currentRow}", $dolumTrh ?: '-');
            $sheet->setCellValue("K{$currentRow}", $sonKullTrh ?: '-');
            $sheet->setCellValue("L{$currentRow}", $sonuc ?: 'Uygun');

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            
            $currentRow++;
            $itemIndex++;
        }
    }
}

// Dip Toplam Satırı
$sheet->mergeCells("A{$currentRow}:F{$currentRow}")->setCellValue("A{$currentRow}", "TOPLAM:");
$sheet->setCellValue("G{$currentRow}", ($itemIndex - 1) . " Ekipman / Kalem");
$sheet->mergeCells("H{$currentRow}:L{$currentRow}")->setCellValue("H{$currentRow}", count($reports) . " Rapor Seçildi");
$sheet->getStyle("A{$currentRow}:L{$currentRow}")->getFont()->setBold(true);
$sheet->getStyle("A{$currentRow}:L{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
$sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension($currentRow)->setRowHeight(22);

// Dosya indirme başlıkları
$filename = 'Firma_Rapor_Icmali_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $customer['company'] ?? 'Musteri') . '_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
