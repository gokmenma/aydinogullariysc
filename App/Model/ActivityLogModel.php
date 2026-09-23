<?php
namespace App\Model;

use App\Model\BaseModel;
use PDO;

class ActivityLogModel extends BaseModel
{
    protected $table = 'logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Son genel sistem aktivitelerini getirir (Login işlemleri hariç).
     *
     * @param int $limit
     * @return array
     */
    public function getLatestActivities(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT l.*, 
                       COALESCE(NULLIF(u.username, ''), IF(COALESCE(NULLIF(l.user_id, 0), l.author) = 0, 'Sistem', CONCAT('Kullanıcı #', COALESCE(NULLIF(l.user_id, 0), l.author)))) as username,
                       u.Unvan,
                       p.p_title as role_title
                FROM {$this->table} l 
                LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                LEFT JOIN perms p ON u.permission = p.id
                WHERE (l.event_type != 'login' OR l.event_type IS NULL)
                ORDER BY l.id DESC 
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Son kullanıcı oturum açma (login) kayıtlarını getirir.
     *
     * @param int $limit
     * @return array
     */
    public function getLatestLogins(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT l.*, 
                       COALESCE(NULLIF(u.username, ''), IF(COALESCE(NULLIF(l.user_id, 0), l.author) = 0, 'Sistem', CONCAT('Kullanıcı #', COALESCE(NULLIF(l.user_id, 0), l.author)))) as username,
                       u.Unvan,
                       p.p_title as role_title
                FROM {$this->table} l 
                LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                LEFT JOIN perms p ON u.permission = p.id
                WHERE (l.event_type = 'login' OR l.action = 'login')
                ORDER BY l.id DESC 
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Olay türü (event_type) için Türkçe etiket döndürür.
     */
    public static function getEventLabel(?string $eventType): string
    {
        $labels = [
            'view' => 'Sayfa Ziyareti',
            'login' => 'Giriş',
            'logout' => 'Çıkış',
            'create' => 'Oluşturma',
            'update' => 'Güncelleme',
            'delete' => 'Silme',
            'export' => 'Dışa Aktarma',
            'copy' => 'Kopyalama',
            'status_change' => 'Durum Değişikliği',
            'upload' => 'Dosya Yükleme',
            'download' => 'Dosya İndirme',
            'error' => 'Hata',
        ];

        return $labels[$eventType ?? ''] ?? ($eventType ? ucfirst($eventType) : 'İşlem');
    }

    /**
     * Olay türü için FontAwesome ikonu döndürür.
     */
    public static function getEventIcon(?string $eventType): string
    {
        $icons = [
            'view' => 'fa fa-eye',
            'login' => 'fa fa-sign-in',
            'logout' => 'fa fa-sign-out',
            'create' => 'fa fa-plus-circle',
            'update' => 'fa fa-pencil',
            'delete' => 'fa fa-trash',
            'export' => 'fa fa-download',
            'copy' => 'fa fa-clone',
            'status_change' => 'fa fa-refresh',
            'upload' => 'fa fa-upload',
            'download' => 'fa fa-download',
            'error' => 'fa fa-exclamation-circle',
        ];

        return $icons[$eventType ?? ''] ?? 'fa fa-history';
    }

    /**
     * Olay türü için rozet / badge sınıfı döndürür.
     */
    public static function getEventBadgeClass(?string $eventType): string
    {
        $badges = [
            'view' => 'soft-blue',
            'login' => 'soft-emerald',
            'logout' => 'soft-amber',
            'create' => 'soft-emerald',
            'update' => 'soft-blue',
            'delete' => 'soft-rose',
            'export' => 'soft-cyan',
            'copy' => 'soft-purple',
            'status_change' => 'soft-purple',
            'upload' => 'soft-blue',
            'download' => 'soft-emerald',
            'error' => 'soft-rose',
        ];

        return $badges[$eventType ?? ''] ?? 'soft-blue';
    }

    /**
     * Modül için etiket döndürür.
     */
    public static function getModuleLabel(?string $module): string
    {
        $modules = [
            'services' => 'Servisler',
            'service' => 'Servisler',
            'offers' => 'Teklifler',
            'offer' => 'Teklifler',
            'customers' => 'Firmalar',
            'customer' => 'Firmalar',
            'purchases' => 'Satın Alma',
            'purchase' => 'Satın Alma',
            'kesif' => 'Keşifler',
            'products' => 'Ürünler',
            'reports' => 'Raporlar',
            'report' => 'Raporlar',
            'auth' => 'Kimlik Doğrulama',
            'backup' => 'Yedekleme',
            'navigation' => 'Gezinme',
            'tasks' => 'Görevler',
            'task' => 'Görevler',
            'personnel' => 'Personel',
            'settings' => 'Ayarlar',
        ];

        return $modules[$module ?? ''] ?? ($module ? ucfirst($module) : 'Sistem');
    }

    /**
     * Zamanı 'X dk önce' formatında döndürür.
     */
    public static function formatRelativeTime(?string $datetimeStr, ?string $fallbackDate = null, ?string $fallbackClock = null): string
    {
        if (empty($datetimeStr) && !empty($fallbackDate)) {
            $datetimeStr = $fallbackDate . (!empty($fallbackClock) ? ' ' . $fallbackClock : '');
        }

        if (empty($datetimeStr)) {
            return '-';
        }

        $time = strtotime($datetimeStr);
        if (!$time) {
            return $datetimeStr;
        }

        $diff = time() - $time;
        if ($diff < 0) {
            return 'Az önce';
        }
        if ($diff < 60) {
            return 'Az önce';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' dk önce';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' saat önce';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' gün önce';
        }

        return date('d.m.Y H:i', $time);
    }
}
