<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

echo "Keşif Dashboard migration başlatılıyor...\n";

try {
    $sql = file_get_contents(__DIR__ . '/20260920_add_kesif_dashboard.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        $ac->exec($statement);
    }
    echo "Migration başarıyla tamamlandı.\n";
} catch (Throwable $e) {
    echo "Hata oluştu: " . $e->getMessage() . "\n";
    exit(1);
}
