<?php

// Ortamı belirlemek için php_sapi_name() fonksiyonunu kullanın
if (php_sapi_name() == 'cli') {
    // CLI ortamı (yerel geliştirme ortamı)

    // Veritabanı bilgileri
    $dbname = 'aydinogullariysc';
    $backupDir = 'C:/xampp/htdocs/aydinogullariysc/files/backup/database/';
    $myCnfPath = 'C:/xampp/htdocs/aydinogullariysc/src/scripts/my.cnf';
} else {
    // Web sunucusu ortamı (sunucu ortamı)
    $backupDir = '/path/to/your/backup/directory/';
    $myCnfPath = '/path/to/my.cnf';
}

// Ortam değişkenini PHP içinde ayarla
putenv('MY_CNF_PATH=C:/xampp/htdocs/aydinogullariysc/src/scripts/my.cnf');

// Ortam değişkeninden my.cnf dosyasının yolunu al
$myCnfPath = getenv('MY_CNF_PATH');
if (!$myCnfPath) {
    die('MY_CNF_PATH ortam değişkeni ayarlanmamış.');
} else {
    echo "MY_CNF_PATH: $myCnfPath\n";
}

// mysqldump komutunu oluştur
$command = "mysqldump --defaults-extra-file=$myCnfPath $dbname > $backupFile";

// Komutu çalıştır
$output = null;
$return_var = null;
exec($command . ' 2>&1', $output, $return_var);

// Çıktıyı kontrol et
if ($return_var === 0) {
    echo "Veritabanı yedekleme başarılı: $backupFile";
} else {
    echo 'Veritabanı yedekleme başarısız.';
    echo "Hata kodu: $return_var";
    echo 'Çıktı: ' . implode("\n", $output);
}
?>