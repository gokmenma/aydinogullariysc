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
// Session başlat (eğer aktif değilse)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı config ve functions yükle
$root = __DIR__;
require_once $root . '/configs/config.php';
require_once $root . '/configs/functions.php';

// Composer autoload
require_once $root . '/vendor/autoload.php';

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
