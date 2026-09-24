<?php
require 'vendor/autoload.php';

function toBase64($image)
{

    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}


$id = $_GET["id"];

// Rapor Bilgileri
$query = $ac->prepare("SELECT * from reports where id = ?");
$query->execute(array($id));
$report = $query->fetch(PDO::FETCH_ASSOC);

$report_date=$report["create_time"] != null ? $report["create_time"] : $report["update_time"];

// Firma Bilgileri
$custquery = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$custquery->execute(array($report["customer_id"]));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Kontrol Eden Bilgileri
$userquery = $ac->prepare("SELECT * FROM users WHERE id = ?");
$userquery->execute(array($report["controller_id"]));
$user = $userquery->fetch(PDO::FETCH_ASSOC);

//Firma Yetkilisi Bilgileri
$userquery->execute(array($report["company_official"]));
$company_offical = $userquery->fetch(PDO::FETCH_ASSOC);

$document=$customer['company'] ."-" . $report['report_number'];




// İÇERİK BURAYA GELECEK
$html='<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.$document.'</title>
</head>
<style>
    body {
        font-family: dejavu sans;
        margin: 0;
        padding: auto;
    }

    @page {
        margin: 25px;
        padding: auto;
        font-size: 8px !important;
    }


    table {
        width: 100%;
        border-collapse: collapse;
        max-width: 790px;
        border: 1px solid;

    }

    td {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    tr td {
        border: 1px solid;
    }



    .form-head {
        font-size: 16px !important;
        text-align: center;
        border-bottom: 1px solid #808080;
    }

    .border {
        border: 1px solid #ccc !important;
    }


    .text-center {
        text-align: center !important;
    }

    .text-rigth {
        text-align: right !important;
    }

    .text-white {
        color: #fff;
    }

    .fs-16 {
        font-size: 16px;
    }

    .bg-gray {
        background-color: #808080;
    }

    .border-bottom {
        border-bottom: 0.1rem solid #000;
    }

    .border-top {
        border-top: 2px solid #808080;
    }

    .m-0 {
        margin: 0 auto;
    }

    p {
        margin: 0;
    }

    .brand {
        text-align: right;

    }

    .brand strong {
        display: block;
        line-height: 1.25;
        margin-bottom: 4px;
    }

    .header strong {
        /* border-bottom: 2px solid #808080;
        border-top: 2px solid #808080; */
        padding: 5px;
        font-size: 12px;
        display: block;
        margin: 7px 0;

    }

    .fw-bold {
        font-weight: bold;
    }

    .rows {
        border-bottom: 2px solid #808080;
    }
</style>

<body>
    <table>

        <tbody>
            <tr>
                <td colspan="24">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td colspan="24" class="brand">
                    <strong>' . format_company_header_title(set('company_name')) . '</strong>
                    <p>' . set('company_address') . '</p>
                    <p>Tel: ' . set('company_phone1') . ' / ' . set('company_phone2') . '</p>
                    <p>' . set('admin_mail') . ' / ' . set('panel_url') . '</p>
                </td>
            </tr>

            <tr style="background:#eee">
                <td colspan="48" class="text-center header" style>
                    <strong> TÜP TEST DENEY RAPORU</strong>
                </td>
            </tr>

            <tr>
                <td colspan="6"><strong>Tüp Sahibi Firma:</strong></td>
                <td colspan="30">'. $customer["company"] .'</td>

                <td colspan="4"><strong>Deney Tarihi:</strong></td>
                <td colspan="8">'. $report["test_date"] .'</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Firma Adresi :</strong></td>
                <td colspan="30">'. $customer["address"] .'</td>


                <td colspan="4"><strong>Rapor No :</strong></td>
                <td colspan="8">'. $report["report_number"] .'</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Servis No :</strong></td>
                <td colspan="30">'. $report["isemrino"] .'</td>


                <td colspan="4"><strong></strong></td>
                <td colspan="8"></td>
            </tr>

            <tr class="text-center" style="font-size:8px;font-weight:bold;background:#eee">
                <td colspan="2" style="max-width:20px">S.NO</td>
                <td colspan="4" style="max-width:30px">TEST NO</td>
                <td colspan="2" style="max-width:30px">KG</td>
                <td colspan="4" style="max-width:30px">CİNSİ</td>
                <td colspan="6" style="max-width:30px">TÜP İMALATCI FİRMA</td>
                <td colspan="4" style="max-width:30px">TÜP İMAL TARİHİ</td>
                <td colspan="4" style="max-width:30px">SERİ NO</td>
                <td colspan="4" style="max-width:30px">TSE</td>
                <td colspan="4" style="max-width:30px">TÜP YÜZEY DURUMU</td>
                <td colspan="4" style="max-width:40px">SIZDIRMAZLIK DENEYİ</td>
                <td colspan="4" style="max-width:20px;">ESNEME DENEYİ</td>
                <td colspan="6" style="max-width:60px">DÜŞÜNCELER</td>



            </tr>';

            $query = $ac->prepare("SELECT * FROM report_hst_content where report_id = ?");
            $query->execute(array($report["id"]));
$cihazSayisi = 1 ;
            while ($content = $query->fetch(PDO::FETCH_ASSOC)) {
           
           
           $html .='<!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->
            <tr class="text-center" style="font-size:8px">
                <td colspan="2" style="max-width:20px">'. $cihazSayisi .'</td>
                <td colspan="4" style="max-width:30px">'. $content["testno"] .'</td>
                <td colspan="2" style="max-width:30px">'. $content["kg"] .'</td>
                <td colspan="4" style="max-width:30px">'. $content["cinsi"] .'</td>
                <td colspan="6" style="max-width:30px">'. $content["imalatci_firma"] .'</td>
                <td colspan="4" style="max-width:30px">'. $content["imal_tarihi"] .'</td>
                <td colspan="4" style="max-width:30px">'. $content["serino"] .'</td>
                <td colspan="4" style="max-width:30px">'. ($content["tse_belgesi"] =="1" ? "VAR" : "YOK" ) .'</td>
                <td colspan="4" style="max-width:30px">'. ($content["yuzey_durumu"] =="1" ? "OLUMLU" : "OLUMSUZ" ) .'</td>
                <td colspan="4" style="max-width:40px">'. ($content["sizdirmazlik_deneyi"] =="1" ? "VAR" : "YOK" ) .'</td>
                <td colspan="4" style="max-width:20px">'. ($content["esneme_deneyi"] =="1" ? "OLUMLU" : "OLUMSUZ" ) .'</td>
                <td colspan="6" style="max-width:80px">'. $content["things"] .'</td>

            </tr>
            <!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->';
            $cihazSayisi += 1;
            }

          $html .= '<tr>

                <td colspan="48">
                    <div style="margin-bottom:30px">
                    '. 
                    $notes = str_replace('{cihazSayisi}', $cihazSayisi - 1 . " Adet", $report["notes"]);
                    echo $cihazSayisi ;
     $html .= '
                  </div>
                </td>
            </tr>

            <tr>
                <td colspan="24" class="text-center"><strong>Rapor Tanzim Tarihi</strong></td>
                <td colspan="24" class="text-center"><strong>Onay</strong></td>
            </tr>
            <tr class="text-center">
                <td colspan="24" style="height:40px">'. $report_date .'</td>
                <td colspan="24"  style="background:#ddd"></td>

            </tr>
       

        </tbody>
    </table>



</body>

</html>';


// İÇERİK BURAYA GELECEK
echo $html;


// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class
$options = new Options();
$options->set('isPhpEnabled', true); // PHP kodlarının çalıştırılmasını etkinleştir
$dompdf = new Dompdf($options);


$dompdf->loadHtml($html);


// $htmlfile=file_get_contents("pages/1/print.php");
// $dompdf->loadHtml($htmlfile);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
// PDF'yi oluştur
$dompdf->render();
ob_end_clean();


//Dosyayı indir
//$dompdf->stream("document.pdf", array("Attachment" => false));

//Tarayıcıda göster
$dompdf->stream($document , array("Attachment" => false));
