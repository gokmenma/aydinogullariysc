<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("APP", $_SERVER['DOCUMENT_ROOT']);
require_once APP . '/configs/config.php';
require_once APP . '/configs/functions.php';
require APP . '/vendor/autoload.php';
require_once APP . "/App/Helper/security.php";
// require_once APP . "/App/Model/OfferModel.php";

use App\Helper\Security;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;



$id = Security::decrypt($_GET['id']);

//$Offer = new OfferModel();

// Teklif ve teklif ürünlerini al
$sql = $ac->prepare("SELECT * FROM offers WHERE id = ?");
$sql->execute([$id]);
$offer = $sql->fetch(PDO::FETCH_OBJ);

//Firma Bilgilerini getir
$sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$sql->execute([$offer->cid]);
$customer = $sql->fetch(PDO::FETCH_OBJ);


//Ürünleri Getir
$sql = $ac->prepare("SELECT * FROM offermatters WHERE oid = ?");
$sql->execute([$id]);
$offerProducts = $sql->fetchAll(PDO::FETCH_OBJ);


// OLUŞTURAN BİLGİLERİ
$crtquery = $ac->prepare('SELECT* FROM users WHERE id = ?');
$crtquery->execute(array($offer->creativer));
$creator = $crtquery->fetch(PDO::FETCH_OBJ);
// OLUŞTURAN BİLGİLERİ


// Yeni bir Spreadsheet nesnesi oluşturun
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlıkları ekleyin
$sheet->setCellValue('B7', 'FİYAT TEKLİF FORMU');
$sheet->mergeCells('B7:O7');
//altını ve üstünü çiz
$sheet->getStyle('B7:O7')->getBorders()->getTop()->setBorderStyle('thin');
$sheet->getStyle('B7:O7')->getBorders()->getBottom()->setBorderStyle('thin');
//yüksekliği ayarla
$sheet->getRowDimension('7')->setRowHeight(30);
$sheet->getStyle('B7')->getAlignment()->setHorizontal('center');
$sheet->getStyle('B7')->getFont()->setSize(20)->setBold(true);


$companyName = set('company_name');
$sheet->setCellValue('O2', mb_strtoupper($companyName, 'UTF-8'));
$sheet->getStyle('O2')->getFont()->setSize(14)->setBold(true);


$sheet->setCellValue('O3', set('company_address'));
$sheet->setCellValue('O4', set('company_phone1') . ' / ' . set('company_phone2'));
$sheet->setCellValue('O5', set('admin_mail') . ' / ' . set('panel_url'));

foreach (['O'] as $columnID) {
    //$sheet->getColumnDimension($columnID)->setAutoSize(true);
    $sheet->getStyle($columnID)->getAlignment()->setHorizontal('right');
}

$sheet->setCellValue('B9', 'Firma Adı :')->getStyle('B9')->getFont()->setBold(true);
$sheet->setCellValue('D9', $customer->company);

$sheet->setCellValue('B10', 'Telefon :')->getStyle('B10')->getFont()->setBold(true);
$sheet->setCellValue('D10', $customer->gsm);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getStyle('D10')->getAlignment()->setHorizontal('left');


$sheet->setCellValue('B11', 'Eposta :')->getStyle('B11')->getFont()->setBold(true);
$sheet->setCellValue('D11', $customer->email);

$sheet->setCellValue('B12', 'İlgili :')->getStyle('B12')->getFont()->setBold(true);
$sheet->setCellValue('D12', $offer->yetkili);


$sheet->setCellValue('N9', 'Teklif No :')->getStyle('N9')->getFont()->setBold(true);
$sheet->setCellValue('O9', $offer->offerNumber);

$sheet->setCellValue('N10', 'Tarih :')->getStyle('N10')->getFont()->setBold(true);
$sheet->setCellValue('O10', $offer->offer_date);

$sheet->setCellValue('N11', 'Referans :')->getStyle('N11')->getFont()->setBold(true);
$sheet->setCellValue('O11', $offer->authors);

$sheet->setCellValue('N12', 'Teklif Konusu :')->getStyle('N12')->getFont()->setBold(true);
$sheet->setCellValue('O12', $offer->offer_subject);
$sheet->getStyle('O12')->getAlignment()->setWrapText(true);


