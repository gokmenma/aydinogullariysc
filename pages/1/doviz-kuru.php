<?php

// XML dosyasını yükle
$doviz = simplexml_load_file('http://www.tcmb.gov.tr/kurlar/today.xml');

// Dolar bilgilerini al
$dolar = array(
    "alis" => (string)$doviz->Currency[0]->BanknoteBuying,
    "satis" => (string)$doviz->Currency[0]->BanknoteSelling,
    "alis_efektif" => (string)$doviz->Currency[0]->ForexBuying,
    "satis_efektif" => (string)$doviz->Currency[0]->ForexSelling
);

// Euro bilgilerini al
$euro = array(
    "alis" => (string)$doviz->Currency[3]->BanknoteBuying,
    "satis" => (string)$doviz->Currency[3]->BanknoteSelling,
    "alis_efektif" => (string)$doviz->Currency[3]->ForexBuying,
    "satis_efektif" => (string)$doviz->Currency[3]->ForexSelling
);

// JSON olarak verileri geri döndür
echo json_encode(array("dolar" => $dolar, "euro" => $euro));
?>

