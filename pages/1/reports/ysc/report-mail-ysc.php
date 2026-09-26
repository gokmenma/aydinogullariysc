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

// Firma Bilgileri
$custquery = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$custquery->execute(array($report["customer_id"]));
$customer = $custquery->fetch(PDO::FETCH_ASSOC);

// Kontrol Eden Bilgileri
$userquery = $ac->prepare("SELECT * FROM users WHERE id = ?");
$userquery->execute(array($report["controller_id"]));
$controller = $userquery->fetch(PDO::FETCH_ASSOC);

//Firma Yetkilisi Bilgileri
$userquery->execute(array($report["company_official"]));
$company_offical = $userquery->fetch(PDO::FETCH_ASSOC);

$document = $customer['company'] . "-" . $report['report_number'];

$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $document . '</title>

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
</head>
<body>
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
                <td colspan="4" class="bg-light-gray text-center">' . $report['control_date'] . '</td>
                <td colspan="5">Kontrol Periyodu</td>
                <td colspan="3" class="bg-light-gray text-center">' . $report['control_period'] . '</td>

                <td colspan="6">Geçerlilik Tarihi</td>
                <td colspan="6" class="bg-light-gray text-center">' . $report['validity_date'] . '</td>
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

            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    GENEL KONTROLLER
                </td>
            </tr>

            <tr class="text-center" style="font-size:7px">
                <td colspan="2" style="max-width:57px">CİHAZ NO</td>
                <td colspan="5" style="max-width:57px">BULUNDUĞU BÖLGE</td>
                <td colspan="5" style="max-width:57px">CİHAZ KULLANIM TARİHLERİ</td>
                <td colspan="4" style="max-width:57px">KONTROL TARİHLERİ</td>
                <td colspan="4" style="max-width:57px;font-size:7px">KONTROLLERDE YAPILAN İŞLEMLER</td>
                <td colspan="4" style="max-width:57px"> DIŞ MUHAFAZA KONTROLÜ -
                    <p>
                        RENK KONTROLÜ
                    </p>
                </td>
                <td colspan="4" style="max-width:57px">ÇEVRE KONTROLÜ</td>
                <td colspan="4" style="max-width:57px">PİM -MÜHÜR KONTROLÜ</td>
                <td colspan="4" style="max-width:57px">MANOMETRE KONTROLÜ</td>
                <td colspan="4" style="max-width:57px">HORTUM -NOZUL KONTROL</td>
                <td colspan="4" style="max-width:57px">TALİMAT KONTROLÜ</td>
                <td colspan="4" style="max-width:57px;font-size:6px">AĞIRLIK KONTROLÜ MANOMETRE KULLANILMAYANLARDA</td>

            </tr>

            <tr class="text-center" style="font-size:6px">
                <td colspan="2"></td>
                <td colspan="5"></td>
                <td colspan="2">DOLUM TARİHİ</td>
                <td colspan="3" >SON KULLANMA TARİHİ</td>

                <td colspan="2">1. KONTROL TARİHİ</td>
                <td colspan="2">2. KONTROL TARİHİ</td>
              
                <td colspan="2">1. KONTROL TARİHİ</td>
                <td colspan="2">2. KONTROL TARİHİ</td>

                <td colspan="2" style="max-width:26px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>

                <td colspan="2" style="max-width:28px;">UYGUN</td>
                <td colspan="2" style="max-width:29px;">UYGUN DEĞİL</td>
            </tr>';




$query = $ac->prepare("SELECT * FROM report_ysc_content where report_id = ?");
$query->execute(array($report["id"]));

// Sütun başlıklarını alır
$fieldquery = $ac->prepare("SHOW COLUMNS FROM report_ysc_content");
$fieldquery->execute();
$columns = $fieldquery->fetchAll(PDO::FETCH_ASSOC);


while ($content = $query->fetch(PDO::FETCH_ASSOC)) {

    $html .= '<tr class="text-center" style="font-size:6px">
                    <!--Cihaz No -->
                    <td colspan="2">
                        <div style="padding:10px"> '
        . $content['cihaz_no'] .
        '</div>
                    </td>

                    <!--Bulundugu Bolge -->
                        <td colspan="5"> ' . $content['bulundugu_bolge'] . '</td>

                    <!--Cihaz Dolum Tarihi-->
                        <td colspan="2" class="text-nowrap"  >' . $content['cihaz_dolum_tarihi'] . '</td>
                        <td colspan="3" >' . $content['cihaz_sonkullanma_tarihi'] . '</td>
    

                    <!--Kontrol Tarihi-->
                        <td colspan="2" >' . $content['kontrol_tarihi_1'] . '</td>
                        <td colspan="2" >' . $content['kontrol_tarihi_2'] . '</td>
                    
                    <!--Kontrol Tarihi-->
                        <td colspan="2" >' . $content['islem_kontrol_tarihi_1'] . '</td>
                        <td colspan="2">' . $content['islem_kontrol_tarihi_2'] . '</td>';


    // UYGUNLUK KONTORLÜ YAPILAN ALANLARDA FOREACH İLE DÖNEREK KONTROL YAPILIR 
    // 8.KOLONDAN İTİBAREN UYGUNLUK KONTROLÜ YAPILMAKTADIR
    foreach ($columns as $index => $column) {
        if ($index >= 10) {
            $html .= '<td colspan="2" style="max-width:28px;">' . ($content[$column['Field']] == "1" ? "UYGUN" : "") . '</td>
                                                <td colspan="2" style="max-width:29px; color:red">' .
                ($content[$column['Field']] == "0" ? "UYGUN DEĞİL" : "") .
                '</td>';
        }
    }



    $html .= '</tr>';

}
;

