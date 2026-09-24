<?php
function toBase64($image )
{
    
    $data = base64_encode(file_get_contents($image));
    return 'data:' . mime_content_type($image) . ';base64,' . $data;
}
//ini_set('display_errors','On');
//error_reporting(E_ALL);
// require __DIR__.'/../../dompdf/autoload.php';
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$pid = $_GET["pid"];
// Dompdf nesnesini oluştur
$css = '

  <style>
  /* Header */
    @page {
      margin-top: 80px; /* Header yüksekliği kadar */
      margin-bottom: 30px; /* Footer yüksekliği kadar */
    }

    header {
      position: fixed;
      top: -60px; /* Header yüksekliği kadar */
      left: 0;
      right: 0;
      height: 50px; /* Header yüksekliği */
      text-align: center;
      border-bottom: 3px solid #303030;
      
    }

    /* Footer */
    footer {
      position: fixed;
      bottom: -0px; /* Footer yüksekliği kadar */
      left: 0;
      right: 0;
      height: 50px; /* Footer yüksekliği */
      text-align: center;
      border-top: 3px solid #303030;
    }
  .clearfix:after {
    content: "";
    display: table;
    clear: both;
  }
  
  a {
    color: #303030;
    text-decoration: none;
  }
  
  body {
    position: relative;
    width: 21cm;  
    height: 29.7cm; 
    width: 100%;  
    height: 100%; 
    margin: 0 auto; 
    color: #555555;
    background: #FFFFFF; 
    font-family: Dejavu Serif; 
    font-size: 11px; 
    margin-bottom: 30px;
  }
  
 
  
  #logo {
    float: left;
    margin-top: 1px;
  }
  
  #logo img {
    height: 50px;
  }
  
  #company {
    float: right;
    text-align: right;
  }
  
  
  #details {
    margin-top: 30px;
    margin-bottom: 30px;
    border-bottom: 3px solid #303030;
  }
  
  #client {
    padding-left: 6px;
    border-left: 6px solid #303030;
    float: left;
  }
  
  #client .to {
    font-size: 1.2em;
    font-weight: bold;
    color: #303030;
  }
  
  h2.name {
  
    font-size: 1.1em;
    font-weight: bold;
    margin: 0;
  }
  
  #invoice {
    float: right;
    text-align: right;
    margin-bottom: 20px;
  }
  
  #invoice h1 {
    color: #303030;
    font-size: 1.2em;
    line-height: 1em;
    font-weight: bold;
    margin: 0  0 10px 0;
  }
  
  #invoice .date {
    font-size: 1.1em;
    color: #000000;
    font-weight: normal;
  
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
  }
  
  table th,
  table td {
    padding: 10px;
    background: #EEEEEE;
    text-align: center;
    border-bottom: 1px solid #FFFFFF;
  }
  
  table th {
    white-space: nowrap;        
    font-weight: normal;
  }
  
  table td {
    text-align: center;
  }
  
  table td h3{
    color: #303030;
    font-size: 1.2em;
    font-weight: bold;
    margin: 0 0 0.2em 0;
  }
  
  table .no {
    color: #ffffff;
    font-size: 1.2em;
    font-weight: bold;
    background: #303030;
  }
  
  table .desc {
    text-align: left;
  
  }
  
  table .unit {
    background: #DDDDDD;
  }
  
  table .qty {
  }
  
  table .total {
    background: #57B223;
    color: #FFFFFF;
  }
  
  table td.unit,
  table td.qty,
  table td.total {
    font-size: 1.2em;
  }
  
  table tbody tr:last-child td {
    border: none;
  }
  
  table tfoot td {
    padding: 10px 20px;
    background: #FFFFFF;
    border-bottom: none;
    font-size: 1.1em;
    white-space: nowrap; 
    border-top: 1px solid #AAAAAA; 
  }
  
  table tfoot tr:first-child td {
    border-top: none; 
  }
  
  table tfoot tr:last-child td {
    color: #57B223;
    font-size: 1.2em;
    border-top: 1px solid #57B223; 
  
  }
  
  table tfoot tr td:first-child {
    border: none;
  }
  
  #thanks{
    font-size: 2em;
    margin-bottom: 50px;
  }
  
  #notices{
    padding-left: 6px;
    border-left: 6px solid #303030;  
  }
  
  #notices .notice {
    font-size: 1.2em;
  }
  
  
  </style>
