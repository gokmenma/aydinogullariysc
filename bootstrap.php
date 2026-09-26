<?php
/**
 * Application Bootstrap
 * 
 * Merkezi başlangıç noktası - tüm uygulamalar bunu yüklesin
 * Kullanım: require_once 'bootstrap.php';
 */

// Error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ob_start();
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
// Session cookie ayarları session_start() öncesinde uygulanmalıdır.
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Kimliği doğrulanmış oturumları 2 saat hareketsizlikten sonra sonlandır.
$now = time();
if (!empty($_SESSION['login']) && isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > 7200) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = $now;

// Veritabanı config ve functions yükle
$root = __DIR__;
require_once $root . '/configs/config.php';
require_once $root . '/configs/functions.php';

// Composer autoload
require_once $root . '/vendor/autoload.php';

// Tanılama ve tek-seferlik bakım betikleri web üzerinden çalıştırılamaz.
if (PHP_SAPI !== 'cli') {
    $cliOnlyScripts = [
        'backup_database.php', 'update_db.php', 'debug_db.php', 'check_db.php',
        'check_writable.php', 'verify_path.php', 'test_save.php', 'test_update.php',
        'test_report_control_model.php',
    ];
    if (in_array(basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')), $cliOnlyScripts, true)) {
        http_response_code(404);
        exit;
    }
}

// Her oturum için merkezi CSRF token üret.
\App\Helper\Security::csrf();

// Yüklenen tüm dosyaları, uygulama kodu diske taşımadan önce doğrula.
if (!empty($_FILES)) {
    try {
        \App\Helper\UploadSecurity::validateAll($_FILES);
    } catch (\RuntimeException $e) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// API isteklerinde oturum, yetki ve CSRF denetimini tek noktada uygula.
\App\Helper\ApiSecurity::enforce($ac, $root);

// Bakım modu tüm web giriş noktalarında merkezi olarak uygulanır.
\App\Helper\MaintenanceMode::enforce($ac, $root);

// Global $ac (PDO) hazırla - config.php'de tanımlanmış
global $ac;


// Yardımcı fonksiyonlar
use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
use App\Logging\LoggerFactory;


/**
 * Uygulama Başlatma Tamam
 * 
 * Artık şunlara erişebilirsiniz:
 * - Global $ac (PDO database connection)
 * - \getLogger("module") fonksiyonu
 * - Date, Security, Helper sınıfları
 * - Session ve permtrue() fonksiyonları
 * - Composer PSR-4 autoload (App\* namespace)
 */

// Log: Application başlatıldı (gerekirse)
// $logger = \getLogger("app");
// $logger->debug('Bootstrap yüklendi', ['timestamp' => microtime(true)]);
