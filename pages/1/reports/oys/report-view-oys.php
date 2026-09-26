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

// RAPOR SORULARI
$quest = $ac->prepare("SELECT * FROM report_questions");
$quest->execute();
$questions = $quest->fetchAll(PDO::FETCH_ASSOC);

//dedektor BİLGİLERİ
$dedector_query = $ac->prepare("SELECT * FROM report_contents where report_id = ?");
$dedector_query->execute(array($id));

//EK BELGELERİ
$attach_query = $ac->prepare("SELECT * FROM files where report_id = ?");
$attach_query->execute(array($id));



// SEÇENEKLERİN DEĞERLERİ
$matters = json_decode($report["report_matters"]);

$bakim = json_decode($report["bakim_bilgileri"]);
$onceki_bakim_varmi = $bakim->bakim1 == "1" ? "EVET" : "HAYIR";
$tutanak_varmi = $bakim->bakim2 == "1" ? "EVET" : "HAYIR";

$dedectors = json_decode($report["dedektor_info"]);


$controller_peak_info = json_decode($report["controller_peak_info"]);




$html = '<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $document . '</title>


    <style>
        @page {
            margin: 50px 25px;
            padding: auto;
            font-size: 8px !important;

        }


        body {
            font-family: Dejavu sans;
            margin: 0;
            padding: auto;

        }

        table {
            width: 100%;
            border-collapse: collapse;
            max-width: 1122px;

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

        .text-nowrap {
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

        .d-block {
            display: inline-block;
        }


        .page-break {
            page-break-after: always;
            visibility: hidden;

        }
    </style>
</head>

<body>
    <table>

        <tbody>
            <tr>
                <td rowspan="" colspan="12" style="min-width:300px">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="24" class="form-head">
                    <strong>OTOMATİK YANGIN SÖNDÜRME SİSTEMLERİ</strong>
                    <p class="m-0"> PERİYODİK KONTROL RAPORU</>
                </td>
                <td colspan="12">
                    <div>
                        <div class="d-block " style="width:70px;font-size:6px">DOKÜMAN NO :</div>
                        <div class="d-block " style="font-size:6px">FR 003</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">HAZIRLAMA TARİHİ </div>
                        <div class="d-block" style="font-size:6px">01.03.2017</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">REV TARİH/REV NO :</div>
                        <div class="d-block" style="font-size:6px">REV.1</div>
                    </div>
                </td>

            </tr>

            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    GENEL BİLGİLER
                </td>
            </tr>
            <tr>
                <td colspan="4">İs Emri No</td>
                <td colspan="8"></td>
                <td colspan="10">Rapor No</td>
                <td colspan="14"></td>
                <td colspan="4">Sayfa No</td>
                <td colspan="8"></td>
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
                <td colspan="36">Binaların Yangından Korunması Hakkında Yönetmelik Madde 98, TS ISO 15004-1,TS ISO 15004-2, 
                TS EN 15004-3 TS EN 15004-4,TS EN 15004-5, TS EN 15004-6, TS EN 15004-7,TS EN 15004-8,TS EN 15004-9,TS EN 15004-10</td>
            </tr>
            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    YANGIN SÖNDÜRME SINIFI
                </td>
            </tr>
            
            <tr>
                <td colspan="24">Otomatik Yangın  Söndürme Sistemi Sınıfı</td>
                <td colspan="24" class="text-center">KÖPÜKLÜ SİSTEM</td>
            </tr>
            <tr>
                <td colspan="24">Otomatik Yangın  Söndürme Sistemi Çeşidi</td>
                <td colspan="24" class="text-center">TRAFO ODASI OTOMATİK CO2 SÖNDÜRME SİSTEMİ</td>
            </tr>
            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    ÖNCEKİ BAKIM BİLGİLERİ
                </td>
            </tr>
            
            <tr>
                <td colspan="36">Daha Önce Kontrolü Yapılmış mı?</td>
                <td colspan="12" class="text-center">' . $onceki_bakim_varmi . '</td>
            </tr>
            <tr>
                <td colspan="36">Önceki Bakım Tutanakları Mevcut Mu?</td>
                <td colspan="12" class="text-center">' . $tutanak_varmi . '</td>
            </tr>
            <tr>
                <td colspan="36">En Son Yapılan Kontrol Tarihi?</td>
                <td colspan="12" class="text-center">' . $bakim->bakim3 . '</td>
            </tr>
            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                   GENEL KONTROLLER
                </td>
            </tr>';


for ($k = 1; $k < 12; $k++) {
    $soru = $questions[$k - 1]["oys_genel"];
    $fv = 'madde' . ($k);
    $first_val = $matters->$fv == 1 ? "UYGUN" : "UYGUN DEĞİL";

    $html .= '
    <tr>
        <td colspan="36">' . $soru . '</td>
        <td colspan="12" class="text-center">' . $first_val . '</td>
    </tr>';
}
;




$html .= '<tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    ONAY
                </td>
            </tr>



            <tr>
                <td colspan="12">
                    DİPLOMA NO
                </td>
                <td colspan="12">
                    5147
                </td>
                <td rowspan="5" colspan="24" class="text-center">
                    ONAY
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    DİPLOMA TARİHİ:
                </td>
                <td colspan="12">
                    ' . $controller["ekipnetno"] . '
                </td>

            </tr>

            <tr>
                <td colspan="12">
                    EMO SİCİL NO

                </td>
                <td colspan="12">
                    ' . $controller["yetkinlikno"] . '
                </td>


            </tr>
            <tr>
                <td colspan="12">
                    EKİPNET NO:
                </td>
                <td colspan="12">
                    ' . $controller["ekipnetno"] . '
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    TARİH
                </td>
                <td colspan="12">
                ' . $report["create_time"] . '
                </td>
            </tr>




        </tbody>
    </table>

    <!-- SAYFA BÖLME -->
    <div class="page-break"></div>

    <!-- İKİNCİ SAYFA İÇERİĞİ -->

    <table>
        <tbody>
            <tr colspan="48">
                <td rowspan="" colspan="12">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="21" class="form-head">
                    <strong>YANGIN ALGILAMA SİSTEMLERİ </strong>
                    <p class="m-0">
                        PERİYODİK KONTROL RAPORU
                    </p>
                </td>

                <td colspan="15">
                    <div>
                        <div class="d-block " style="width:70px;font-size:6px">Firma Adı:</div>
                        <div class="d-block " style="font-size:6px">' . $customer["company"] . '</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">Adresi </div>
                        <div class="d-block" style="font-size:6px">' . $customer["address"] . '</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">Telefon :</div>
                        <div class="d-block" style="font-size:6px">' . $customer["gsm"] . '</div>
                    </div>
                </td>

            </tr>
            <tr colspan="48" class="text-center" style="font-size:6px;background:#eee">
                <td colspan="4">Sıra</td>
                <td colspan="8" style="width:100px">ALGILAMA CİNSİ</td>

                <td colspan="13" style="width:100px">BULUNDUĞU BÖLGE</td>
                <td colspan="8">ÇEVRE KONTROLÜ</td>
                <td colspan="8" style="max-width:20px">DIŞ MUHAFAZA</td>
                <td colspan="7" style="max-width:20px">ÇALIŞABİLİRLİK TESTİ</td>

            </tr>';
$sira = 1;
while ($content = $dedector_query->fetch(PDO::FETCH_ASSOC)) {

    $cevre_kontrolu = $content["cevre_kontrolu"] == "1" ? "UYGUN" : "UYGUN DEĞİL";
    $dis_muhafaza = $content["dis_muhafaza"] == "1" ? "UYGUN" : "UYGUN DEĞİL";
    $calisabilirlik_testi = $content["calisabilirlik_testi"] == "1" ? "UYGUN" : "UYGUN DEĞİL";

    $html .= '<tr colspan="48" class="text-center" style="font-size:6px">
                <td colspan="4">' . $sira . '</td>
                <td colspan="8" style="width:100px">' . $content["algilama_cinsi"] . '</td>

                <td colspan="13" style="width:100px">' . $content["bulundugu_bolge"] . '</td>
                <td colspan="8">' . $cevre_kontrolu . '</td>
                <td colspan="8" style="max-width:20px">' . $dis_muhafaza . '</td>
                <td colspan="7" style="max-width:20px">' . $calisabilirlik_testi . '</td>

            </tr>';
    $sira++;
}

$html .= '<tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    ONAY
                </td>
            </tr>



            <tr>
                <td colspan="12">
                    DİPLOMA NO
                </td>
                <td colspan="12">
                    5147
                </td>
                <td rowspan="5" colspan="24" class="text-center">
                    ONAY
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    DİPLOMA TARİHİ:
                </td>
                <td colspan="12">
                    ' . $controller["ekipnetno"] . '
                </td>

            </tr>

            <tr>
                <td colspan="12">
                    EMO SİCİL NO

                </td>
                <td colspan="12">
                    ' . $controller["yetkinlikno"] . '
                </td>


            </tr>
            <tr>
                <td colspan="12">
                    EKİPNET NO:
                </td>
                <td colspan="12">
                    ' . $controller["ekipnetno"] . '
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    TARİH
                </td>
                <td colspan="12">
                    31.03.2024
                </td>
            </tr>

        </tbody>
    </table>
    <div class="page-break"></div>
    <table>
        <tbody>
            <tr>
                <td rowspan="" colspan="12">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="24" class="form-head">
                    <strong>YANGIN ALGILAMA SİSTEMLERİ </strong>
                    <p class="m-0">
                        PERİYODİK KONTROL RAPORU
                    </p>
                </td>
                <td colspan="12">
                    <div>
                        <div class="d-block " style="width:70px;font-size:6px">DOKÜMAN NO :</div>
                        <div class="d-block " style="font-size:6px">FR 003</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">HAZIRLAMA TARİHİ </div>
                        <div class="d-block" style="font-size:6px">01.03.2017</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">REV TARİH/REV NO :</div>
                        <div class="d-block" style="font-size:6px">REV.1</div>
                    </div>
                </td>

            </tr>
            <tr>
                <td colspan="48">
                    <div style="margin:5px"></div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        1.7.2. İş Ekipmanlarına Ait teknik Özellikler:Raporun bu bölümünde periyodik kontrole tabi
                        tutulacak
                        İG ekipmanlarının adı, markası, modeli, imal yılı, ekipmanın seri numarası, konumu, kullanım
                        amacı
                        ile gerek görülen teknik özellikler ve diğer bilgilere yer verilir.
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        RAPORUN HAZIRLANMASINDA TEMEL DAYANAK:6331 SAYILI KANUNUN İŞ EKİPMANLARININ KULLANIMINDA SAĞLIK
                        VE
                        GÜVENLİK ŞARTLARI YÖNETMELİĞİNİN EK-III BAKIM, ONARIM VE PERİYODİK KONTROLLER İLE İLGİLİ
                        HUSUSLAR
                        MADDE 1.7
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        1.7.3. Periyodik Kontrol Metodu:İlgili standart numarası ve adı, periyodik kontrol esnasında
                        kullanılan ekipmanların özellikleri ve diğer bilgiler belirtilir.
                    </div>
                </td>
            </tr>


            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        1.7.7. Sonuç Ve Kanaat: Raporun bu bölümünde periyodik kontrole tabi tutulan iş ekipmanının
                        varsa
                        tespit edilen ve giderilen noksanlıklar açıklanarak, bir sonraki periyodik kontrole kadar
                        geçecek
                        süre içerisinde görevini güvenli bir şekilde yapıp yapamayacağını açıkça belirtilir.
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        SONUÇ VE KANAAT : 6331 Sayılı Kanun gereği çıkartılan İş Ekipmanlarının Kullanımında Sağlık ve
                        Güvenlik şartları Yönetmeliğine ve BİNALARDA YANGINDA KORUNMA YÖNETMELİĞİ Projede belirtilen
                        kriterlere uygun olup olmadığının belirlenmesine yönelik olarak yapılan. Ayrıca TS 9811, TS EN
                        671-3, TS EN 12416-1 + A2, TS EN 12416-2 + A1,TS EN 12845 + A2 standartlarında belirtien
                        kritelere
                        uygun olup YANGIN ALGILAMA SİSTEMİ 1 YIL SÜREYLE KULLANIMA UYGUNDUR. Uygunluğunun
                        devamlılığından
                        işveren sorumludur.
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        1.7.8. ONAY: Bu bölümde periyodik kontrolleri yapmaya yetkili kişinin/kişilerin kimlik
                        bilgileri,
                        mesleği, diploma tarihi ve numarasına ilişkin bilgiler, Bakanlık kayıt numarası ile raporun kaç
                        nüsha olarak düzenlendiğini belirterek, imza altına alınır.Yukarıdaki bilgilerin veya yetkili
                        kişinin imzasının bulunmadığı raporlar geçersizdir.
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <div style="padding:3px">
                        6331 SAYILI KANUNUN "İŞ EKİPMANLARININ KULLANIMINDA SAĞLIK VE GÜVENLİK ŞARTLARI YÖNETMELİĞİ"
                        GEREĞİ
                        HAZIRLANAN BU RAPOR İKİ NÜSHA VE İKİ SAYFA OLARAK TANZİM EDİLMİŞ VE AŞAĞIDAKİ SERİ NUMARISI İLE
                        KAYIT ALTINA ALINARAK ONAYLANMIŞTIR.UYGUNLUĞUNUN DEVAMLILIĞINDAN İŞ VEREN SORUMLUDUR.
                    </div>
                </td>
            </tr>

            
            <tr>
                <td colspan="48" class="text-center text-white bg-gray">
                    MUAYENEYİ YAPAN
                </td>
            </tr>



            <tr>
                <td colspan="12">
                    Adı Soyadı :
                </td>
                <td colspan="12">
                ' . $controller_peak_info->name . '
                </td>
                <td rowspan="8" colspan="24" class="text-center">
                    ONAY
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    UNVANI :
                </td>
                <td colspan="12">
                ' . $controller_peak_info->title . '
                </td>

            </tr>

            <tr>
                <td colspan="12">
                    DİPLOMA NO

                </td>
                <td colspan="12">
                ' . $controller_peak_info->diploma . '
                </td>


            </tr>
            <tr>
                <td colspan="12">
                    EMO SİCİL NO
                </td>
                <td colspan="12">
                ' . $controller_peak_info->emo . '
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    EKİPNET NO
                </td>
                <td colspan="12">
                ' . $controller_peak_info->ekipnet . '
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    RAPOR TARİHİ
                                </td>
                <td colspan="12">
                    31.03.2024
                </td>
            </tr>
            <tr>
                <td colspan="12">
                REVİZYON TARİHİ
                </td>
                <td colspan="12">
                    31.03.2024
                </td>
            </tr>
            <tr>
                <td colspan="12">
                BİR SONRAKİ KONTROL TARİHİ
                </td>
                <td colspan="12">
                    31.03.2024
                </td>
            </tr>

        </tbody>
    </table>
    <!-- İKİNCİ SAYFA İÇERİĞİ BİTİŞ-->';

//EK BELGELERİ
$attach_query = $ac->prepare("SELECT * FROM files where report_id = ?");
$attach_query->execute(array($id));


while ($attach = $attach_query->fetch(PDO::FETCH_ASSOC)) {
    $path = "files/reports/";
    $image_path = $path . $attach["filename"];
    // list($width, $height) = getimagesize($attach);
    list($width, $height) = getimagesize($image_path);

    // Sayfa yönlendirmesini belirle
    $orientation = ($width > $height) ? 'width' : 'height'; // Yatay ise 'L', dikey ise 'P'
    //$dompdf->setPaper('A4', $orientation);
}

$html .= '
    <div class="page-break"></div>

    <!-- ÜÇÜNCÜ SAYFA İÇERİK -->
    <table>
        <tbody>
            <tr>
                <td rowspan="" colspan="12">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="24" class="form-head">
                    <strong>YANGIN ALGILAMA SİSTEMLERİ</strong>
                    <p class="m-0">
                        ' . $attach["fileDescription"] . '
                    </p>
                </td>
                <td colspan="12">
                    <div>
                        <div class="d-block " style="width:70px;font-size:6px">DOKÜMAN NO :</div>
                        <div class="d-block " style="font-size:6px">FR 003</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">HAZIRLAMA TARİHİ </div>
                        <div class="d-block" style="font-size:6px">01.03.2017</div>
                    </div>

                    <div>
                        <div class="d-block" style="width:70px;font-size:6px">REV TARİH/REV NO :</div>
                        <div class="d-block" style="font-size:6px">REV.1</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="12">Firma</td>
                <td colspan="36">Firma Adı Buraya</td>
            </tr>
            <tr>
                <td colspan="12">Firma Adresi</td>
                <td colspan="36">Firma Adresi Buraya</td>
            </tr>
            <tr>
                <td colspan="48">
                <div style="height:85%;background:#eee" class="text-center">
                <img src="' . toBase64($image_path) . '" id="logo" style="' . $orientation . ':100%" alt="' . $attach["fileDesciption"] . '">
                </div>
                </td>
            </tr>
            <tr>
                <td colspan="48">' . $attach["fileDescription"] . '</td>
            </tr>

        </tbody>
    </table>

    <!-- ÜÇÜNCÜ SAYFA İÇERİK -->
</body>

</html>';


echo $html;


// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class
$options = new Options();
$options->set('isPhpEnabled', true); // PHP kodlarının çalıştırılmasını etkinleştir
$dompdf = new Dompdf($options);

//RAPOR TAMAMLANDIĞINDA BURASI AKTİF ALTTAKİ İKİ SATIR PASİF OLACAK
$dompdf->loadHtml($html);

//$htmlfile = file_get_contents("pages/1/print.php");
//$dompdf->loadHtml($htmlfile);



// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', "P");


// Render the HTML as PDF
// PDF'yi oluştur
$dompdf->render();
ob_end_clean();
// add pagination
$canvas = $dompdf->getCanvas(); // get the canvas
// add the page number and total number of pages
$canvas->page_script('
    $text = "$PAGE_NUM / $PAGE_COUNT";
    $pdf->text(535, 791.89, $text, \'Helvetica\', 8, array(0,0,0));
');

//Dosyayı indir
//$dompdf->stream("document.pdf", array("Attachment" => false));

//Tarayıcıda göster
$dompdf->stream($document, array("Attachment" => false));