';
// Servis bilgileri
$sql = $ac->prepare("Select * from projects  WHERE id = ?");
$sql->execute(array($pid));
$result = $sql->fetch(PDO::FETCH_ASSOC);

// Firma Bilgi
$sql = $ac->prepare("SELECT * FROM customers WHERE id = ?");
$sql->execute(array($result['pcid']));
$row = $sql->fetch(PDO::FETCH_ASSOC);

$sql = $ac->prepare("Select * from users WHERE id = ?");
$sql->execute(array($result['pcreativer']));
$person = $sql->fetch(PDO::FETCH_ASSOC);


// HTML içeriği

// Başlangıç HTML kodu
$html = '<!DOCTYPE html>
<!DOCTYPE html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <title>Example 2</title>
    '.$css.'
    <link rel="stylesheet" href="style.css" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        <img src="' . toBase64('src/images/logo.png') . '" id="logo" alt="company logo">
      </div>
      <div id="company">
        
      </div>
    
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">FİRMA BİLGİLERİ</div>
          <h2 class="name">Firma İsmi</h2>
          <div class="address">Firma Adres</div>
          
        </div>
        <div id="invoice">
          <h1>TEKLİF BİLGİLERİ</h1>
          <div class="date">Teklif No: <b>1234</b> </div>
          <div class="date">Teklif Tarihi: <b>12.12.2023</b></div>
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0">
        <thead>
          <tr>
            <th class="no">S/N</th>
            <th class="desc" style="font-size:1.3em;"><b>ÜRÜN/ÜRÜNLER</b></th>
            <th class="unit"style="font-size:1.3em;"><b>FİYAT</b></th>
            <th class="qty"style="font-size:1.3em;"><b>MİKTAR</b></th>
            <th class="total"style="font-size:1.3em;"><b>TOPLAM</b></th>
          </tr>
        </thead>
        <tbody>';

// Satırı 30 kez ekle
for ($i = 1; $i <= 30; $i++) {
    $html .= '
          <tr>
            <td class="no">'.$i.'</td>
            <td class="desc"><h3>Ürün Kodu</h3>Aydınlatma Tesisatı</td>
            <td class="unit">$40.00</td>
            <td class="qty">20</td>
            <td class="total">$800.00</td>
          </tr>';
}

// Devam eden HTML kodu
$html .= '
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2"></td>
            <td colspan="2"><b>ARA TOPLAM</b></td>
            <td>$5,200.00</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2"><b>KDV 25%</b></td>
            <td>$1,300.00</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2"><b>GENEL TOPLAM<b></td>
            <td>$6,500.00</td>
          </tr>
        </tfoot>
      </table>
      
      <div id="notices">
        <div style="font-size: 1.4em;"><b>NOT:</b></div>
        <div class="notice">Eklenmek istenen not varsa buraya yazılabilir</div>
      </div>
    </main>
    <footer >
    <h2 class="name">' . mb_strtoupper(set('company_name')) . '</h2>
    <div>' . set('company_address') . '</div>
      <div>Tel: ' . set('company_phone1') . ' / ' . set('company_phone2') . '</div>
      <div><a href="' . set('panel_url') . '">' . set('panel_url') . '  /</a><a href="mailto:' . set('admin_mail') . '"> ' . set('admin_mail') . '</a></div>
      
    </footer>
  </body>
</html>';

// Sonuç olarak oluşan HTML kodunu görüntüle
echo $html;



$options = new Options();
$options->set('isPhpEnabled', true); // PHP kodlarının çalıştırılmasını etkinleştir

// PDF içeriğini oluştur
$dompdf = new Dompdf($options);

// HTML içeriğini PDF'ye ekle
$dompdf->loadHtml($html);

// PDF'nin boyutunu ve oryantasyonunu ayarla
$dompdf->setPaper('A4', 'portrait');

// PDF'yi oluştur
$dompdf->render();
ob_end_clean();
// PDF'yi göster veya dosyaya kaydet
$dompdf->stream("document.pdf", array("Attachment" => false));
?>
