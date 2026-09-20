<?php
// Oturumun başlatılıp başlatılmadığını kontrol edin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['login'])) {
    header('Location: /authorize.php');
    exit;
}

// Çıktı tamponunu başlatın
ob_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';
require $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/App/Model/ReportControlModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Model\ReportControlModel;

$reportControlModel = new ReportControlModel();

$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';
$filling_list = $reportControlModel->getReportFillingList($month, $year);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Sıra No');
$sheet->setCellValue('B1', 'Firma Adı');
$sheet->setCellValue('C1', 'Rapor No');
$sheet->setCellValue('D1', 'Cihaz No');
$sheet->setCellValue('E1', 'Bulunduğu Bölge');
$sheet->setCellValue('F1', 'Ay');
$sheet->setCellValue('G1', 'Yıl');


// Verileri ekleyin
$rowNumber = 2;
foreach ($filling_list as $list) {
    $sheet->setCellValue('A' . $rowNumber, $rowNumber-1);
    $sheet->setCellValue('B' . $rowNumber, $list->firma_adi);
    $sheet->setCellValue('C' . $rowNumber, $list->report_number);
    $sheet->setCellValue('D' . $rowNumber, $list->cihaz_no);
    $sheet->setCellValue('E' . $rowNumber, $list->bulundugu_bolge);
    $sheet->setCellValue('F' . $rowNumber, $list->ay);
    $sheet->setCellValue('G' . $rowNumber, $list->yil);
    $rowNumber++;
}

// Çıktı tamponunu temizleyin
ob_end_clean();

// Dosya adını belirleyin
$final_filename = 'Dolum_Listesi.xlsx';

// Headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $final_filename . '"');
header('Cache-Control: max-age=0');

// Dosyayı oluşturun ve indirin
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;