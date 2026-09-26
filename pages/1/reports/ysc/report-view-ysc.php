<?php

require 'vendor/autoload.php';

use Dompdf\Dompdf;
use App\Helper\Date;

if (!function_exists('toBase64')) {
    function toBase64($image)
    {
        if (empty($image) || !file_exists($image)) {
            return '';
        }
        $data = base64_encode(file_get_contents($image));
        $mime = @mime_content_type($image) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . $data;
    }
}


$id = $_GET['id'];

// Rapor Bilgileri
$query = $ac->prepare('SELECT * from reports where id = ?');
$query->execute(array($id));
$report = $query->fetch(PDO::FETCH_ASSOC);

// Firma Bilgileri
$custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
$custquery->execute(array($report['customer_id']));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Kontrol Eden Bilgileri
$userquery = $ac->prepare('SELECT * FROM users WHERE id = ?');
$userquery->execute(array($report['controller_id']));
$controller = $userquery->fetch(PDO::FETCH_ASSOC);

// Firma Yetkilisi Bilgileri
$userquery->execute(array($report['company_official']));
$company_offical = $userquery->fetch(PDO::FETCH_ASSOC);

$document = ($customer['company'] ?? '') . '-' . ($report['report_number'] ?? '');






$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $document . '</title>
</head>
<style>
    body {
        font-family: Dejavu sans;
        margin: 0;
        padding: auto;
        font-weigth: 600;
        font-size: 11px;
    }

    @page {
        margin: 25px;
        margin-bottom: 35px;
        padding: auto;
        font-size: 8px !important;
    }

    @page {
        size: landscape !important;
    }
     @page:last footer {
        background-color: #d9d9d9;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        max-width: 790px;

    }

    td {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    table,
    th,
    td {
        border: 0.5px solid #808080;
    }

    .form-head {
        font-size: 9px !important;
        text-align: center;
    }

    .border {
        border: 1px solid #ccc !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-white {
        color: #fff;
    }
    .text-nowrap{
        white-space: nowrap;
    }

    .bg-gray {
        background-color: #808080;
    }
    
    .bg-light-gray {
        background-color: #eee;
    }
    
    .bg-light-red {
        background-color: #F5E8C7;
    }

    .m-0 {
        margin: 0 auto;
    }
        .p-0 {
        padding: 0;
    }

      .header {
                position: fixed;
                top: -60px;
                left: 0px;
                right: 0px;
                background-color: #d9d9d9;
   
            }
            footer {
                position: fixed; 
                bottom: -15px; 
                left: 0px; 
                right: 0px;
                height: 50px; 
            }
            footer .img-left {
                position: absolute;
                left: 0;
                width: 100px;
                }
            
                footer .img-right {
                position: absolute;
                right: 0;
                width: 100px;
                }
          

            </style>

<body>
   <footer >
        <div class="footer-img img-left">';

if (!empty($controller['imza_file'])) {
    $html .= '<img  src="' . toBase64('files/imzalar/' . $controller['imza_file']) . '" width="100px" id="logo" alt="">';
}

$html .= '  </div>
        <div class="footer-img img-right">';
if (!empty($company_offical['imza_file'])) {
    $html .= '<img  src="' . toBase64('files/imzalar/' . $company_offical['imza_file']) . '" width="100px" id="logo" alt="">';
}
$html .= ' </div>
    </footer> 
    <table>

        <tbody>
            <tr>
                <td colspan="12"> 
                <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td colspan="24" class="form-head">
                    <strong>YANGIN SÖNDÜRME TÜPLERİ KONTROL RAPORU</strong>
                    <p>
                        TS ISO 11602-2 STANDARTLARINA UYGUN
                    </p>
                </td>
                <td colspan="12">
                   
                </td>
            </tr>


            <tr>
                <td colspan="3">İş Emri no</td>
                <td colspan="9" style="max-width:80px; height:30px" class="bg-light-gray text-center">
                    <div>
                        ' . $report['isemrino'] . '
                    </div>
                </td>   

                <td colspan="4">Rapor No</td>
                <td colspan="4" class="bg-light-gray text-center">' . $report['report_number'] . '</td>
                
                <td colspan="4">Kontrol Tarihi</td>
                <td colspan="4" class="bg-light-gray text-center">' . Date::dmY($report['control_date']) . '</td>
                <td colspan="5">Kontrol Periyodu</td>
                <td colspan="3" class="bg-light-gray text-center">' .  Date::dmY($report['control_period']) . '</td>

                <td colspan="6">Geçerlilik Tarihi</td>
                <td colspan="6" class="bg-light-gray text-center">' . Date::dmY($report['validity_date']) . '</td>
            </tr>

            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    GENEL BİLGİLER / GENERAL INFORMATION
                </td>
            </tr>

            <tr>
                <td colspan="12">Firma Bilgileri</td>
                <td colspan="36">' . $customer['company'] . '</td>
            </tr>

            <tr>
                <td colspan="12">Adres Bilgileri</td>
                <td colspan="36">' . $customer['address'] . '</td>
            </tr>


            <tr>
                <td colspan="12">Telefon Bilgileri</td>
                <td colspan="12">' . $customer['gsm'] . '</td>

                <td colspan="12">E-Mail Adresi</td>
                <td colspan="12">' . $customer['email'] . '</td>
            </tr>

            <tr>
                <td colspan="12">Fax</td>
                <td colspan="12">' . $customer['fax'] . '</td>

                <td colspan="12">Web</td>
                <td colspan="12">' . $customer['web'] . '</td>
            </tr>

            <tr>
                <td colspan="12">İlgili Standartlar</td>
                <td colspan="36">
                ' . $report['standarts'] . '
                </td>
            </tr>

            <tr>
                <td colspan="12">Test Sırasında Kullanılan Ekipmanlar </td>
                <td colspan="36">
                ' . $report['equipments'] . '
                </td>
            </tr>
</table>
<table  width="100%" page-break-inside: auto;>
    <thead class="header">
            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    GENEL KONTROLLER
                </td>
            </tr>

            <tr class="text-center" style="font-size:9px">
                <td colspan="2" style="max-width:57px">CİHAZ NO</td>
                <td colspan="5" style="max-width:57px">BULUNDUĞU BÖLGE</td>
                <td colspan="3" style="max-width:57px">CİHAZ CİNSİ</td>

                <td colspan="5" style="max-width:57px">CİHAZ KULLANIM TARİHLERİ</td>
                <td colspan="4" style="max-width:57px">KONTROL TARİHLERİ</td>
                <td colspan="5" style="max-width:57px;font-size:9px">KONTROLLERDE YAPILAN İŞLEMLER</td>
              
               <td colspan="3" rowspan="2" style="max-width:57px"> DIŞ MUHAFAZA KONTROLÜ -
                    <p>
                        RENK KONTROLÜ
                    </p>
                </td>
                <td colspan="3" rowspan="2" style="max-width:57px">ÇEVRE KONTROLÜ</td>
                <td colspan="3"  rowspan="2" style="max-width:57px">PİM -MÜHÜR KONTROLÜ</td>
                <td colspan="3"  rowspan="2" style="max-width:57px">MANOMETRE KONTROLÜ</td>


                <td colspan="4"  rowspan="2" style="max-width:57px">HORTUM -NOZUL KONTROL</td>
                <td colspan="4"  rowspan="2" style="max-width:57px">TALİMAT KONTROLÜ</td>
                <td colspan="4"  rowspan="2" style="max-width:57px;font-size:6px">AĞIRLIK KONTROLÜ MANOMETRE KULLANILMAYANLARDA</td>

            </tr>

            <tr class="text-center" style="font-size:9px">
                <td colspan="2"></td>
                <td colspan="5"></td>
                <td colspan="3"></td>
                <td colspan="2">DOLUM TARİHİ</td>
                <td colspan="3" >SON KULLANMA TARİHİ</td>

                <td colspan="2">1. KONTROL TARİHİ</td>
                <td colspan="2">2. KONTROL TARİHİ</td>
              
                <td colspan="2">1. KONTROL TARİHİ</td>
                <td colspan="3">2. KONTROL TARİHİ</td>

            </tr>
    </thead>
            ';

$query = $ac->prepare('SELECT * FROM report_ysc_content where report_id = ?');
$query->execute(array($report['id']));

// Sütun başlıklarını alır
$fieldquery = $ac->prepare('SHOW COLUMNS FROM report_ysc_content');
$fieldquery->execute();
$columns = $fieldquery->fetchAll(PDO::FETCH_ASSOC);

while ($content = $query->fetch(PDO::FETCH_ASSOC)) {
    $html .= '<tr class="text-center" style="font-size:9px">
                    <!--Cihaz No -->
                    <td colspan="2">
                        <div style="padding:10px"> '
        . $content['cihaz_no']
        . '</div>
                    </td>

                    <!--Bulundugu Bolge -->
                        <td colspan="5"> ' . $content['bulundugu_bolge'] . '</td>
                    
                        <!--Cinsi -->
                        <td colspan="3"> ' . $content['cinsi'] . '</td>

                    <!--Cihaz Dolum Tarihi-->
                        <td colspan="2" class="text-nowrap"  >' . $content['cihaz_dolum_tarihi'] . '</td>
                        <td colspan="3" >' . $content['cihaz_sonkullanma_tarihi'] . '</td>
    

                    <!--Kontrol Tarihi-->
                        <td colspan="2" >' . $content['kontrol_tarihi_1'] . '</td>
                        <td colspan="2" >' . $content['kontrol_tarihi_2'] . '</td>
                    
                    <!--Kontrol Tarihi-->
                        <td colspan="2" >' . $content['islem_kontrol_tarihi_1'] . '</td>
                        <td colspan="3">' . $content['islem_kontrol_tarihi_2'] . '</td>';

    // UYGUNLUK KONTORLÜ YAPILAN ALANLARDA FOREACH İLE DÖNEREK KONTROL YAPILIR
    // 8.KOLONDAN İTİBAREN UYGUNLUK KONTROLÜ YAPILMAKTADIR
    $colspan = 3;
    foreach ($columns as $index => $column) {
        if ($index >= 11) {
            if ($index >= 15) {
                $colspan = 4;
            }
            $html .= '<td colspan="' . $colspan . '" style="max-width:28px;">' . ($content[$column['Field']] == '1' ? 'UYGUN' : 'UYGUN DEĞİL') . '</td>
                                                ';
        }
    }

    $html .= '</tr>';
}
;

$html .= '
           <tr>
                <td colspan="10">İkaz ve Uyarılar</td>
                <td colspan="38">
                ' . $report['warnings'] . '
                </td>
            </tr>

            <tr>
                <td colspan="10">Sonuç ve Kanaat</td>
                <td colspan="38">
                ' . $report['notes'] . '
                </td>
            </tr>

            <tr>

            <td colspan="48">
                ' . $report['subNotes'] . '

            </td>
        
        </tr>';

if ($_GET['sign'] != 'no') {
    $html .= '  <tr page-break-inside: auto;>
                <td colspan="24" class="text-center"><strong>Kontrolü Yapan</strong></td>
                <td colspan="24" class="text-center"><strong>Firma Yetkilisi</strong></td>
            </tr>
            <tr>
                <td colspan="4">Adı Soyadı</td>
                <td colspan="10"><strong>' . $controller['username'] . '</strong></td>
                <td rowspan="3" colspan="10"> <div class="text-center" style="height:150px;vertical-align:center">';
    if (!empty($controller['imza_file'])):
        $html .= '<img src="' . toBase64('files/imzalar/' . $controller['imza_file']) . '" width="180px" id="logo" alt="">';
    endif;
    $html .= '
                    </div></td>

                <td colspan="4">Adı Soyadı</td>
                <td colspan="10"><strong>' . $company_offical['username'] . '</strong></td>
                <td rowspan="3" colspan="10"><div class="text-center" style="height:150px;vertical-align:center">';
    if (!empty($company_offical['imza_file'])):
        $html .= '<img src="' . toBase64('files/imzalar/' . $company_offical['imza_file']) . '" width="180px" id="logo" alt="">';
    endif;
    $html .= '
                    </div></strong></td>
              
              

            </tr>
            <tr>
                <td colspan="4">Mesleği - Ekipnet Numarası</td>
                <td colspan="10">' . $controller['meslek'] . ' - ' . $controller['ekipnetno'] . '</td>

                <td colspan="4">Mesleği</td>
                <td colspan="10">' . $company_offical['meslek'] . '</td>
                 
               
            </tr>


            <tr>

                <td colspan="4">Oda Sicil No - Yetkinlik No</td>
                <td colspan="10">' . $controller['odasicilno'] . ' - ' . $controller['yetkinlikno'] . '</td>

                <td colspan="4"></td>
                <td colspan="10"></td>

            </tr>

            <tr>

                <td colspan="24">
                   
                </td>
                <td colspan="24">
                    
                </td>
               

            </tr>';
}
;

$html .= '        </tbody>
    ';

//$html .= $columnName;

$html .= '</body>

</html>';

// instantiate and use the dompdf class
$options = new \Dompdf\Options();
$options->set('isPhpEnabled', true);  // PHP kodlarının çalıştırılmasını etkinleştir
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);