//Teklif üst açıklama
$sheet->setCellValue('B14', $offer->offer_header_content);
//Metni kaydır
$sheet->getStyle('B14')->getAlignment()->setWrapText(true);
//Satır yüksekliğini ayarla
$sheet->getRowDimension('14')->setRowHeight(50);
//Hücreleri birleştir
$sheet->mergeCells('B14:O14');


// Ürünler tablosunu oluştur
$sheet->setCellValue('B17', 'NO');
$sheet->setCellValue('C17', 'ÜRÜN/HİZMET AÇIKLAMA');

$sheet->mergeCells('C17:L17');

$sheet->setCellValue('M17', 'MİKTAR');
$sheet->setCellValue('N17', 'BİRİM FİYAT');
$sheet->setCellValue('O17', 'TUTAR');

//B'den O'ya kadar arka plan rengini değiştir ve bold yap
$sheet->getStyle('B17:O17')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
$sheet->getStyle('B17:O17')->getFont()->setBold(true);


// Verileri ekleyin
$row = 18;
foreach ($offerProducts as $product) {
    //B'den O'ya kadar altı çizili yap
    $sheet->getStyle('B' . $row . ':O' . $row)->getBorders()->getBottom()->setBorderStyle('thin');

    $sheet->setCellValue('B' . $row, $row - 17);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal('center');

    $sheet->setCellValue('C' . $row, $product->title);
    $sheet->mergeCells('C' . $row . ':L' . $row);

    $sheet->setCellValue('M' . $row, $product->amount . ' ' . $product->unit);
    $sheet->setCellValue('N' . $row, number_format($product->saleprice, 2, ',', '.') . ' ' . $product->salecur);
    $sheet->setCellValue('O' . $row, number_format($product->total_price, 2, ',', '.') . ' ' . $product->salecur);
    $row++;

}


