<?php
//require dirname(__DIR__,1) . '/bootstrap.php';
// require_once "App/Helper/date.php";

use App\Helper\Security;

$id = Security::decrypt($_GET["id"]);


function toBase64($image)
{

    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}

// QR kod oluşturmak için kütüphaneleri yükle
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;






// Rapor Bilgileri
$query = $ac->prepare("SELECT * from projects where id = ?");
$query->execute(array($id));
$service = $query->fetch(PDO::FETCH_ASSOC);

// Firma Bilgileri
$custquery = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$custquery->execute(array($service["pcid"]));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Teklif Ürünleri (Eğer varsa)
$offerProducts = [];
if (!empty($service['poid'])) {
    $prodQuery = $ac->prepare("SELECT title, amount, unit FROM offermatters WHERE oid = ? ORDER BY satirno");
    $prodQuery->execute([$service['poid']]);
    $offerProducts = $prodQuery->fetchAll(PDO::FETCH_ASSOC);
}



$document = $customer['company'] . "-" . $service['service_number'];
$address = $customer["address"];
$encodedAddress = urlencode($address);
$googleMapsLink = "https://www.google.com/maps/search/?api=1&query=" . $encodedAddress;

// QR kodunu Google Maps linkinden oluştur
try {
    $qrCode = QrCode::create($googleMapsLink)
        ->setSize(200)
        ->setMargin(10);

    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrCodeBase64 = $result->getDataUri(); // Base64 formatında veri URI'si
} catch (Exception $e) {
    // Eğer QR kod oluşturulamazsa boş string kullan
    $qrCodeBase64 = '';
}

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
        padding: 10px 0;
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
</style>

<body>
    <table>

        <tbody>
            <tr>
                <td colspan="12" style="min-width:250px;margin-right:10px"> 
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo" style="padding:10px">
                </td>
              
                <td colspan="24" class="form-head" style="min-width:200px">
                    <div style="padding:10px">
                        <strong>İŞ EMRİ / GÖREVLENDİRME FORMU</strong>
                    </div>
                </td>
               
                <td colspan="12" style="min-width:250px">
                    <div style="text-align:right;padding:4px">
                        <strong >' . set("company_name") . '</strong>
                        <p style="margin:0">' . set("company_address") . '</p>
                        <p style="margin:0">' . set("company_phone1") . ' / ' . set("company_phone2") . '</p>
                        <p style="margin:0">' . set("panel_url") . ' / ' . set("admin_mail") . '</p>
                  </div>
                </td>
            </tr>



           

            <tr>
                <td colspan="48" class="text-center text-white bg-gray" style="font-size:9px">
                <div style="padding:6px"> SERVİS BİLGİLERİ</div>
                </td>
            </tr>

            <tr class="text-center" style="font-size:9px">
                <td colspan="6" style="text-align:right;padding-right:5px" >Firma Adı :</td>
                <td colspan="30" style="text-align:left;padding-left:5px">' . $customer["company"] . '</td>
                
                <td colspan="4" style="text-align:right;padding-right:5px">Servis No :  </td>
                <td colspan="8"><strong>' . $service["service_number"] . '</strong></td>
            </tr>
            <tr class="text-center" style="font-size:9px">
                <td colspan="6" style="text-align:right;padding-right:5px" >Firma İlgili Kişi :</td>
                <td colspan="30" style="text-align:left;padding-left:5px">' . $customer["yetkili"] . '</td>

                <td colspan="4" style="text-align:right;padding-right:5px">Servis Oluşturma Tarihi :</td>
                <td colspan="8"><strong>' . $service["pregdate"] . '</strong></td>
            </tr>
 
           <tr class="text-center" style="font-size:9px">
                <td colspan="6" style="text-align:right;padding-right:5px">Firma Adres: <p style="margin:0">Telefon:</p></td>
                <td colspan="22" style="text-align:left;padding-left:5px">
                 <a href="' . $googleMapsLink . '" target="_blank">' . $address . '</a>
                  <p style="margin:0"> ' . $customer["gsm"] . '</p></td>
            
                <td colspan="8" style="text-align:center; vertical-align:middle; padding:5px">
                    ' . ($qrCodeBase64 ? '<img src="' . $qrCodeBase64 . '" width="55" height="55" />' : '<span style="font-size:7px; color:#999;">QR Hazırlanamadı</span>') . '
                </td>

                <td colspan="4" style="text-align:right;padding-right:5px; vertical-align:middle;">Servisi Oluşturan Personel :  </td>
                <td colspan="8" style="text-align:center"><strong>' . getUsername($service["pcreativer"]) . '</strong></td>
            </tr>
           

