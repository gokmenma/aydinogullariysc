<?php

// Terminal için basit bir komut, web arayüzleri için gerekli değil.
function log_mes($message) {
    $message = date("H:i:s") . " - $message - " . PHP_EOL;
    print($message);
    flush();
    ob_flush();
}

// Değişkenler 
# Bulutfon panelinden alcağınız master token
$token = "5Upn_CHBf8snKMzAgUC_TgFhnOHDiHFitRZud5G4owdoARsixtkcWcbIIqdPsYdYb54";

# Bulutfon üzerinden onaylattığınız sms başlığı
$title = "FDGHJCCDXX";

# Formdan gelen alıcı listesi
$receivers = array('905079747767');

# Formdan gelen mesaj alanı
$message = "Mesaj icerigisaa"; 

# Red linki
$reject_link = TRUE;

# Success http status codes
# API'den 200 veya 201 dönüyorsa istek başarılırdır.
$success_http_status_code = array(200, 201);

# API'den 400, 401, 422 dönerse
$error_http_status_code = array(400, 401, 422);

// Curl ile SMS Gönder

# Data
$data = array(
    'title' => $title,
    'content' => $message,
    'receivers' => $receivers,
    'reject_link' => $reject_link
);
$data_string = json_encode($data);


# Curl oturumunu başlattık
$ch = curl_init(); 

// cURL Ayarları

# SMS gönderimi için kullanacağımız API adresi
curl_setopt($ch, CURLOPT_URL, 'http://api.bulutfon.com/v2/sms/messages?apikey=' . $token); 

# Burada curl post kullanacagımızı belirttik 1 yerine true de denebilir
curl_setopt($ch, CURLOPT_POST, 1); 

# Sonuçları true false yerine veri olarak dondur
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

# SSL'i aktif et
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

# Curlda post tipinin JSON oldugunu soyluyor.
curl_setopt($ch, CURLOPT_HTTPHEADER,
    array(
        'Content-Type:application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($data_string)
    )
);

#  Burada ise göndereceğimiz parametreleri belirtiyoruz.
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); 

# Çalıştır ve verileri $response_data değişkenine yaz
$response_json = curl_exec($ch);

# Statü kodunu al
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

# Curl oturumunu kapat
curl_close($ch);

# $response_data'yı PHP array'e çevir.
$response_array = json_decode($response_json, true);

# SMS gitti mi?
if (in_array($http_status, $success_http_status_code)) {
    log_mes("SMS gitti.");
    log_mes("SMS ID: " . $response_array['id']);
    log_mes("SMS Icerik: " . $response_array['content']);
    log_mes("SMS Gelecek tarihli mi?: " . $response_array['is_future_sms']);
    log_mes("SMS Tarih: " . $response_array['send_date']);
    log_mes(var_dump($response_array));
} elseif (in_array($http_status, $error_http_status_code)) {
    log_mes("SMS gitmedi.");
    log_mes("Hata kodu: " . $response_array['error']['code']);
    log_mes("Hata basligi: " . $response_array['error']['title']);
    log_mes("Hata mesaji: " . $response_array['error']['message']);
} else {
    log_mes("Genel bir hata var. Belki 500 hatası olabilir.");
}