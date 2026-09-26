<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
session_start();
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;


function toBase64($image)
{
    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}

$pagehead = @$_GET['proforma'] == true ? 'PROFORMA FATURA' : 'FİYAT TEKLİF FORMU';

$oid = $_GET['id'];
$sql = $ac->prepare('SELECT* FROM offers WHERE id = ?');
$sql->execute(array($oid));
$offer = $sql->fetch(PDO::FETCH_ASSOC);

$custquery = $ac->prepare('SELECT* FROM customers WHERE id = ?');
$custquery->execute(array($offer['cid']));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// OLUŞTURAN BİLGİLERİ
$crtquery = $ac->prepare('SELECT* FROM users WHERE id = ?');
$crtquery->execute(array($offer['creativer']));
$creator = $crtquery->fetch(PDO::FETCH_ASSOC);
// OLUŞTURAN BİLGİLERİ

$user_id = $_SESSION['lid'];
$sender_mail = getUserInfo($user_id, 'email');

// TOPLAM BİLGİLERİ

// TOPLAM BİLGİLERİ
$tl_ara_toplam = $offer['tl_alt_toplam'];
$euro_ara_toplam = $offer['euro_alt_toplam'];
$dolar_ara_toplam = $offer['dolar_alt_toplam'];



$iskonto = $offer['iskonto'];
$toplam = $aratoplam - $iskonto;
$kdvOrani = $offer['Kdv'];
$kdvTutari = $toplam * $kdvOrani / 100;
$kdvDahil = $toplam + $kdvTutari;

$aratoplam = tlFormat($aratoplam);
$iskonto = tlFormat($iskonto);
$toplam = tlFormat($toplam);
$kdvTutari = tlFormat($kdvTutari);
$kdvDahil = tlFormat($kdvDahil);

$tl_toplam_karsilik = $offer['tl_toplam_karsilik'];

// Mail ile ilgili alanlar
// ******************************************************************** */
$mail_body = $_POST['mail_body'] ?? 'Teklif formunuz ekte sunulmuştur.';
$mail_body = str_replace("\n", '<br>', $mail_body);
// mail body boş ise default değeri atıyoruz
$mail_body = $mail_body == '' ? 'Teklif formunuz ekte sunulmuştur.' : $mail_body;

// Kopya mail olarak gönderilecek kullanıcılar
$mail_address_copy = $_POST['mail_address'];

foreach ($mail_address_copy as $email) {
    $sending_mail_address .= $email . ',';
}

// E-posta adreslerini virgül ile ayır ve boş olmayanları filtrele
$customer_mail = explode(',', $_POST['customer_mail_address']);

// Alıcıları ekleyin
foreach ($customer_mail as $email) {
    $sending_mail_address .= $email . ',';
}
// ******************************************************************** */

// İÇERİK BURAYA GELECEK

