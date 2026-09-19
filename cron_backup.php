<?php
/**
 * Aydınoğulları YSC - Otomatik Yedekleme Cron Betiği
 * 
 * Kullanım (CLI / Cron Job):
 *   /opt/lampp/bin/php /opt/lampp/htdocs/aydinogullariysc_trae/cron_backup.php
 * 
 * Web Üzerinden Tetikleme (Harici Cron / Webhook):
 *   https://siteadi.com/cron_backup.php?token=GIZLI_TOKEN&type=full
 */

require_once __DIR__ . '/bootstrap.php';

use App\Service\BackupService;
use App\Model\BackupModel;

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
$backupType = 'full';

if ($isCli) {
    // CLI argümanlarını oku
    global $argv;
    if (isset($argv[1])) {
        if (in_array($argv[1], ['full', 'db', 'files'], true)) {
            $backupType = $argv[1];
        }
    }
} else {
    // Web üzerinden çağrılıyorsa token doğrulaması zorunludur
    $model = new BackupModel();
    $settings = $model->getBackupSettings();
    $cronToken = $settings['backup_cron_token'] ?? '';
    $receivedToken = $_GET['token'] ?? '';

    if (empty($cronToken) || empty($receivedToken) || !hash_equals($cronToken, $receivedToken)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Yetkisiz erişim. Geçersiz cron token.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['type']) && in_array($_GET['type'], ['full', 'db', 'files'], true)) {
        $backupType = $_GET['type'];
    }

    header('Content-Type: application/json; charset=utf-8');
}

try {
    $backupService = new BackupService();
    $result = $backupService->runBackup($backupType);

    if ($isCli) {
        if ($result['success']) {
            echo "[" . date('Y-m-d H:i:s') . "] YEDEKLEME BAŞARILI!\n";
            echo "Dosya: " . $result['file_name'] . " (" . $result['file_size_formatted'] . ")\n";
            echo "Süre: " . $result['duration_sec'] . " sn\n";
            echo "Harici Aktarım: " . $result['remote_status'] . "\n";
            echo "E-Posta: " . $result['mail_status'] . "\n";
            echo "Detaylar:\n - " . implode("\n - ", $result['messages']) . "\n";
            exit(0);
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] YEDEKLEME BAŞARISIZ: " . ($result['error'] ?? 'Bilinmeyen hata') . "\n";
            exit(1);
        }
    } else {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    if ($isCli) {
        echo "[" . date('Y-m-d H:i:s') . "] KRİTİK HATA: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
