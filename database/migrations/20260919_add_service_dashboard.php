<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

echo "Servis Dashboard migration başlatılıyor...\n";

try {
    $sql = file_get_contents(__DIR__ . '/20260919_add_service_dashboard.sql');
    
    // Çoklu sorguları sırayla çalıştır
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmtSql) {
        if (!empty($stmtSql)) {
            $ac->exec($stmtSql);
        }
    }
    
    echo "Migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "Hata oluştu: " . $e->getMessage() . "\n";
    exit(1);
}
