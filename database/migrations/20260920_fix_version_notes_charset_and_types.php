<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

echo "Sürüm notları ve keşifler tabloları karakter seti ve tip düzeltme migration başlatılıyor...\n";

try {
    $sql = file_get_contents(__DIR__ . '/20260920_fix_version_notes_charset_and_types.sql');
    
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