';
if (!empty($offerProducts)) {
    $html .= '
            <tr>
                <td colspan="16" class="text-center text-white bg-gray" style="font-size:9px">
                    <div style="padding:6px"> KULLANILACAK MALZEMELER</div>
                </td>
                <td colspan="32" class="text-center text-white bg-gray" style="font-size:9px">
                    <div style="padding:6px"> İŞİN TANIMI</div>
                </td>
            </tr>
            <tr>
                <td colspan="16" style="vertical-align:top;">
                    <div style="min-height:180px;text-align:left;margin:5px;font-size:8px">';
    foreach ($offerProducts as $product) {
        $html .= '• ' . $product['title'] . ' (' . (float) $product['amount'] . ' ' . $product['unit'] . ')<br>';
    }
    $html .= '      </div>
                </td>
                <td colspan="32" style="vertical-align:top;">
                    <div style="min-height:180px;text-align:left;margin:5px">' . nl2br((string)$service["pdesc"]) . '</div>
                </td>
            </tr>';
} else {
    $html .= '
            <tr>
                <td colspan="48" class="text-center text-white bg-gray" style="font-size:9px">
                    <div style="padding:6px"> İŞİN TANIMI</div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="min-height:180px;text-align:left;margin:5px">' . nl2br((string)$service["pdesc"]) . '</div>
                </td>
            </tr>';
}

$html .= '

            <tr class="text-center" style="font-size:9px">
                <td colspan="4">Firmaya Giriş :</td>
                <td colspan="8"></td>
                <td colspan="24"></td>
                <td colspan="4" style="text-align:right">Personel Miktarı :	</td>
                <td colspan="8"></td>
            </tr>

            <tr class="text-center" style="font-size:9px">
                <td colspan="4">Firmadan Çıkış:	</td>
                <td colspan="8"></td>
                <td colspan="24"></td>
                <td colspan="4" style="text-align:right">Varsa Gecikme Nedeni :	</td>
                <td colspan="8"></td>
            </tr>

            <tr class="text-center" style="font-size:9px">
                <td colspan="4">Servis Sonucu:	</td>
                <td colspan="44">
                <div style="min-height:110px;text-align:left;margin:5px">' . nl2br((string)$service["pnotes"]) . '</div></td>
 
            </tr>

            <tr class="text-center" style="font-size:9px;">
                <td colspan="12" style="padding-bottom:60px" >İŞİ TESLİM EDEN PERSONEL: </td>
                <td colspan="24"></td>
                <td colspan="12" style="padding-bottom:60px" >TESLİM ALAN YETKİLİ İMZA </td>
                
            </tr>

            <tr>
            <td colspan="48" style="padding:10px">
            <strong>Talimatlar: </strong>
                 <p>**Tarafınıza "Kişisel Koruyucu Zimmet Tutanağı" ile teslim edilmiş olan koruyucular olmadan işe başlamayınız. (Montaj için: Montaj
                eldiveni, gözlük, toz maskesi, çelik burunlu iş ayakkabısı, iş kıyafeti, yelek; Yüksekte çalışma yapılacaksa; tüm bu kişisel
                koruyuculara ek olarak emniyet kemeri kullanılmalıdır ve bu kemerler talimatlarına uygun şekilde kullanılmalıdır. </p>
                <p style="margin:0">**Elektrik ile alakalı işlerde yalıtkan eldiven yalıtkan ayakkabı vb.) Yetkili olmadan hiçbir makine ve cihazı kullanmayınız. </p>
                <p>**Sürücüler talimatlara,trafik hız ve kurallarına uygun biçimde araç kullanmalıdır. </p>
                <p>**Elektrikli el aletleri ile çalışırken talimatlara uygun çalışma yapılmalıdır.</p>
                <p>**Size beyan edilmiş olan çalışma talimatlarına uygun çalışma yapınız.</p>
            </td>
            </tr>
        </tbody>
    </table>

</body>

</html>';
echo $html;


// reference the Dompdf namespace

use App\Helper\Date;
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
$dompdf->setPaper('A4', 'P');

// Render the HTML as PDF
// PDF'yi oluştur
$dompdf->render();
ob_end_clean();

$canvas = $dompdf->getCanvas(); // get the canvas
// add the page number and total number of pages

$canvas->page_script('
    $text = "' . (date("d.m.Y H:i:s")) . '";
    $pdf->text(500, 805.89, $text, \'Helvetica\', 7, array(0,0,0));
');



//Dosyayı indir
//$dompdf->stream("document.pdf", array("Attachment" => false));

//Tarayıcıda göster
$dompdf->stream($document, array("Attachment" => false));