$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.$customer['company'].' - TEKLİF GÖRÜNTÜLE </title>
</head>
<style>
    body {
        font-family: dejavu sans;
        margin: 0;
        padding: auto;
        font-size: 10px;
    }

    @page {
        margin: 40px;
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

   

    .form-head {
        font-size: 16px !important;
        text-align: center;
        border-bottom: 1px solid #808080;
    }

    .border {
        border: 1px solid #ccc !important;
    }
        .text-left {
        text-align: left !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
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
        .border{
        border: 1px solid #808080;
    }
    .border-right{
        border-right: 2px solid #808080;
    }
    .border-bottom{
        border-bottom: 2px solid #808080;
    }
    .border-bottom-1{
        border-bottom: 1px solid #808080;
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

    .brand strong {
        display: block;
        line-height: 1.25;
        margin-bottom: 4px;
    }

    .header strong  {
        border-bottom: 2px solid #808080;
        border-top: 2px solid #808080;
        padding: 5px;
        font-size: 16px;
        display: block;
        margin: 10px 0;

    }
    table-header,.rows{
        border-bottom: 1px solid #808080;
    }
    .rows{
        border-top: 1px solid #808080;
    }

    #alt_toplam_table{
        width: 100%;
      
    }

    #alt_toplam_table tr{
        border-bottom: 1px solid #808080;
    }

    .col-10{
        width: 20,83%;
        min-width: 20,83%;
        max-width: 20,83%;
    }

    .col-6{
        width: 12,5%;
        min-width: 12,5%;
        max-width: 12,5%;
    }

    .border-none{
        border: none !important;
    }

    .p-1{
        padding: 5px !important;
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

            <tr>
                <td colspan="48" class="text-center header" style >
                    <strong> ' . $pagehead . '</strong>
                </td>
            </tr>

            <tr>
                <td colspan="6"><strong>Firma:</strong></td>
                <td colspan="30">' . $customer['company'] . '</td>

                <td colspan="4"><strong>Teklif No:</strong></td>
                <td colspan="8">' . $offer['offerNumber'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Telefon :</strong></td>
                <td colspan="30">' . $customer['gsm'] . '</td>


                <td colspan="4"><strong>Tarih :</strong></td>
                <td colspan="8">' . $offer['offer_date'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>E Posta:</strong></td>
                <td colspan="30">' . $customer['email'] . '</td>


                <td colspan="4"><strong>Referans :</strong></td>
                <td colspan="8"></td>
            </tr>
            <tr>
                <td colspan="6"><strong>İlgili :</strong></td>
                <td colspan="30">' . $customer['yetkili'] . '</td>

                 <td colspan="4"><strong>Teklif Konusu :</strong></td>
                <td colspan="8">' . $offer['offer_subject'] . '</td>
           </tr>



            <tr>
                <td colspan="48" style="padding:30px 0">
                ' . $offer['offer_header_content'] . '

                </td>
                 
            </tr>


            <tr class="table-header" style="font-weight:bold;background:#bbb;">
                <td colspan="2" style="max-width:30px;">NO</td>
                <td colspan="28" style="max-width:100px">ÜRÜN / HİZMET AÇIKLAMASI</td>
                <td colspan="6" style="max-width:57px" class="text-right">MİKTAR</td>
                <td colspan="6" style="max-width:57px" class="text-right">BİRİM FİYAT</td>
                <td colspan="6" style="max-width:57px" class="text-right"> TUTAR </td>


            </tr>';

$inquery = $ac->prepare('select * from offermatters where oid = ? order by satirno');
$inquery->execute(array($offer['id']));
$sira = 1;
while ($matters = $inquery->fetch(PDO::FETCH_ASSOC)) {
    $birimfiyat = tlFormat($matters['saleprice']) . ' ' . $matters['salecur'];
    $toplamfiyat = tlFormat($matters['total_price']) . ' ' . $matters['salecur'];
    // if ($matters['salecur'] == 'TL') {
    //     $tl_ara_toplam += $matters['total_price'];


    // } elseif ($matters['salecur'] == 'EUR') {
    //     $euro_ara_toplam += $matters['total_price'];
    // } elseif ($matters['salecur'] == 'USD') {
    //     $dolar_ara_toplam += $matters['total_price'];
    // }

    $html .= '<tr class="rows" >
                    <td colspan="2">
                        <div style="padding:4px">
                            ' . $sira . '
                        </div>
                    </td>
                    <td colspan="28" style="max-width:100px">' . $matters['title'] . '</td>
                    <td colspan="6" style="max-width:57px" class="text-right">' . $matters['amount'] . ' ' . $matters['unit'] . '</td>
                    <td colspan="6" style="max-width:57px" class="text-right">' . $birimfiyat . '</td>
                    <td colspan="6" style="max-width:57px;text-align:right"> ' . $toplamfiyat . '</td>
                </tr>';
    $sira++;
}



$tl_iskonto = $offer['tl_iskonto'] ?? 0;
$euro_iskonto = $offer['euro_iskonto'] ?? 0;
$dolar_iskonto = $offer['dolar_iskonto'] ?? 0;



$tl_alt_toplam = ($tl_ara_toplam - $tl_iskonto);
$euro_alt_toplam = ($euro_ara_toplam - $euro_iskonto);
$dolar_alt_toplam = ($dolar_ara_toplam - $dolar_iskonto);

$tl_display = $tl_alt_toplam > 0 ? 'block' : 'none';
$euro_display = $euro_alt_toplam > 0 ? 'block' : 'none';
$dolar_display = $dolar_alt_toplam > 0 ? 'block' : 'none';

$tl_kdv = $tl_alt_toplam * $offer['Kdv'] / 100;
$euro_kdv = $euro_alt_toplam * $offer['Kdv'] / 100;
$dolar_kdv = $dolar_alt_toplam * $offer['Kdv'] / 100;

$tl_kdv_dahil = ($tl_alt_toplam + $tl_kdv);
$euro_kdv_dahil = ($euro_alt_toplam + $euro_kdv);
$dolar_kdv_dahil = ($dolar_alt_toplam + $dolar_kdv);


$kur_euro = $offer['curEuro'];
$kur_dolar = $offer['curDollar'];

// DÖVİZ KURLARINA GÖRE HESAPLAMALAR
$euro_toplam_tl_karsilik = $euro_kdv_dahil * $kur_euro;
$dolar_toplam_tl_karsilik = $dolar_kdv_dahil * $kur_dolar;


$toplam_tl_tutari = $tl_kdv_dahil + $euro_toplam_tl_karsilik + $dolar_toplam_tl_karsilik;

//tüm para birimlerini göstermek isteniyorsa
$all_currency = isset($_GET['all_currency']) ? $_GET['all_currency'] : false;

if ($tl_kdv_dahil > 0 && $euro_kdv_dahil > 0 || $tl_kdv_dahil > 0 && $dolar_kdv_dahil > 0 || $euro_kdv_dahil > 0 && $dolar_kdv_dahil > 0) {
    $all_currency = true;
}

if ($_GET["summary"] != "false") {

    if ($all_currency == "true") {


        // ALT TOPLAMLAR
        $html .= '
    <tr>
        <td colspan="48">
            <table id="alt_toplam_table">
                    <tr class="border-none">
                        <td colspan="48" class="text-center" >
                            <div style="margin:15px"></div>
                        </td>
                    </tr>

                    
                    <tr class="text-right" style="background:#bbb;font-weight:bold">
                        <td colspan="45" style="background:#bbb;">PARA BİRİMİ</td>
                        <td class="col-4" style="background:#bbb">TL</td>
                        <td class="col-4" style="background:#bbb">EURO</td>
                        <td class="col-4" style="background:#bbb">DOLAR</td>
                    </tr>


                     <tr class="border-none text-right">
                    <td colspan="42"></td>
                       <td colspan="3" class="border-bottom-1" >
                               ARA TOPLAM
                        </td>
                        <td class="col-10 text-right border-bottom-1" >' . tlFormat($tl_ara_toplam) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($euro_ara_toplam) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($dolar_ara_toplam) . '</td>
                    </tr>';
        if ($tl_iskonto > 0 || $euro_iskonto > 0 || $dolar_iskonto > 0) {
            $html .= '<tr class="border-none text-right">
                    <td colspan="42"></td>
                        <td colspan="3" class="border-bottom-1" >
                                İSKONTO
                        </td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($tl_iskonto) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($euro_iskonto) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($dolar_iskonto) . '</td>
                    </tr>

                   <tr class="border-none text-right">
                    <td colspan="42"></td>
                       <td colspan="3" class="border-bottom-1" >
                               ALT TOPLAM
                        </td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($tl_alt_toplam) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($euro_alt_toplam) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($dolar_alt_toplam) . '</td>
                    </tr>';
        }

        $html .= '<tr class="border-none text-right">
                 
                    <td colspan="42"></td>
                       <td colspan="3" class="border-bottom-1" >
                              KDV '.$offer['Kdv'].'%
                        </td>
                        <td  class="col-10 text-right border-bottom-1" >' . tlFormat($tl_kdv) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($euro_kdv) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($dolar_kdv) . '</td>
                    </tr>
                     <tr class="border-none text-right">
                    <td colspan="42"></td>
                       <td colspan="3" class="border-bottom-1" >
                               KDV DAHİL
                        </td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($tl_kdv_dahil) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($euro_kdv_dahil) . '</td>
                        <td class="col-10 text-right border-bottom-1">' . tlFormat($dolar_kdv_dahil) . '</td>
                    </tr>
                    
                //TL TOPLAMINI BURAYA YAZ
                  <tr class="border-none text-right">
                    <td colspan="42"></td>
                       <td colspan="3" class="border-bottom-1" style="background:#bbb;font-weight:bold">
                             GENEL TOPLAM
                        </td>
                        <td colspan="3" class="col-10 text-center border-bottom-1" style="background:#bbb;font-weight:bold"  >'
            . tlFormat($tl_toplam_karsilik) . " " . "TRY" .
            '</td>
                    </tr>
               
            </table>
        </td>
    </tr>';
    } else {
        //Hangi para biriminin olduğunu buluyoruz 
        if ($tl_ara_toplam > 0) {
            $money_unit = 'TRY';
            $money_ara_toplam = $tl_ara_toplam;
            $money_iskonto = $tl_iskonto;
            $money_alt_toplam = $tl_alt_toplam;
            $money_kdv = $tl_kdv;
            $money_kdv_dahil = $tl_kdv_dahil;

        } elseif ($euro_ara_toplam > 0) {
            $money_unit = 'EUR';
            $money_ara_toplam = $euro_ara_toplam;
            $money_iskonto = $euro_iskonto;
            $money_alt_toplam = $euro_alt_toplam;
            $money_kdv = $euro_kdv;
            $money_kdv_dahil = $euro_kdv_dahil;
            
        } elseif ($dolar_ara_toplam > 0) {
            $money_unit = 'USD';
            $money_ara_toplam = $dolar_ara_toplam;
            $money_iskonto = $dolar_iskonto;
            $money_alt_toplam = $dolar_alt_toplam;
            $money_kdv = $dolar_kdv;
            $money_kdv_dahil = $dolar_kdv_dahil;

        }


        $html .= '
        <tr>
            <td colspan="48">
                <table id="alt_toplam_table">
                        <tr class="border-none">
                            <td colspan="48" class="text-center" >
                                <div style="margin:15px"></div>
                            </td>
                        </tr>
                            <tr class="border-none text-right">
                            <td style="width:60%"></td>
                            <td style="width:20%" class="border-bottom-1"> ARA TOPLAM</td>
                            <td style="width:20%" class="border-bottom-1">' . tlFormat($money_ara_toplam) . " " . $money_unit .'</td>

                        </tr>';
        if ($money_iskonto > 0) {
            $html .= '
                        <tr class="border-none text-right">
                            <td style="width:60%"></td>
                            <td style="width:20%" class="border-bottom-1"> İSKONTO</td>
                            <td style="width:20%" class="border-bottom-1">' . tlFormat($money_iskonto) . " " . $money_unit .'</td>

                        </tr>
                        <tr class="border-none text-right">
                            <td style="width:60%"></td>
                            <td style="width:20%" class="border-bottom-1"> ALT TOPLAM</td>
                            <td style="width:20%" class="border-bottom-1">' . tlFormat($money_alt_toplam) . " " . $money_unit . '</td>

                        </tr>';
        }

        $html .=
            '
                      <tr class="border-none text-right">
                            <td style="width:60%"></td>
                            <td style="width:20%" class="border-bottom-1"> 
                            KDV '.$offer['Kdv'].'%
                            </td>
                            <td style="width:20%" class="border-bottom-1">' . tlFormat($money_kdv) . " " . $money_unit .'</td>
                      </tr>
                     <tr class="border-none text-right">
                            <td style="width:60%" ></td>
                            <td style="width:20%" class="border-bottom-1"> KDV DAHİL</td>
                            <td style="width:20%" class="border-bottom-1">' . tlFormat($money_kdv_dahil) . " " . $money_unit .'</td>
                      </tr>
                    
                      //TL TOPLAMINI BURAYA YAZ
                       <tr class="border-none text-right">
                                <td style="width:60%"></td>
                                <td style="width:20%" class="border-bottom-1" style="background:#bbb;font-weight:bold">
                                        GENEL TOPLAM
                                    </td>
                                    <td style="width:20%" class="col-10 text-right border-bottom-1" style="background:#bbb;font-weight:bold"  >'
                        . tlFormat($money_kdv_dahil) . " " . $money_unit .
                        '</td>
                    </tr>
            </table>
            </td>
        </tr>';

    }
}

$html .= '<tr>

         

                <td colspan="48" style="padding:20px 0">
                 ' . $offer['offer_footer_content'] . '

                </td>
            </tr>

            <tr>
                <td colspan="24" class="text-center"><strong>Oluşturan</strong></td>
                <td colspan="24" class="text-center"><strong>Sipariş Onayı</strong></td>
            </tr>
            <tr class="text-center">
                <td colspan="24">' . $creator['username'] . '</td>
                <td colspan="24">Firma Kaşesi / İmza</td>  

            </tr>
            <tr class="text-center">
                <td colspan="24">' . $creator['Unvan'] . '</td>
                <td colspan="24">.</td>
            </tr>

        </tbody>
    </table>

</body>

</html>';

// İÇERİK BURAYA GELECEK

// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class
$options = new Options();
$options->set('isPhpEnabled', true);  // PHP kodlarının çalıştırılmasını etkinleştir
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// $htmlfile=file_get_contents("pages/1/print.php");
// $dompdf->loadHtml($htmlfile);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

$pdf_file = $customer['company'] . ' ' . $pagehead . '.pdf';
// PDF'yi oluştur
$dompdf->render();

if (!$_GET['send-mail']) {
    ob_end_clean();
    // add pagination
    $canvas = $dompdf->getCanvas();  // get the canvas
    // add the page number and total number of pages
    $canvas->page_script('
    $text = "$PAGE_NUM / $PAGE_COUNT";
    $pdf->text(80, 806.89, $text, \'Helvetica\', 8, array(0,0,0));');

    $dompdf->stream($pdf_file, array('Attachment' => false));
} else {
    $canvas = $dompdf->getCanvas();  // get the canvas
    // add the page number and total number of pages
    $canvas->page_script('
        $text = "$PAGE_NUM / $PAGE_COUNT";
        $pdf->text(80, 806.89, $text, \'Helvetica\', 8, array(0,0,0));
    ');

    $pdf_content = $dompdf->output();

    // MAİL GÖNDERİMİ
    // PDF dosyasını sunucuda geçici olarak saklayın
    $pdf_file = 'Teklif.pdf';

    // send_mail($pdf_file, $pdf_content, $customer, $creator);

    file_put_contents($pdf_file, $pdf_content);
    // // E-posta ekini tanımlayın
    $attachment = chunk_split(base64_encode(file_get_contents($pdf_file)));

    try {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPDebug = 2;
        $mail->SMTPAuth = true;

        $mail->SMTPSecure = 'tls';  // Güvenli bağlantı için tls kullanıyoruz
        $mail->Host = set('mail_host');  // Mail sunucusunun adresi (IP de olabilir)
        $mail->Port = set('mail_port');
        $mail->IsHTML(true);
        $mail->SetLanguage('tr', 'phpmailer/language');
        $mail->Encoding = 'base64';

        $mail->Username = set('mail_username');  // Gönderici adresiniz (e-posta adresiniz)
        $mail->Password = set('mail_password');  // Mail adresimizin sifresi
        $mail->SetFrom('mbeyazilim@gmail.com', set('company_name'));
        // E-posta adreslerini virgül ile ayır ve boş olmayanları filtrele
        $sending_mail_adres = array_filter(array_map('trim', explode(',', $sending_mail_address)));

        foreach ($sending_mail_adres as $email) {
            $mail->addAddress($email);  // E-posta adreslerini ekleyin
        }

        $mail->AddAttachment($pdf_file);  // Yüklenen dosyayı ekle
        $mail->Subject = $mail_subject;
        $mail->Body = $mail_body;
        $mail->CharSet = 'utf-8';

        if ($mail->Send()) {
            // $sql = $ac->prepare("INSERT INTO mail_logs SET tomail = ?, from_mail = ? , mail_body= ?, statu = ? ,mail_file =?, sender = ?");
            // $sql->execute(array($customer["email"], $sender_mail, $mail->Body, 1, $pdf_file, $creator["id"]));

            header("Location: index.php?p=report-send-as-mail&id=$oid&type=offer&st=success-mail");
        } else {
            header('Location:index.php?p=offers&st=unsuccessful');
        }
    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    }

    // PDF dosyasını sunucudan silin
    unlink($pdf_file);
}