// PDF oluştur
$dompdf->render();

if (empty($_GET['send-mail']) || $_GET['send-mail'] !== 'true') {
    if (ob_get_length()) {
        ob_end_clean();
    }
    // add pagination
    $canvas = $dompdf->getCanvas();  // get the canvas
    $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $text = "Sayfa $pageNumber / $pageCount";
        $font = $fontMetrics->getFont("Helvetica", "normal");
        $size = 8;
        $width = $fontMetrics->getTextWidth($text, $font, $size);
        $canvas->text(400, 580, $text, $font, $size);
    });
    $dompdf->stream($document . '.pdf', array('Attachment' => false));
} else {
    $canvas = $dompdf->getCanvas();  // get the canvas
    // add the page number and total number of pages
    $canvas->page_script('
        $text = "$PAGE_NUM / $PAGE_COUNT";
        $pdf->text(535, 791.89, $text, \'Helvetica\', 8, array(0,0,0));
    ');

    $pdf_content = $dompdf->output();

    send_pdf_email_attachment(
        $pdf_content,
        $document . '.pdf',
        'index.php?p=report-send-as-mail&id=' . urlencode((string)$id) . '&type=ysc&st=success-mail',
        'index.php?p=report-send-as-mail&id=' . urlencode((string)$id) . '&type=ysc&st=unsuccessful',
        'Yangın Söndürme Tüpü Kontrol Raporu - ' . ($report['report_number'] ?? ''),
        'Yangın söndürme tüpü kontrol raporunuz ekte sunulmuştur.'
    );
}

