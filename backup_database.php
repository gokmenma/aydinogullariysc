<?php
/**
 * Tam veritabanı yedeği alan PHP betiği
 * Örnek: backup_2025-10-15_09-30-22.sql
 */

require_once __DIR__ . '/bootstrap.php';

use App\Service\BackupService;

$database = defined('HOSTDATABASE') ? HOSTDATABASE : 'aydinogu_aydinogullari_yeni';
echo "=== Aydınoğulları YSC Veritabanı Yedekleme ({$database}) ===\n";

$backupService = new BackupService();
$result = $backupService->runBackup('db');

if ($result['success']) {
    echo "✅ Veritabanı yedeği başarıyla alındı ve arşivlendi.\n";
    echo "📁 Dosya: " . $result['file_name'] . " (" . $result['file_size_formatted'] . ")\n";
    echo "⏱ Süre: " . $result['duration_sec'] . " sn\n";
    echo "☁️ Bulut Aktarım Durumu: " . ($result['remote_status'] ?? 'none') . "\n";
    if (!empty($result['messages'])) {
        foreach ($result['messages'] as $msg) {
            echo "   • {$msg}\n";
        }
    }
} else {
    echo "❌ Hata: " . ($result['error'] ?? 'Bilinmeyen hata') . "\n";
}
?>
