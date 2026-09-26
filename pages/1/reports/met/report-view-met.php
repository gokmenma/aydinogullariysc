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
$quest = $ac->prepare("SELECT met as soru FROM report_questions WHERE met !='' ");
$quest->execute();
$questions = $quest->fetchAll(PDO::FETCH_ASSOC);

//DOLAP BİLGİLERİ
$closet_query = $ac->prepare("SELECT * FROM report_met_content where report_id = ?");
$closet_query->execute(array($id));

//EK BELGELERİ
$attach_query = $ac->prepare("SELECT * FROM files where report_id = ?");
$attach_query->execute(array($id));



// SEÇENEKLERİN DEĞERLERİ
$matters = json_decode($report["report_matters"]);


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
.landscape {
    page: rotated;
    transform-origin: top left;
    transform: translateX(100%) rotate(90deg);
    width: 100vh;
    height: 100vw;
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
                <td rowspan="" colspan="12">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="24" class="form-head">
                    <strong>MEKANİK TESİSAT PERİYODİK KONTROL RAPORU</strong>
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
                <td colspan="48" class="text-center text-white bg-gray">
                    GENEL MADDELER
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    1.7.2. İş Ekipmanlarına Ait teknik Özellikler:Raporun bu bölümünde periyodik kontrole tabi tutulacak
                    İG ekipmanlarının adı, markası, modeli, imal yılı, ekipmanın seri numarası, konumu, kullanım amacı
                    ile gerek görülen teknik özellikler ve diğer bilgilere yer verilir.
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    <strong>
                        RAPORUN HAZIRLANMASINDA TEMEL DAYANAK:6331 SAYILI KANUNUN İŞ EKİPMANLARININ KULLANIMINDA SAĞLIK
                        VE
                        GÜVENLİK ŞARTLARI YÖNETMELİĞİNİN EK-III BAKIM, ONARIM VE PERİYODİK KONTROLLER İLE İLGİLİ
                        HUSUSLAR
                        MADDE 1.7
                    </strong>
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    1.7.3. Periyodik Kontrol Metodu:İlgili standart numarası ve adı, periyodik kontrol esnasında
                    kullanılan ekipmanların özellikleri ve diğer bilgiler belirtilir.
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    <strong>
                        1.7.3. MADDE GEREĞİ:KONTROL METODUN İLGİLİ STANDART NUMARASI VE ADI
                    </strong>
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    TS 11368 YANGIN ÖNLEME-HORTUM DOLAPLARI
                    <p class="m-0 ">
                        TS 11926 YANGIN MUSLUKLARI TESİS VE KULLANIM KURALLARINA GÖRE YILLIK YAPILAN UYGUNLUK KONTROLÜ
                    </p>
                </td>
            </tr>


            <tr>
                <td colspan="48">
                    1.7.4. Tespit Ve Değerlendirme: Raporun bu bölümünde EK-III madde 1.7.3\'te belirlenen kurallar ve
                    yapılan periyodik kontrolden elde edilen değerlerin , yine EK-III madde 1.7.2\'de yer verilen iş
                    ekipmanının teknik özelliklerini karşılayıp karşılamadığı husus ile ilgili standart ve teknik
                    litaretürde yer alan sınır değerlere uygun olup olmadığı kıyaslanarak değerlendirilir.Periyodik
                    kontrolde uygulanan test ve diğer işlemlere ilişkin bilgilere yer verilir.
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    <strong>
                        1.7.4. TESPİT VE DEĞERLENDİRME:
                    </strong>
                </td>
            </tr>';



for ($k = 1; $k < 16; $k++) {
    $soru = $questions[$k]["soru"];
    $fv = 'madde' . ($k);
    $first_val = ($matters->$fv == "1" || $matters->$fv == "UYGUN") ? "UYGUN" : "UYGUN DEĞİL";
    $first_number = $k;
    $second_number = $k + 15;

    $sv = 'madde' . ($k + 15);
    $second_val = ($matters->$sv == "1" || $matters->$sv == "UYGUN") ? "UYGUN" : "UYGUN DEĞİL";

    $html .= '
    <tr>
        <td colspan="2" class="text-center">' . $first_number . '</td>
        <td colspan="17">' . $questions[$k - 1]["soru"] . '</td>
        <td colspan="5" class="text-center" style="background:#eee;font-weight:bold">' . $first_val . '</td>

        <td colspan="2" class="text-center">' . $second_number . '</td>
        <td colspan="17">' . $questions[$k + 14]["soru"] . '</td>
        <td colspan="5" class="text-center" style="background:#eee;font-weight:bold">' . $second_val . '</td>
    </tr>';
}
;

$html .= '<tr>

               <td colspan="48">
                   
                    1.7.5. Test, Deney Ve Muayene: İş ekipmanının periyodik kontrolü esnasında yapılan test deney ve
                 muayene (hidrostatik test, statik test,dinamik test, tahribatsız muayene yöntemleri ve benzeri)
                 sonuçları belirtilir.
            </td>
            </tr>

            <tr>
                <td colspan="48">
                    1.7.5. MADDE GEREĞİ: İŞ EKİPMANIN PERİYODİK KONTROLÜNDE YAPILAN DENEY VE MUAYENELER
                </td>
            </tr>

            <tr>
                <td colspan="48">
                    FONKSİYONEL TEST HİDROFOR HİDROSTATİK POMPA YAPILDI.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    1.7.6. İkaz Ve Öneriler: Yapılan periyodik kontrol sonucunda İG sağlığı ve güvenliği yönünden uygun
                    bulunmayan hususların belirlenmesi halinde, bunların nasıl uygun hale getirileceğine ilişkin
                    öneriler ile bu hususlar giderilmeden iş ekipmanlarının kullanımının güvenli olmayacağı belirtilir.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    1.7.6. MADDE GEREĞİ: İKAZ VE ÖNERİLER
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    DOLAP SİSTEMİNDE KRİTİK ALANDA YAPILAN BASINÇ TESTİNDE SORUN GÖZLENMEMİŞTİR. YANGIN DOLAPLARI
                    HİDROFOR VE POMPALAR İNCELENMİŞTİR. FİRMADA BULUNAN .. M3 ELEKTRİKLİ.. BAR ANA YANGIN POMPASI,... M3
                    DİZEL ... BAR YEDEK YANGIN POMPASI, ... M3 JOKEY POMPA,MEVCUT HALİ İLE UYGUNDUR. ... TONLUK SU TANKI
                    UYGUNDUR,... ADET YANGIN DOLABI UYGUNDUR.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    1.7.7. Sonuç Ve Kanaat: Raporun bu bölümünde periyodik kontrole tabi tutulan iş ekipmanının varsa
                    tespit edilen ve giderilen noksanlıklar açıklanarak, bir sonraki periyodik kontrole kadar geçecek
                    süre içerisinde görevini güvenli bir şekilde yapıp yapamayacağını açıkça belirtilir.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    1.7.7. MADDE GEREĞİ:SONUÇ VE KANAAT
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    6331 Sayılı Kanun gereği çıkartılan İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik şartları
                    Yönetmeliğine ve BİNALARDA YANGINDA KORUNMA YÖNETMELİĞİ Projede belirtilen kriterlere uygun olup
                    olmadığının belirlenmesine yönelik olarak yapılan. Ayrıca TS 9811, TS EN 671-3, TS EN 12416-1 + A2,
                    TS EN 12416-2 + A1,TS EN 12845 + A2 standartlarında belirtilen kritelere uygun olarak yapılan
                    Periyodik Kontrole göre YANGIN SÖNDÜRME SİSTEMİ 1 YIL SÜREYLE KULLANIMA UYGUNDUR. Uygunluğunun
                    devamlılığından işveren sorumludur.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    1.7.8. ONAY: Bu bölümde periyodik kontrolleri yapmaya yetkili kişinin/kişilerin kimlik bilgileri,
                    mesleği, diploma tarihi ve numarasına ilişkin bilgiler, Bakanlık kayıt numarası ile raporun kaç
                    nüsha olarak düzenlendiğini belirterek, imza altına alınır.Yukarıdaki bilgilerin veya yetkili
                    kişinin imzasının bulunmadığı raporlar geçersizdir.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    6331 SAYILI KANUNUN "İŞ EKİPMANLARININ KULLANIMINDA SAĞLIK VE GÜVENLİK ŞARTLARI YÖNETMELİĞİ" GEREĞİ
                    HAZIRLANAN BU RAPOR İKİ NÜSHA VE İKİ SAYFA OLARAK TANZİM EDİLMİŞ VE AŞAĞIDAKİ SERİ NUMARISI İLE
                    KAYIT ALTINA ALINARAK ONAYLANMIŞTIR.UYGUNLUĞUNUN DEVAMLILIĞINDAN İŞ VEREN SORUMLUDUR.
                </td>
            </tr>
            <tr>
                <td colspan="48">
                    <strong>EKLER:</strong>
                </td>
            </tr>';


while ($files = $attach_query->fetch(PDO::FETCH_ASSOC)) {
    $attach_name = $files["fileDescription"];
    $html .= '<tr>
                    <td colspan="48">
                        ' . $attach_name . '
                    </td>
                </tr>';
}

$html .= '<tr>
                <td colspan="24">
                <strong>REVİZYON TARİHİ:</strong>
                </td>
                <td colspan="24">
                ' . $report["update_time"] . '
                </td>
            </tr>
            <tr>

                <td colspan="12">
                    KONTROL TARİHİ :
                </td>
                <td colspan="12">
                   ' . $report["control_date"] . '
                </td>
                <td colspan="12">
                    BİR SONRAKİ KONTROL TARİHİ :
                </td>
                <td colspan="12">
                ' . $report["next_control_date"] . '
                </td>
            </tr>

            <tr>
                <td colspan="24">
                    KONTROLÜ YAPAN MAKİNA MÜHENDİSİ
                </td>
                <td rowspan="5" colspan="24" class="text-center">
                    ONAY
                </td>
            </tr>
            <tr>
                <td colspan="12">
                    DİPLOMA NO:
                </td>
                <td colspan="12">
                ' . $controller["ekipnetno"] . '
                </td>
     
            </tr>

            <tr>
                <td colspan="12">
                    YETKİNLİK NO:
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
                <td colspan="24" class="text-center">
                    <div style="margin:10px">
                        ONAY
                    
                    </div>
                </td>
           
             </tr>
         
             

        </tbody>
    </table>

    <!-- SAYFA BÖLME -->
    <div class="page-break"></div>

    <!-- İKİNCİ SAYFA İÇERİĞİ -->
  <div class="">
    <table >
        <tbody>
            <tr colspan="48">
                <td rowspan="" colspan="12">
                    <img src="' . toBase64('src/images/logo.png') . '" width="180px" id="logo" alt="company logo">
                </td>
                <td rowspan="" colspan="21" class="form-head">
                    <strong>MEKANİK TESİSAT PERİYODİK KONTROL RAPORU</strong>
                    <p class="m-0">
                        EK1 YANGIN DOLAP KONTROL LİSTESİ
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
            <tr colspan="48" class="text-center" style="font-size:6px">
                <td colspan="4">Sıra</td>
                <td colspan="4" style="width:100px">CİHAZIN CİNSİ</td>
                <td colspan="4" style="width:100px">BULUNDUĞU KISIM</td>

                <td colspan="2">ÖZELLİKLERİ (MT)</td>
                <td colspan="2" style="max-width:42px">KONTROL TARİHİ</td>
                <td colspan="2" style="max-width:42px">BİR SONRAKİ KONTROL TARİHİ</td>
                <td colspan="2" style="max-width:42px">VANA UYGUN MU?</td>
                <td colspan="3" style="max-width:58px">HORTUM BAĞLANTILARI UYGUN MU?</td>
                <td colspan="2" style="max-width:42px">LEVHA UYGUN MU ?</td>
                <td colspan="2" style="max-width:42px">PASLANMA VAR MI ?</td>
                <td colspan="3" style="max-width:42px">KİLİT UYGUN MU?</td>
                <td colspan="3" style="max-width:58px">HORTUM DURUMU UYGUN MU ?</td>

                <td colspan="5" style="max-width:58px">BULUNDUĞU HATTAKİ BASINÇ DEĞERİ NEDİR?</td>
                <td colspan="5" style="max-width:50px">NOZUL DURMU UYGUN MU?</td>
                <td colspan="5" style="width:120px">AÇIKLAMA</td>

            </tr>';

// Durum kontrol fonksiyonu (Büyük/Küçük harf ve boşluk duyarsız)
function robustStatusCheck($val, $type = 1) {
    if ($val === null) $val = "";
    $val = trim(strtoupper($val));
    if ($type == 2) { // VAR/YOK tipi için
        return ($val == "YOK" || $val == "0") ? "YOK" : "VAR";
    }
    // UYGUN/UYGUN DEĞİL tipi için
    return ($val == "1" || $val == "UYGUN" || $val == "EVET" || $val == "OK") ? "UYGUN" : "UYGUN DEĞİL";
}

$sira = 1;
while ($closets = $closet_query->fetch(PDO::FETCH_ASSOC)) {

    $vana_durum             = robustStatusCheck($closets["vana_durum"]);
    $hortum_baglanti_durum  = robustStatusCheck($closets["hortum_baglanti_durum"]);
    $levha_durum            = robustStatusCheck($closets["levha_durum"]);
    $pas_durum              = robustStatusCheck($closets["pas_durum"], 2);
    $kilit_durum            = robustStatusCheck($closets["kilit_durum"]);
    $hortum_durum           = robustStatusCheck($closets["hortum_durum"]);
    $nozul_durum            = robustStatusCheck($closets["nozul_durum"]);


    // Tarih formatlama (30.03 \n 2025 şeklinde)
    $c_date = $closets["control_date_closet"];
    if (strlen($c_date) > 5 && strpos($c_date, '.') !== false) {
        $parts = explode('.', $c_date);
        if (count($parts) == 3) {
            $c_date = $parts[0] . '.' . $parts[1] . '<br/>' . $parts[2];
        }
    }

    $n_date = $closets["next_control_date_closet"];
    if (strlen($n_date) > 5 && strpos($n_date, '.') !== false) {
        $parts = explode('.', $n_date);
        if (count($parts) == 3) {
            $n_date = $parts[0] . '.' . $parts[1] . '<br/>' . $parts[2];
        }
    }

    $html .= '<tr colspan="48" class="text-center">
                <td colspan="4" style="width:40px" class="text-center">' . $sira . '</td>
                <td colspan="4" style="width:100px">' . $closets["cinsi"] . '</td>
                <td colspan="4" style="width:100px">' . $closets["bulundugu_kisim"] . '</td>

                <td colspan="2">' . $closets["ozellikler"] . '</td>
                <td colspan="2" style="min-width:60px">' . $c_date . '</td>
                <td colspan="2">' . $n_date . '</td>
                <td colspan="2">' . $vana_durum . '</td>
                <td colspan="3">' . $hortum_baglanti_durum . '</td>
                <td colspan="2">' . $levha_durum . '</td>
                <td colspan="2">' . $pas_durum . '</td>
                <td colspan="3">' . $kilit_durum . '</td>
                <td colspan="3">' . $hortum_durum . '</td>

                <td colspan="5">' . $closets["basinc_degeri"] . '</td>
                <td colspan="5">' . $nozul_durum . '</td>
                <td colspan="5">' . $closets["aciklama"] . '</td>
            </tr>';
    $sira++;
}
;
$html .= '  <tr>
                <td colspan="48" ><div style="margin-left: 20px">Kontrol Kriterleri</div></td>
            </tr>

            <tr>
                <td colspan="48">
                    ' . $report["subNotes"] . '
                </td>
            </tr>
            <tr class="text-center">
                <td colspan="22">
                    <strong>
                        KONTROLÜ YAPAN
                    </strong>

                </td>
                <td colspan="26"> <strong>
                        ONAY
                    </strong> </td>
            </tr>
            <tr>
                <td colspan="22">
                    <div style="margin:10px">

                    </div>
                <td colspan="26">
                    <div style="margin:20px">

                    </div>
                </td>

            </tr>

        </tbody>
    </table>
</div>
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
                    <strong>MEKANİK TESİSAT PERİYODİK KONTROL RAPORU</strong>
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
 
    <!-- ÜÇÜNCÜ SAYFA İÇERİK -->';
}
;


$html .= '</body>

</html>'


;
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
// $dompdf->setPaper('A4', "L");


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
