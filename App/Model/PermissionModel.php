<?php

namespace App\Model;

use PDO;
use App\Model\BaseModel;
use App\Logging\LoggerFactory;

class PermissionModel extends BaseModel
{
    protected $table = 'userroles';
    protected $authTable = 'authority';
    protected $userAuthTable = 'userauths';
    protected $userTable = 'users';

    public function __construct()
    {
        parent::__construct('userroles');
    }

    /**
     * Sayfa üstü KPI ve özet istatistiklerini hesaplar
     * @return array
     */
    public function getSummaryStats(): array
    {
        try {
            // 1. Toplam Rol / Pozisyon Sayısı
            $stmtRoles = $this->db->prepare("SELECT COUNT(*) AS total_roles FROM {$this->table}");
            $stmtRoles->execute();
            $totalRoles = (int) ($stmtRoles->fetchColumn() ?: 0);

            // 2. Toplam Sistem Yetkisi Sayısı
            $stmtAuth = $this->db->prepare("SELECT COUNT(*) AS total_auths FROM {$this->authTable} WHERE isActive = 1");
            $stmtAuth->execute();
            $totalAuths = (int) ($stmtAuth->fetchColumn() ?: 0);

            // 3. Rol Atanmış Personel/Kullanıcı Sayısı
            $stmtUsers = $this->db->prepare("SELECT COUNT(*) AS assigned_users FROM {$this->userTable} WHERE permission IS NOT NULL AND permission > 0");
            $stmtUsers->execute();
            $assignedUsers = (int) ($stmtUsers->fetchColumn() ?: 0);

            // 4. En Çok Yetkiye Sahip Rol
            $stmtTopRole = $this->db->prepare("
                SELECT r.roleName, COUNT(ua.authID) AS auth_count 
                FROM {$this->table} r 
                LEFT JOIN {$this->userAuthTable} ua ON ua.roleID = r.id 
                GROUP BY r.id, r.roleName 
                ORDER BY auth_count DESC, r.id ASC 
                LIMIT 1
            ");
            $stmtTopRole->execute();
            $topRole = $stmtTopRole->fetch(PDO::FETCH_ASSOC);

            return [
                'total_roles' => $totalRoles,
                'total_auths' => $totalAuths,
                'assigned_users' => $assignedUsers,
                'top_role_name' => $topRole['roleName'] ?? 'Admin',
                'top_role_auth_count' => (int) ($topRole['auth_count'] ?? 0),
            ];
        } catch (\PDOException $e) {
            error_log("PermissionModel::getSummaryStats Error: " . $e->getMessage());
            return [
                'total_roles' => 0,
                'total_auths' => 0,
                'assigned_users' => 0,
                'top_role_name' => '-',
                'top_role_auth_count' => 0,
            ];
        }
    }

    /**
     * Tüm rolleri, bunlara atanmış kullanıcı sayısı ve yetki detaylarıyla listeler
     * @return array
     */
    public function getRolesWithDetails(): array
    {
        try {
            $sql = "
                SELECT 
                    r.id,
                    r.roleName,
                    r.roleDescription,
                    r.isActive,
                    (SELECT COUNT(*) FROM {$this->userTable} u WHERE u.permission = r.id) AS user_count,
                    (SELECT COUNT(*) FROM {$this->userAuthTable} ua WHERE ua.roleID = r.id) AS auth_count
                FROM {$this->table} r
                ORDER BY r.id ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Her rol için yetki listesini ve atanmış kullanıcıları getir
            foreach ($roles as &$role) {
                $roleId = (int)$role['id'];

                // Yetkiler
                $stmtAuths = $this->db->prepare("
                    SELECT a.id, a.authName, a.authTitle, a.authGroup 
                    FROM {$this->userAuthTable} ua
                    INNER JOIN {$this->authTable} a ON a.id = ua.authID
                    WHERE ua.roleID = ? AND a.isActive = 1
                    ORDER BY a.authGroup ASC, a.authTitle ASC
                ");
                $stmtAuths->execute([$roleId]);
                $role['authorities'] = $stmtAuths->fetchAll(PDO::FETCH_ASSOC);

                // Atanmış Kullanıcılar
                $stmtUsers = $this->db->prepare("
                    SELECT id, username, Unvan, email, avatar_link 
                    FROM {$this->userTable} 
                    WHERE permission = ? 
                    ORDER BY username ASC
                ");
                $stmtUsers->execute([$roleId]);
                $role['users'] = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
            }

            return $roles;
        } catch (\PDOException $e) {
            error_log("PermissionModel::getRolesWithDetails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sistemdeki tüm yetkileri modül/grup bazında kategorize ederek döner
     * @return array
     */
    public function getAllAuthoritiesGrouped(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, authName, authTitle, authGroup 
                FROM {$this->authTable} 
                WHERE isActive = 1 
                ORDER BY authGroup ASC, authTitle ASC
            ");
            $stmt->execute();
            $allAuths = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($allAuths as $auth) {
                $group = (int)$auth['authGroup'];
                $grouped[$group][] = $auth;
            }
            return $grouped;
        } catch (\PDOException $e) {
            error_log("PermissionModel::getAllAuthoritiesGrouped Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Belirli bir rolün tüm detaylarını döner
     * @param int $roleId
     * @return array|null
     */
    public function getRoleDetails(int $roleId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$roleId]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                return null;
            }

            // Atanmış Yetkiler
            $stmtAuths = $this->db->prepare("
                SELECT a.id, a.authName, a.authTitle, a.authGroup 
                FROM {$this->userAuthTable} ua
                INNER JOIN {$this->authTable} a ON a.id = ua.authID
                WHERE ua.roleID = ? AND a.isActive = 1
                ORDER BY a.authGroup ASC, a.authTitle ASC
            ");
            $stmtAuths->execute([$roleId]);
            $role['authorities'] = $stmtAuths->fetchAll(PDO::FETCH_ASSOC);
            $role['auth_ids'] = array_column($role['authorities'], 'id');

            // Atanmış Kullanıcılar
            $stmtUsers = $this->db->prepare("
                SELECT id, username, Unvan, email, avatar_link 
                FROM {$this->userTable} 
                WHERE permission = ? 
                ORDER BY username ASC
            ");
            $stmtUsers->execute([$roleId]);
            $role['users'] = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            return $role;
        } catch (\PDOException $e) {
            error_log("PermissionModel::getRoleDetails Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Rolü ve ilişkili yetkilerini güvenli şekilde siler (Admin rolü korunur)
     * @param int $roleId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteRole(int $roleId): array
    {
        if ($roleId <= 1) {
            return [
                'success' => false,
                'message' => 'Yönetici (Admin) rolü sistem güvenliği gereği silinemez.'
            ];
        }

        try {
            $this->db->beginTransaction();

            // 1. Rol var mı kontrol et
            $stmtRole = $this->db->prepare("SELECT roleName FROM {$this->table} WHERE id = ?");
            $stmtRole->execute([$roleId]);
            $roleName = $stmtRole->fetchColumn();

            if (!$roleName) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Silinmek istenen rol bulunamadı.'
                ];
            }

            // 2. Bu role atanmış kullanıcıların yetki değerini sıfırla / pasife al
            $stmtUpdateUsers = $this->db->prepare("UPDATE {$this->userTable} SET permission = 0, statu = 0 WHERE permission = ?");
            $stmtUpdateUsers->execute([$roleId]);

            // 3. userauths tablosundaki ilişkileri temizle
            $stmtDelAuths = $this->db->prepare("DELETE FROM {$this->userAuthTable} WHERE roleID = ?");
            $stmtDelAuths->execute([$roleId]);

            // 4. userroles tablosundan rolü sil
            $stmtDelRole = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmtDelRole->execute([$roleId]);

            $this->db->commit();

            // Loglama
            if (function_exists('audit_log')) {
                audit_log(
                    'delete',
                    'permission-settings',
                    "Pozisyon/Rol silindi: {$roleName} (ID: {$roleId})",
                    'userroles',
                    $roleId,
                    ['role_name' => $roleName]
                );
            }

            return [
                'success' => true,
                'message' => 'Pozisyon/Rol ve ilişkili yetkiler başarıyla silindi.'
            ];
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("PermissionModel::deleteRole Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Yetki grupları için etiket, ikon ve renk tanımlarını döner
     * @return array
     */
    public static function getGroupDefinitions(): array
    {
        return [
            1 => [
                'title' => 'Müşteri & Firma Yönetimi',
                'icon'  => 'fa fa-building',
                'color' => '#2563eb',
                'bg'    => '#eff6ff'
            ],
            2 => [
                'title' => 'Satın Alma Yönetimi',
                'icon'  => 'fa fa-shopping-cart',
                'color' => '#059669',
                'bg'    => '#ecfdf5'
            ],
            3 => [
                'title' => 'Teklif Şablonları',
                'icon'  => 'fa fa-file-text-o',
                'color' => '#7c3aed',
                'bg'    => '#f5f3ff'
            ],
            4 => [
                'title' => 'Evrak & Doküman Yönetimi',
                'icon'  => 'fa fa-folder-open',
                'color' => '#0891b2',
                'bg'    => '#ecfeff'
            ],
            5 => [
                'title' => 'Servis & Saha Yönetimi',
                'icon'  => 'fa fa-wrench',
                'color' => '#d97706',
                'bg'    => '#fffbeb'
            ],
            6 => [
                'title' => 'Dosya & Arşiv Yönetimi',
                'icon'  => 'fa fa-paperclip',
                'color' => '#475569',
                'bg'    => '#f8fafc'
            ],
            7 => [
                'title' => 'Görev & İş Takibi',
                'icon'  => 'fa fa-tasks',
                'color' => '#ea580c',
                'bg'    => '#fff7ed'
            ],
            9 => [
                'title' => 'Not Yönetimi',
                'icon'  => 'fa fa-sticky-note-o',
                'color' => '#ca8a04',
                'bg'    => '#fefce8'
            ],
            10 => [
                'title' => 'Yapılacaklar & Hatırlatıcılar',
                'icon'  => 'fa fa-check-square-o',
                'color' => '#16a34a',
                'bg'    => '#f0fdf4'
            ],
            11 => [
                'title' => 'Ürün & Hizmet Kataloğu',
                'icon'  => 'fa fa-cubes',
                'color' => '#6366f1',
                'bg'    => '#eef2ff'
            ],
            12 => [
                'title' => 'İletişim & Panel Ayarları',
                'icon'  => 'fa fa-envelope-o',
                'color' => '#9333ea',
                'bg'    => '#faf5ff'
            ],
            13 => [
                'title' => 'Teklif & Satış Yönetimi',
                'icon'  => 'fa fa-handshake-o',
                'color' => '#0284c7',
                'bg'    => '#f0f9ff'
            ],
            14 => [
                'title' => 'Yetkilendirme & Rol Tanımları',
                'icon'  => 'fa fa-shield',
                'color' => '#dc2626',
                'bg'    => '#fef2f2'
            ],
            16 => [
                'title' => 'Raporlar & Analizler',
                'icon'  => 'fa fa-bar-chart',
                'color' => '#0d9488',
                'bg'    => '#f0fdfa'
            ],
            17 => [
                'title' => 'Destek & Talep Yönetimi',
                'icon'  => 'fa fa-life-ring',
                'color' => '#e11d48',
                'bg'    => '#fff1f2'
            ],
            20 => [
                'title' => 'Keşif & Proje Yönetimi',
                'icon'  => 'fa fa-compass',
                'color' => '#4338ca',
                'bg'    => '#e0e7ff'
            ],
            99 => [
                'title' => 'Kullanıcı & Personel Yönetimi',
                'icon'  => 'fa fa-users',
                'color' => '#be185d',
                'bg'    => '#fdf2f8'
            ],
        ];
    }

    /**
     * Rol ve yetkilerini günceller
     * @param int $roleId
     * @param string $roleName
     * @param string $roleDescription
     * @param array $authIds
     * @return array
     */
    public function updateRole(int $roleId, string $roleName, string $roleDescription, array $authIds): array
    {
        $roleName = trim($roleName);
        $roleDescription = trim($roleDescription);

        if (empty($roleName)) {
            return [
                'success' => false,
                'message' => 'Pozisyon / Rol adı boş bırakılamaz.'
            ];
        }

        if (empty($authIds)) {
            return [
                'success' => false,
                'message' => 'Lütfen bu rol için en az 1 yetki seçiniz.'
            ];
        }

        try {
            $this->db->beginTransaction();

            // 1. Rol adını ve açıklamasını güncelle
            $stmt = $this->db->prepare("UPDATE {$this->table} SET roleName = ?, roleDescription = ? WHERE id = ?");
            $stmt->execute([$roleName, $roleDescription, $roleId]);

            // 2. Mevcut yetkileri sil
            $del = $this->db->prepare("DELETE FROM {$this->userAuthTable} WHERE roleID = ?");
            $del->execute([$roleId]);

            // 3. Yeni yetkileri ekle
            $ins = $this->db->prepare("INSERT INTO {$this->userAuthTable} (roleID, authID) VALUES (?, ?)");
            $uniqueAuthIds = array_unique(array_filter(array_map('intval', $authIds)));
            foreach ($uniqueAuthIds as $authId) {
                if ($authId > 0) {
                    $ins->execute([$roleId, $authId]);
                }
            }

            $this->db->commit();

            // Loglama
            if (function_exists('audit_log')) {
                audit_log(
                    'update',
                    'permission-edit',
                    "Pozisyon yetkileri güncellendi: {$roleName} (ID: {$roleId}, Yetki Adedi: " . count($uniqueAuthIds) . ")",
                    'userroles',
                    $roleId,
                    [
                        'role_name' => $roleName,
                        'auth_count' => count($uniqueAuthIds)
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Pozisyon ve yetki matrisi başarıyla güncellendi.',
                'auth_count' => count($uniqueAuthIds)
            ];
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("PermissionModel::updateRole Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı güncelleme hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Yeni rol ve yetkilerini oluşturur
     * @param string $roleName
     * @param string $roleDescription
     * @param array $authIds
     * @return array
     */
    public function createRole(string $roleName, string $roleDescription, array $authIds): array
    {
        $roleName = trim($roleName);
        $roleDescription = trim($roleDescription);

        if (empty($roleName)) {
            return [
                'success' => false,
                'message' => 'Pozisyon / Rol adı boş bırakılamaz.'
            ];
        }

        if (empty($authIds)) {
            return [
                'success' => false,
                'message' => 'Lütfen bu rol için en az 1 yetki seçiniz.'
            ];
        }

        try {
            $this->db->beginTransaction();

            // 1. Rolü ekle
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (roleName, roleDescription, isActive) VALUES (?, ?, 1)");
            $stmt->execute([$roleName, $roleDescription]);
            $newRoleId = (int)$this->db->lastInsertId();

            // 2. Yetkileri ekle
            $ins = $this->db->prepare("INSERT INTO {$this->userAuthTable} (roleID, authID) VALUES (?, ?)");
            $uniqueAuthIds = array_unique(array_filter(array_map('intval', $authIds)));
            foreach ($uniqueAuthIds as $authId) {
                if ($authId > 0) {
                    $ins->execute([$newRoleId, $authId]);
                }
            }

            $this->db->commit();

            // Loglama
            if (function_exists('audit_log')) {
                audit_log(
                    'create',
                    'permission-new',
                    "Yeni pozisyon oluşturuldu: {$roleName} (ID: {$newRoleId}, Yetki Adedi: " . count($uniqueAuthIds) . ")",
                    'userroles',
                    $newRoleId,
                    [
                        'role_name' => $roleName,
                        'auth_count' => count($uniqueAuthIds)
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Yeni pozisyon ve yetki tanımları başarıyla oluşturuldu.',
                'role_id' => $newRoleId,
                'auth_count' => count($uniqueAuthIds)
            ];
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("PermissionModel::createRole Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı kayıt hatası: ' . $e->getMessage()
            ];
        }
    }
}
