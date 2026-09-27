<?php
header('Content-Type: application/json; charset=utf-8');

$cacheFile = dirname(__DIR__, 2) . '/cache/tcmb_rates.json';
$cacheDir = dirname($cacheFile);
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

// 30 dakikalık önbellek kontrolü
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
    $cached = @file_get_contents($cacheFile);
    if ($cached) {
        echo $cached;
        exit;
    }
}

try {
    $ch = curl_init('https://www.tcmb.gov.tr/kurlar/today.xml');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    $xmlText = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($xmlText !== false && $httpCode === 200) {
        $doviz = @simplexml_load_string($xmlText);
        if ($doviz && isset($doviz->Currency[0])) {
            $dolar = array(
                "alis" => (string)$doviz->Currency[0]->BanknoteBuying,
                "satis" => (string)$doviz->Currency[0]->BanknoteSelling,
                "alis_efektif" => (string)$doviz->Currency[0]->ForexBuying,
                "satis_efektif" => (string)$doviz->Currency[0]->ForexSelling
            );

            $euro = array(
                "alis" => (string)$doviz->Currency[3]->BanknoteBuying,
                "satis" => (string)$doviz->Currency[3]->BanknoteSelling,
                "alis_efektif" => (string)$doviz->Currency[3]->ForexBuying,
                "satis_efektif" => (string)$doviz->Currency[3]->ForexSelling
            );

            $response = json_encode(array("dolar" => $dolar, "euro" => $euro), JSON_UNESCAPED_UNICODE);
            @file_put_contents($cacheFile, $response);
            echo $response;
            exit;
        }
    }
} catch (\Throwable $e) {}

// Hata durumunda eski önbellek varsa onu kullan
if (file_exists($cacheFile)) {
    $cached = @file_get_contents($cacheFile);
    if ($cached) {
        echo $cached;
        exit;
    }
}

// Fallback varsayılan
echo json_encode([
    "dolar" => ["alis" => "0", "satis" => "0", "alis_efektif" => "0", "satis_efektif" => "0"],
    "euro" => ["alis" => "0", "satis" => "0", "alis_efektif" => "0", "satis_efektif" => "0"]
]);