$sheet->getStyle('M:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

//genişlikleri 15 yap
$sheet->getColumnDimension('M')->setWidth(15);
$sheet->getColumnDimension('N')->setWidth(15);
$sheet->getColumnDimension('O')->setWidth(15);




$sheet->setCellValue('I' . $row + 2, 'PARA BİRİMİ')
    ->getStyle('I' . $row + 2)->getFont()->setBold(true);


$sheet->setCellValue('K' . $row + 2, 'TL')
    ->getStyle('K' . $row + 2)->getFont()->setBold(true);

$sheet->setCellValue('M' . $row + 2, 'EURO')
    ->getStyle('M' . $row + 2)->getFont()->setBold(true);

$sheet->setCellValue('O' . $row + 2, 'DOLAR')
    ->getStyle('O' . $row + 2)->getFont()->setBold(true);




//H'den O'ya kadar arka plan rengini değiştir ve bold yap
$sheet->getStyle('I' . $row + 2 . ':O' . $row + 2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');


$sheet->setCellValue('I' . $row + 3, 'ARA TOPLAM');
$sheet->setCellValue('K' . $row + 3, tlFormat($offer->tl_alt_toplam));
$sheet->setCellValue('M' . $row + 3, tlFormat($offer->euro_ara_toplam));
$sheet->setCellValue('O' . $row + 3, tlFormat($offer->dolar_ara_toplam));

if ($offer->tl_iskonto > 0 || $offer->euro_iskonto > 0 || $offer->dolar_iskonto > 0) {
    $sheet->setCellValue('I' . $row + 4, 'İSKONTO');
    $sheet->setCellValue('K' . $row + 4, tlFormat($offer->tl_iskonto));
    $sheet->setCellValue('M' . $row + 4, tlFormat($offer->euro_iskonto));
    $sheet->setCellValue('O' . $row + 4, tlFormat($offer->dolar_iskonto));
    $row++;
}

$sheet->setCellValue('I' . $row + 4, 'KDV 20%');
$sheet->setCellValue('K' . $row + 4, tlFormat($offer->tl_kdv));
$sheet->setCellValue('M' . $row + 4, tlFormat($offer->euro_kdv));
$sheet->setCellValue('O' . $row + 4, tlFormat($offer->dolar_kdv));

$sheet->setCellValue('I' . $row + 5, 'KDV DAHİL');
$sheet->setCellValue('K' . $row + 5, tlFormat($offer->tl_kdvli_toplam));
$sheet->setCellValue('M' . $row + 5, tlFormat($offer->euro_kdvli_toplam));
$sheet->setCellValue('O' . $row + 5, tlFormat($offer->dolar_kdvli_toplam));

$sheet->setCellValue('I' . $row + 6, 'GENEL TOPLAM');
$sheet->setCellValue('J' . $row + 6, tlFormat($offer->tl_toplam_karsilik) . ' TRY');

$sheet->mergeCells('J' . $row + 6 . ':O' . $row + 6)
    ->getStyle('J' . $row + 6)->getFont()->setBold(true);
$sheet->getStyle('I' . $row + 6 . ':O' . $row + 6)
    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');

$sheet->getStyle('J' . $row + 6)->getAlignment()->setHorizontal('center');



//I KOLONUNU BOLD YAP VE SAĞA YASLA
$sheet->getStyle('I' . $row + 2 . ':I' . $row + 6)->getFont()->setBold(true);
$sheet->getStyle('I' . $row + 2 . ':I' . $row + 6)->getAlignment()->setHorizontal('right');
//I KOLONUNU auto size yap
$sheet->getColumnDimension('I')->setAutoSize(true);

// HTML içeriğini normal metin olarak yazdırmak için strip_tags ve str_replace kullanın
$plainText = strip_tags(str_replace(['<br>', '&nbsp;'], ["\n", ' '], $offer->offer_footer_content));
$sheet->setCellValue('B' . ($row + 9), $plainText);


// Hücredeki metni alt alta yazdırmak için wrap text özelliğini etkinleştirin
$sheet->getStyle('B' . ($row + 9))->getAlignment()->setWrapText(true);
$sheet->mergeCells('B' . ($row + 9) . ':O' . ($row + 9));
//yüksekliği otomatik ayarla
$sheet->getRowDimension($row + 9)->setRowHeight(112);


//Oluşturan
$sheet->setCellValue('E' . ($row + 12), 'Oluşturan')
      ->getStyle('E' . ($row + 12))->getFont()->setBold(true);
$sheet->setCellValue('E' . ($row + 13), $creator->username);
    $sheet->getStyle('E' . ($row + 13))->getAlignment()->setHorizontal('center');
$sheet->setCellValue('E' . ($row + 14), $creator->Unvan);
    $sheet->getStyle('E' . ($row + 14))->getAlignment()->setHorizontal('center');

//Firma Kaşe İmza
$sheet->setCellValue('M' . ($row + 12), 'Firma Kaşe İmza');


//Yazdırma ayarlarından tüm sütunları bir satıra sığacak şekilde ayarla
$sheet->getPageSetup()->setFitToPage(true);      // Sayfaya sığacak şekilde ayarla
$sheet->getPageSetup()->setFitToWidth(1);        // Tüm içeriği genişlik olarak 1 sayfaya sığdır
//$sheet->getPageSetup()->setFitToHeight(0);       // Yükseklik otomatik hesaplansın (0 = otomatik)
//$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE); // Yatay yazdırma

// Opsiyonel: Yazdırma alanını belirleme (örneğin A1:M50 aralığı)
// $sheet->getPageSetup()->setPrintArea('A1:M50');


// Kenar boşluklarını ayarlama (dar kenar boşlukları)
$sheet->getPageMargins()->setTop(0.64);
$sheet->getPageMargins()->setRight(0.64);
$sheet->getPageMargins()->setLeft(0.64);
$sheet->getPageMargins()->setBottom(0.64);


// Resim ekle
$drawing = new Drawing();
$drawing->setName('Logo');
$drawing->setDescription('Şirket Logosu');
$drawing->setPath(APP . '/files/46_logo.png'); // Resim dosyasının yolu
$drawing->setCoordinates('B2'); // Resmin konumlanacağı hücre
$drawing->setWidth(180); // Resmin genişliği (piksel)
$drawing->setHeight(60); // İsterseniz yüksekliği de ayarlayabilirsiniz
$drawing->setWorksheet($sheet);

// Çıktı tamponunu bir kez daha temizle
ob_end_clean();

// Dosyanın adını belirle
$filename = 'offer_data_' . date('Y-m-d') . '.xlsx';

// Başlıkları ayarla
// Başlıkları ayarla
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0'); 

// Xlsx writer kullan (daha modern format)
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;