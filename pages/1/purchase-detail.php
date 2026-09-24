<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';



function toBase64($image)
{
    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}

$id = $_GET['id'];

// Rapor Bilgileri
$query = $ac->prepare('SELECT * from purchases where id = ?');
$query->execute(array($id));
$report = $query->fetch(PDO::FETCH_ASSOC);

// Firma Bilgileri
$custquery = $ac->prepare('SELECT * FROM customers WHERE id = ?');
$custquery->execute(array($report['companyID']));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Kontrol Eden Bilgileri
$userquery = $ac->prepare('SELECT * FROM users WHERE id = ?');
$userquery->execute(array($report['controller_id']));
$controller = $userquery->fetch(PDO::FETCH_ASSOC);

// Firma Yetkilisi Bilgileri
$userquery->execute(array($report['company_official']));
$company_offical = $userquery->fetch(PDO::FETCH_ASSOC);

$document = $customer['company'] . '-' . $report['report_number'];


//Mail ile ilgili alanlar
$mail_body = $_POST['mail_body'] ?? 'Siparişiniz ekte sunulmuştur.';
$mail_body = str_replace("\n", '<br>', $mail_body);
// mail body boş ise default değeri atıyoruz
$mail_body = $mail_body == '' ? 'Siparişiniz ekte sunulmuştur.' : $mail_body;

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




// İÇERİK BURAYA GELECEK

$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . shorted($customer['company'], 30) . '- Sipariş Formu</title>
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
                    <strong>' . format_company_header_title(set('company_name')) . '</strong>
                    <p>' . set('company_address') . '</p>
                    <p>Tel: ' . set('company_phone1') . ' / ' . set('company_phone2') . '</p>
                    <p>' . set('admin_mail') . ' / ' . set('panel_url') . '</p>
                </td>
            </tr>

            <tr style="background:#eee">
                <td colspan="48" class="text-center header" style >
                    <strong> SATIN ALMA FORMU </strong>
                </td>
            </tr>
            <tr>
                <td colspan="6"><strong>Firma:</strong></td>
                <td colspan="30">' . $customer['company'] . '</td>

                <td colspan="4"><strong>Satın Alma No:</strong></td>
                <td colspan="8">' . $report['siparisNo'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>Telefon :</strong></td>
                <td colspan="30">' . $customer['gsm'] . '</td>


                <td colspan="4"><strong>Tarih :</strong></td>
                <td colspan="8">' . $report['create_time'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>E Posta:</strong></td>
                <td colspan="30">' . $customer['email'] . '</td>


                <td colspan="4"><strong>Fatura Tarihi :</strong></td>
                <td colspan="8">' . $report['invoice_date'] . '</td>
            </tr>
            <tr>
                <td colspan="6"><strong>İlgili :</strong></td>
                <td colspan="30">' . $customer['yetkili'] . '</td>


                <td colspan="4"><strong>Fatura No :</strong></td>
                <td colspan="8">' . $report['invoice_number'] . '</td>
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
                <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px">MİKTAR</td>
                <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px">BİRİM FİYAT</td>
                <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px"> TUTAR </td>


            </tr>';
// $TlToplam = 0;
$toplam_tutar = 0;
$query = $ac->prepare('Select * from purchase_items WHERE purID = ?');
$query->execute(array($id));
$sira = 1;
function convertToNumber($value) {
    // TL ve boşlukları kaldır, noktaları ve virgülleri doğru şekilde işleyerek sayıya dönüştür
    $value = str_replace(['TL', ' ', '.'], '', $value);
    $value = str_replace(',', '.', $value);
    return (float) $value;
}


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

    $amount = ($row['amount']);
    $price = ($row['price']);

    if ($row['currency'] == 'EUR') {
        $TlToplam = $report['Euro'] * $amount * $price;
    } elseif ($row['currency'] == 'USD' || $row['currency'] == 'DOLAR') {
        $TlToplam = $report['Dollar'] * $amount * $price;
    } else {
        $TlToplam = $amount * $price;
    }
    $toplam_tutar += $TlToplam;

    $iskonto = isset($report['iskonto']) ? convertToNumber($report['iskonto']) : 0;
    $rowspan = $iskonto > 0 ? 5 : 4;

    // Ara toplamı hesapla
    $araToplam = $toplam_tutar - $iskonto;

    $KdvOrani = isset($report['Kdv']) ? convertToNumber($report['Kdv']) : 0;
    $Kdvtutari = $araToplam * $KdvOrani / 100;
    $KdvliToplam = $araToplam + $Kdvtutari;

    $html .= '<!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->
                <tr class="" style="font-size:8px">
                    <td colspan="2">
                        <div style="padding:5px;">
                            ' . $sira . '
                        </div>
                    </td>
                    <td colspan="10" style="padding-left:3px;max-width:80px;">' . $row['stokKodu'] . '</td>
                    <td colspan="18" style="padding-left:3px;max-width:100px">' . $row['product'] . '</td>
                    <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px">' . $row['amount'] . ' ' . $row['unit'] . '</td>
                    <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px">
                    
                    ' . ($row['price'] > 0 ? $row['price'] . ' ' . $row['currency'] : '') .'
                                      
                    </td>
                    <td colspan="6" style="text-align:right;padding-right:3px;max-width:57px"><strong> ' . tlFormat($TlToplam) . ' TL</strong></td>
                </tr>
                <!-- ÜRÜN BİLGİLERİ BURAYA GELECEK -->';
    $sira++;
}

