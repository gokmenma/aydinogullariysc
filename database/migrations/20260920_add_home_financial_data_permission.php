<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

echo "Ana sayfa finansal veri yetkisi migration'ı başlatılıyor...\n";

try {
    $sql = file_get_contents(__DIR__ . '/20260920_add_home_financial_data_permission.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        $ac->exec($statement);
    }

    echo "Migration başarıyla tamamlandı.\n";
} catch (Throwable $exception) {
    echo "Hata oluştu: " . $exception->getMessage() . "\n";
    exit(1);
}
