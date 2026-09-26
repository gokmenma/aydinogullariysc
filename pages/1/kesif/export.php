<?php
// require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Model/KesifModel.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Helper/date.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';


require_once dirname(__DIR__, 3) . '/bootstrap.php';


use App\Helper\Date;
use App\Helper\Helper;
use App\Model\KesifModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$data = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Yetki kontrolü
if (!permtrue('kesifExport')) {
    http_response_code(403);
    die('Bu işlem için yetkiniz bulunmamaktadır.');
}
// Helper::dd($data);

try {
    // Logger başlat
    $logger = \getLogger("kesif");
    
    // Kesifleri getir
    $kesifObj = new KesifModel();
    $kesifler = $kesifObj->getAllActive();


    
    

    // Log kaydı - Excel export
    $logger->info('Keşif listesi Excel\'e aktarıldı', [
        'user_id' => $_SESSION['lid'] ?? 0,
        'username' => $_SESSION['username'] ?? 'Guest',
        'toplam_kayit' => count($kesifler),
        "aktarılan_kayitlar" => json_encode($kesifler,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ]);

    // Spreadsheet oluştur
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Keşifler');

    // Başlık satırı
    $headers = [
        'Sıra No',
        'Keşif Tarihi',
        'Keşife Gidecek Kişi',
        'Firma Adı',
        'Yapılacak İş',
        'Konum',
        'Durum',
        'Kayıt Tarihi',
        'Kayıt Yapan',
        'Keşif Sonu Notu'
    ];

    // Başlıkları yaz - A1'den başla
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    foreach ($headers as $colIndex => $header) {
        $cellRef = $columns[$colIndex] . '1';
        $sheet->setCellValue($cellRef, $header);
        
        // Başlık stilini ayarla
        $sheet->getStyle($cellRef)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        
        $sheet->getStyle($cellRef)->getFont()->setBold(true);
        $sheet->getStyle($cellRef)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cellRef)->getAlignment()->setWrapText(true);
    }

    // Veri satırları
    $row = 2;
    $sira = 1;
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    
    foreach ($kesifler as $kesif) {
        $durum = $kesif->durum ?? 'bekliyor';
        $durum_tr = match($durum) {
            'bekliyor' => 'Bekliyor',
            'iptal_edildi' => 'İptal Edildi',
            'teklif_gonderildi' => 'Teklif Gönderildi',
            default => $durum
        };
        
        $rowData = [
            $sira++,
            $kesif->kesif_tarihi,
            $kesif->gidecek_kisi ?? '-',
            $kesif->firma,
            $kesif->yapilacak_is,
            $kesif->konum,
            $durum_tr,
            $kesif->kayit_tarihi,
            $kesif->kullanici_adi ?? '-',
            $kesif->kesif_sonu_notu ?? '-'
        ];
        
        foreach ($rowData as $colIndex => $value) {
            $cellRef = $columns[$colIndex] . $row;
            $sheet->setCellValue($cellRef, (string)$value);
            
            // Hücre stilini ayarla
            $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle($cellRef)->getAlignment()->setWrapText(true);
            
            // Alternatif satır rengi
            if ($row % 2 == 0) {
                $sheet->getStyle($cellRef)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF2F2F2');
            }
        }
        
        $row++;
    }

    // Kolon genişliği
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(18);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(25);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(15);
    $sheet->getColumnDimension('J')->setWidth(20);

    // Dosya indir
    $filename = 'Kesifler_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    // Hata log kaydı
    $logger->error('Excel export hatası', [
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['lid'] ?? 0
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Excel export hatası: ' . $e->getMessage()]);
    exit;
}