$html .= '<tr >
                <td rowspan="'. $rowspan.'" colspan="36"></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;" ><strong >TOPLAM</strong></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;" >' .tlFormat($toplam_tutar) . ' TL</td>
            </tr>';


           if($iskonto > 0) {
            $html .=
            '<tr>
                    <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;"><strong>İSKONTO</strong></td>
                    <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;">' . tlformat($iskonto) . ' TL</td>
                </tr>';
            }

            $html .='<tr>
                
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;"><strong>ARA TOPLAM</strong></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;">' . tlformat($araToplam) . ' TL</td>
            </tr>
            <tr>
                
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;"><strong>KDV</strong></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;">' . tlFormat($Kdvtutari) . ' TL</td>
            </tr>
            <tr>
               
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;"><strong>KDV DAHİL</strong></td>
                <td colspan="6" class="border-bottom" style="text-align:right;padding-right:3px;">' . tlFormat($KdvliToplam) . ' TL</td>
            </tr>



            <tr>

                <td colspan="48">
                    <div style="margin-bottom:30px">

                        ' . $report['description1'] . '
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="24" class="text-center"><strong>Oluşturan</strong></td>
                <td colspan="24" class="text-center"><strong>Satın Alma Onayı</strong></td>
            </tr>
            <tr class="text-center">
                <td colspan="24">' . getUserInfo($report['creator'], 'username') . '</td>
                <td colspan="24">Firma Kaşesi / İmza</td>  

            </tr>
            <tr class="text-center">
                <td colspan="24" >' . getUserInfo($report['creator'], 'meslek') . '</td>
                <td colspan="24">.</td>
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
$options->set('isPhpEnabled', true);  // PHP kodlarının çalıştırılmasını etkinleştir
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
// ob_end_clean();

// //Dosyayı indir
// //$dompdf->stream("document.pdf", array("Attachment" => false));

// //Tarayıcıda göster
// $dompdf->stream($document, array("Attachment" => false));
// send_mail($pdf_file, $pdf_content, $customer, $creator);

// PDF'yi oluştur
$dompdf->render();
if (!$_GET['send-mail']) {
    ob_end_clean();
    // add pagination
    $canvas = $dompdf->getCanvas();  // get the canvas
    // add the page number and total number of pages
    $canvas->page_script('
    $text = "$PAGE_NUM / $PAGE_COUNT";
    $pdf->text(80, 791.89, $text, \'Helvetica\', 8, array(0,0,0));');

    $dompdf->stream($pdf_file, array('Attachment' => false));
} else {
    $canvas = $dompdf->getCanvas();  // get the canvas
    // add the page number and total number of pages
    $canvas->page_script('
        $text = "$PAGE_NUM / $PAGE_COUNT";
        $pdf->text(80, 791.89, $text, \'Helvetica\', 8, array(0,0,0));
    ');

    $pdf_content = $dompdf->output();

    // MAİL GÖNDERİMİ
    // PDF dosyasını sunucuda geçici olarak saklayın
    $pdf_file = $report['siparisNo'] . ' numaralı Sipariş.pdf';

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
        //************************************************************** */
        $sending_mail_adres = array_filter(array_map('trim', explode(',', $sending_mail_address)));
        
        foreach ($sending_mail_adres as $email) {
            $mail->addAddress($email);  // E-posta adreslerini ekleyin
        }
        //************************************************************** */

        $mail->AddAttachment($pdf_file);  // Yüklenen dosyayı ekle
        $mail->Subject = 'Sipariş Maili';
        $mail->Body = $mail_body;
        $mail->CharSet = 'utf-8';

        if ($mail->Send()) {
            //  $sql = $ac->prepare('INSERT INTO mail_logs SET tomail = ?, from_mail = ? , mail_body= ?, statu = ? ,mail_file =?, sender = ?');
            //  $sql->execute(array($sending_mail_adres, sesset("email"), $mail->Body, 1, $pdf_file, $creator['id']));

            header('Location: index.php?p=report-send-as-mail&id=' . $id . '&type=purchase&st=success-mail');
        } else {
            header('Location:index.php?p=purchases&st=unsuccessful');
        }
    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    }

    // PDF dosyasını sunucudan silin
    unlink($pdf_file);
}
