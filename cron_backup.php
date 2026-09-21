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
$userId = null;
$logId = null;

if ($isCli) {
    // CLI argümanlarını oku: argv[1] = type, argv[2] = userId, argv[3] = logId
    global $argv;
    if (isset($argv[1])) {
        if (in_array($argv[1], ['full', 'db', 'files'], true)) {
            $backupType = $argv[1];
        }
    }
    if (isset($argv[2]) && is_numeric($argv[2]) && (int)$argv[2] > 0) {
        $userId = (int)$argv[2];
    }
    if (isset($argv[3]) && is_numeric($argv[3]) && (int)$argv[3] > 0) {
        $logId = (int)$argv[3];
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
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];
    }
    if (isset($_GET['log_id']) && is_numeric($_GET['log_id'])) {
        $logId = (int)$_GET['log_id'];
    }
}

@ignore_user_abort(true);
@set_time_limit(0);
@ini_set('memory_limit', '1024M');

if (!$isCli) {
    // Validated token!
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    $resJson = json_encode([
        'status' => 'started',
        'message' => 'Yedekleme işlemi başlatıldı ve arka planda yürütülüyor.'
    ], JSON_UNESCAPED_UNICODE);
    
    header('Content-Length: ' . strlen($resJson));
    header('Connection: close');
    echo $resJson;

    // FastCGI / LiteSpeed bağlantıyı hemen sonlandırıp arka planda devam etsin
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } elseif (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
    } else {
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level()) {
            ob_end_flush();
        }
        flush();
    }
}

try {
    $backupService = new BackupService();
    $result = $backupService->runBackup($backupType, $userId, $logId);

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
        exit(0);
    }
} catch (\Throwable $e) {
    if ($isCli) {
        echo "[" . date('Y-m-d H:i:s') . "] KRİTİK HATA: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        exit(1);
    }
}