$html .= '
           <tr>
                <td colspan="12">İkaz ve Uyarılar</td>
                <td colspan="36">
                ' . $report["warnings"] . '
                </td>
            </tr>

            <tr>
                <td colspan="12">Sonuç ve Kanaat</td>
                <td colspan="36">
                ' . $report["notes"] . '
                </td>
            </tr>

            <tr>

            <td colspan="48">
                ' . $report["subNotes"] . '

            </td>
        </tr>
            <tr>
                <td colspan="24" class="text-center"><strong>Kontrolü Yapan</strong></td>
                <td colspan="24" class="text-center"><strong>Firma Yetkilisi</strong></td>
            </tr>
            <tr>
                <td colspan="8">Adı Soyadı</td>
                <td colspan="16"><strong>' . $controller["username"] . '</strong></td>

                <td colspan="8">Adı Soyadı</td>
                <td colspan="16"><strong>' . $company_offical["username"] . '</strong></td>

            </tr>
            <tr>
                <td colspan="8">Mesleği - Ekipnet Numarası</td>
                <td colspan="16">' . $controller["meslek"] . " - " . $controller["ekipnetno"] . '</td>

                <td colspan="8">Mesleği</td>
                <td colspan="16">' . $company_offical["meslek"] . '</td>

            </tr>


            <tr>

                <td colspan="8">Oda Sicil No - Yetkinlik No</td>
                <td colspan="16">' . $controller["odasicilno"] . " - " . $controller["yetkinlikno"] . '</td>

                <td colspan="8"></td>
                <td colspan="16"></td>

            </tr>

            <tr>

                <td colspan="24"><div class="text-center" style="height:50px">İmza</div></td>
               
            
                <td colspan="24"><div class="text-center" style="height:50px">İmza</div></td>
               

            </tr>

        </tbody>
    </table>';

$html .= $columnName

;

$html .= '</body>

</html>';
echo $html;

$sendto = $customer['email'];
// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class
$options = new Options();
$options->set('isPhpEnabled', true); // PHP kodlarının çalıştırılmasını etkinleştir
$options->set('isHtml5ParserEnabled', true); // HTML5 desteği etkinleştir
$options->set('isRemoteEnabled', true); // Uzak dosyalara erişimi etkinleştir

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// PDF dosyasını kaydetmek için kullanılacak dosya adı
$pdfFilePath = 'sample_document.pdf';

// PDF dosyasını belirtilen yola kaydetme
//$dompdf->stream($pdfFilePath, array("Attachment" => false));

// E-posta ekine PDF dosyasını eklemek için PDF içeriğini bir değişkene atayın
$pdfContent = $dompdf->output();

// E-posta gönderme işlemi için gerekli kütüphaneleri dahil edin
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer'ı yükle
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// PHPMailer nesnesi oluştur
$mail = new PHPMailer(true);
try {
    $mail->IsSMTP();
    $mail->SMTPDebug = 1; // Hata ayıklama değişkeni: 1 = hata ve mesaj gösterir, 2 = sadece mesaj gösterir
    $mail->SMTPAuth = true; //SMTP doğrulama olmalı ve bu değer değişmemeli
    $mail->SMTPSecure = ''; // Normal bağlantı için boş bırakın veya tls yazın, güvenli bağlantı kullanmak için ssl yazın
    $mail->Host = set("mail_host"); // Mail sunucusunun adresi (IP de olabilir)
    $mail->Port = set("mail_port"); // Normal bağlantı için 587, güvenli bağlantı için 465 yazın
    $mail->IsHTML(true);
    $mail->SetLanguage("tr", "phpmailer/language");
    $mail->Encoding = 'base64';
    $mail->CharSet = "utf-8";
    $mail->Username = set("mail_username"); // Gönderici adresiniz (e-posta adresiniz)
    $mail->Password = set("mail_password"); // Mail adresimizin sifresi
    $mail->SetFrom(set("mail_from"), set("mail_name"));
    $mail->AddAddress($sendto); // Gönderilen Alıcı
    $mail->SetFrom(set("mail_from"), set("mail_name"));// Gönderen bilgileri



    $mail->AddAddress($sendto); // Gönderilen Alıcı
    $mail->addStringAttachment($pdfContent, 'sample_document.pdf');

    // E-posta konu ve içeriği
    $mail->Subject = 'Pdf gönderimi';
    $mail->Body = 'Pdf ektedir';

    // E-postayı gönderme
    $mail->send();
    echo 'E-posta başarıyla gönderildi!';
} catch (Exception $e) {
    echo "E-posta gönderirken bir hata oluştu: {$mail->ErrorInfo}";
}
ob_end_clean();
?>