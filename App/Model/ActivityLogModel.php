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
     * Belirli bir kullanıcının aktivitelerini filtreli ve sayfalı olarak getirir.
     *
     * @param int $userId
     * @param array $filters ['event_type' => string, 'module' => string, 'search' => string]
     * @param int $page
     * @param int $perPage
     * @return array ['items' => array, 'total' => int, 'pages' => int, 'current_page' => int, 'per_page' => int]
     */
    public function getUserActivities(int $userId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["COALESCE(NULLIF(l.user_id, 0), l.author) = :user_id"];
        $params = [':user_id' => $userId];

        // Olay türü filtresi
        if (!empty($filters['event_type'])) {
            $ev = $filters['event_type'];
            if ($ev === 'operations') {
                $where[] = "(l.event_type IN ('create', 'update', 'delete', 'export', 'download', 'copy', 'status_change', 'upload') OR (l.event_type IS NULL AND l.action NOT LIKE '%giriş%' AND l.action NOT LIKE '%view%' AND l.message NOT LIKE '%giriş%'))";
            } elseif ($ev === 'login') {
                $where[] = "(l.event_type IN ('login', 'logout') OR l.action IN ('login', 'logout') OR l.summary LIKE '%giriş yaptı%')";
            } elseif ($ev === 'view') {
                $where[] = "(l.event_type = 'view' OR l.action = 'view' OR l.summary LIKE 'Sayfa görüntülendi%')";
            } else {
                $where[] = "l.event_type = :event_type";
                $params[':event_type'] = $ev;
            }
        }

        // Modül filtresi
        if (!empty($filters['module'])) {
            $where[] = "l.module = :module";
            $params[':module'] = $filters['module'];
        }

        // Arama filtresi
        if (!empty($filters['search'])) {
            $where[] = "(l.summary LIKE :search OR l.action LIKE :search OR l.message LIKE :search OR l.module LIKE :search OR l.ip_address LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);

        // Toplam kayıt sayısı
        $countSql = "SELECT COUNT(*) FROM {$this->table} l WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        // Sayfa listesi
        $dataSql = "SELECT l.*, 
                           COALESCE(NULLIF(u.username, ''), 'Ben') as username,
                           u.Unvan,
                           p.p_title as role_title
                    FROM {$this->table} l
                    LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                    LEFT JOIN perms p ON u.permission = p.id
                    WHERE {$whereSql}
                    ORDER BY l.id DESC
                    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->execute();
        $items = $dataStmt->fetchAll(PDO::FETCH_OBJ);

        $totalPages = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'pages' => max(1, $totalPages),
            'current_page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Belirli bir kullanıcının son aktivitelerini liste olarak getirir (DataTable için).
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserActivitiesList(int $userId, int $limit = 1000): array
    {
        $limit = max(1, min(5000, $limit));
        $sql = "SELECT l.*, 
                       COALESCE(NULLIF(u.username, ''), 'Ben') as username,
                       u.Unvan,
                       p.p_title as role_title
                FROM {$this->table} l
                LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
                LEFT JOIN perms p ON u.permission = p.id
                WHERE COALESCE(NULLIF(l.user_id, 0), l.author) = ?
                ORDER BY l.id DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Kullanıcının aktivite istatistiklerini hesaplar.
     */
    public function getUserActivityStats(int $userId): array
    {
        // Toplam aktivite
        $st1 = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ?");
        $st1->execute([$userId]);
        $total = (int) $st1->fetchColumn();

        // Bugün yapılanlar
        $st2 = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ? AND (created_at >= CURDATE() OR dates = DATE_FORMAT(NOW(), '%d-%m-%Y'))");
        $st2->execute([$userId]);
        $today = (int) $st2->fetchColumn();

        // Başarılı girişler
        $st3 = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ? AND (event_type = 'login' OR action = 'login' OR summary LIKE '%giriş yaptı%')");
        $st3->execute([$userId]);
        $logins = (int) $st3->fetchColumn();

        // Veri işlemleri (oluşturma / düzenleme / silme vb.)
        $st4 = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ? AND (event_type IN ('create', 'update', 'delete', 'export', 'download', 'copy', 'status_change', 'upload'))");
        $st4->execute([$userId]);
        $operations = (int) $st4->fetchColumn();

        return [
            'total' => $total,
            'today' => $today,
            'logins' => $logins,
            'operations' => $operations,
        ];
    }

    /**
     * Kullanıcının son oturum açma (login) kaydını getirir.
     */
    public function getUserLastLogin(int $userId): ?object
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ? AND (event_type = 'login' OR action = 'login' OR summary LIKE '%giriş yaptı%') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        return $res ?: null;
    }

    /**
     * Kullanıcının işlem yaptığı benzersiz modülleri getirir.
     */
    public function getUserModules(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT module FROM {$this->table} WHERE COALESCE(NULLIF(user_id, 0), author) = ? AND module IS NOT NULL AND module != '' ORDER BY module ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
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
