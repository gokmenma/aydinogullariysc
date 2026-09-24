<?php
require 'vendor/autoload.php';

function toBase64($image)
{

    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}


$id = $_GET["id"];


// Rapor Bilgileri
$query = $ac->prepare("SELECT * from purchases where id = ?");
$query->execute(array($id));
$report = $query->fetch(PDO::FETCH_ASSOC);

$type=$report["type"] == 1  ? "TALEP" : "SİPARİŞ" ;

// Firma Bilgileri
$custquery = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$custquery->execute(array($report["companyID"]));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Kontrol Eden Bilgileri
$userquery = $ac->prepare("SELECT * FROM users WHERE id = ?");
$userquery->execute(array($report["controller_id"]));
$controller = $userquery->fetch(PDO::FETCH_ASSOC);

//Firma Yetkilisi Bilgileri
$userquery->execute(array($report["company_official"]));
$company_offical = $userquery->fetch(PDO::FETCH_ASSOC);

$document = $customer['company'] . "-" . $report['report_number'];
    

// İÇERİK BURAYA GELECEK

$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>' . shorted($customer['company'], 30) . '- SİPARİŞ TALEP FORMU</title>
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
        border: 1px solid #777;

    }

    td {
        white-space: wrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    tr td{
        border: 1px solid #777;
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
    .fs-16{
        font-size: 16px;
    }

    .bg-gray {
        background-color: #808080;
    }
    .border-bottom{
        border-bottom: 0.1rem solid #000;
    }
    .border-top{
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

    .header strong  {
        /* border-bottom: 2px solid #808080;
        border-top: 2px solid #808080; */
        padding: 5px;
        font-size: 12px;
        display: block;
        margin: 7px 0;
  
    }
    .rows{
        border-bottom: 2px solid #808080;
    }
</style>

<body>
    <table>

        <tbody>
            <tr>
                <td colspan="24">
                <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo" style="padding:10px">
                </td>
                <td colspan="24" class="brand">
                   
                    <div style="text-align:right;padding:4px">
                        <strong >' . format_company_header_title(set("company_name")) . '</strong>
                        <p style="margin:0">' . set("company_address") . '</p>
                        <p style="margin:0">' . set("company_phone1") . ' / ' . set("company_phone2") . '</p>
                        <p style="margin:0">' . set("panel_url") . ' / ' . set("admin_mail") . '</p>
                  </div>
                
                </td>
            </tr>

            <tr style="background:#eee">
                <td colspan="48" class="text-center header" style >
                    <strong> SİPARİŞ TALEP FORMU</strong>
                </td>
            </tr>
            <tr>
                <td colspan="6"><strong>Firma:</strong></td>
                <td colspan="30">'.$customer["company"].'</td>

                <td colspan="4"><strong>Talep No:</strong></td>
                <td colspan="8">'.$report["siparisNo"].'</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Telefon :</strong></td>
                <td colspan="30">'.$customer["gsm"].'</td>


                <td colspan="4"><strong>Tarih :</strong></td>
                <td colspan="8">'.$report["create_time"].'</td>
            </tr>
            <tr>
                <td colspan="6"><strong>E Posta:</strong></td>
                <td colspan="30">'.$customer["email"].'</td>


                <td colspan="4"><strong>Kategori :</strong></td>
                <td colspan="8">'.$type.'</td>
            </tr>
            <tr>
                <td colspan="6"><strong></strong></td>
                <td colspan="30"></td>


                <td colspan="4"><strong>Oluşturan :</strong></td>
                <td colspan="8">'. getUserInfo($report["creator"],"username").'</td>
            </tr>

 

            <tr>
                <td colspan="48" class="text-center" 
                style="font-size:14px;
                        padding:10px;
                        font-weight:bold;
                        ">Ürün Bilgileri</td>
            </tr> 

            <tr class="" style="font-size:8px;font-weight:bold;background:#eee">
                <td colspan="2" style="max-width:30px">NO</td>
                <td colspan="10" style="padding-left:3px;max-width:80px">STOK KODU</td>
                <td colspan="18" style="padding-left:3px;max-width:100px">ÜRÜN / HİZMET AÇIKLAMASI</td>
                <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px">MİKTAR</td>
                <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px">BİRİM FİYAT</td>
                <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px"> TUTAR </td>


            </tr>';
$TlToplam=0;
        $query=$ac->prepare('Select * from purchase_items WHERE purID = ?');
        $query->execute(array($id));
        $sira = 1 ;
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            
            if($row["currency"] == "EUR"){
                $TlToplam= tlFormat($report["Euro"] * $row["amount"] * $row["price"]);
            }elseif($row["currency"] == "USD" || $row["currency"] == "DOLAR"){
                $TlToplam= tlFormat($report["Dollar"] * $row["amount"] * $row["price"]);
            }else{
                $TlToplam= tlFormat( $row["amount"] * $row["price"]);
            }
            
             $altToplam = $report["ToplamTL"] ? $report["ToplamTL"] : 0 ;
             $altToplam=str_replace(".","", $altToplam);
             $KdvOrani= $report["Kdv"] ? $report["Kdv"] : 0;

             $iskonto=$report["iskonto"] ? $report["iskonto"] : 0  ;

             $Kdvtutari = $altToplam * $KdvOrani / 100 ;
             $KdvliToplam = tlFormat($altToplam + $Kdvtutari);

            $html .= '<!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->
                <tr class="" style="font-size:8px">
                    <td colspan="2">
                        <div style="padding:5px;">
                            '. $sira.'
                        </div>
                    </td>
                    <td colspan="10" style="padding-left:3px;max-width:80px;">'.$row["stokKodu"].'</td>
                    <td colspan="18" style="padding-left:3px;max-width:100px">'.$row["product"].'</td>
                    <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px">'.$row["amount"]. " " . $row["unit"].'</td>
                    <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px">'
                    //price >0 ise fiyatı yazdır
                    .($row["price"] > 0 ? $row["price"]. " " . $row["currency"] : "").
                                        
                    '</td>
                    <td colspan="6" style="padding-right:3px;text-align:right;max-width:57px"><strong> '.$TlToplam.' TL</strong></td>
                </tr>
                <!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->';
                $sira ++ ;
            }
           
           

            $html .= '<tr >
                <td rowspan="1" colspan="36"></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;"><strong >ARA TOPLAM</strong></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;">'.$report["altToplam"].' TL</td>
            </tr>
           


            <tr>

                <td colspan="48">
                    <div style="margin-bottom:30px"><strong>Açıklama :</strong>

                        '.$report["description1"].'
                    </div>
                </td>
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


// sayfa şablonu print sayfasında tanımlanıncaya kadar buradan çalıştır
// $htmlfile = file_get_contents("pages/1/print.php");
// $dompdf->loadHtml($htmlfile);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
// PDF'yi oluştur
$dompdf->render();
ob_end_clean();


//Dosyayı indir
//$dompdf->stream("document.pdf", array("Attachment" => false));

//Tarayıcıda göster
$dompdf->stream($document, array("Attachment" => false));
