<?php
namespace App\Model;

use App\Model\BaseModel;
use PDO;
use Exception;
use PDOException;

class MissionModel extends BaseModel
{
    protected $table = 'missions';
    private static $usersCache = null;

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Tüm kullanıcıları önbelleğe alıp ID ile hızlı erişim sağlar.
     */
    public function getUsersMap()
    {
        if (self::$usersCache !== null) {
            return self::$usersCache;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, username, Unvan, email, avatar_link, statu FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            self::$usersCache = [];
            foreach ($users as $user) {
                self::$usersCache[(int)$user['id']] = $user;
            }
            return self::$usersCache;
        } catch (PDOException $e) {
            error_log("MissionModel getUsersMap Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Kullanıcıya atanmış görevleri getirir.
     */
    public function getMyMissions($userId)
    {
        try {
            $userId = (int)$userId;
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE deleted != 'yes' ORDER BY statu ASC, id DESC");
            $stmt->execute();
            $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $myMissions = [];
            foreach ($all as $mission) {
                $authorIds = array_filter(explode('|', $mission['authors'] ?? ''));
                if (in_array((string)$userId, $authorIds) || in_array($userId, $authorIds)) {
                    $myMissions[] = $mission;
                }
            }

            return $myMissions;
        } catch (PDOException $e) {
            error_log("MissionModel getMyMissions Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Kullanıcının oluşturduğu (verdiği) görevleri getirir.
     */
    public function getGivenMissions($userId)
    {
        try {
            $userId = (int)$userId;
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE creativer = ? AND deleted != 'yes' ORDER BY statu ASC, id DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MissionModel getGivenMissions Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sistemdeki tüm görevleri getirir.
     */
    public function getAllMissions()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE deleted != 'yes' ORDER BY statu ASC, id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MissionModel getAllMissions Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tek bir görevin detayını getirir.
     */
    public function getMissionById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MissionModel getMissionById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Görev durumunu günceller (Örn: Tamamlandı / Yapıldı işaretleme).
     */
    public function updateStatus($id, $statu, $userId = null)
    {
        try {
            $id = (int)$id;
            $statu = (int)$statu;
            $okeyDate = $statu === 1 ? date("Y-m-d H:i:s") : '-';

            $stmt = $this->db->prepare("UPDATE {$this->table} SET statu = ?, okeydate = ? WHERE id = ?");
            $res = $stmt->execute([$statu, $okeyDate, $id]);

            if ($res && function_exists('audit_log')) {
                $statusText = $statu === 1 ? 'Tamamlandı (Yapıldı)' : 'Beklemede';
                audit_log('status_change', 'missions', "Görev durumu güncellendi: {$statusText} (ID: {$id})", 'mission', $id, [
                    'statu' => $statu,
                    'okeydate' => $okeyDate,
                    'updated_by' => $userId
                ]);
            }

            return $res;
        } catch (PDOException $e) {
            error_log("MissionModel updateStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Görev bilgilerini günceller.
     */
    public function updateMission($id, array $data, $userId = null)
    {
        try {
            $id = (int)$id;
            $statu = isset($data['statu']) ? (int)$data['statu'] : 0;
            $okeyDate = $statu === 1 ? date("Y-m-d H:i:s") : '-';

            $stmt = $this->db->prepare("UPDATE {$this->table} SET 
                FirmaAdi = ?,
                categoryName = ?,
                title = ?,
                mdesc = ?,
                startdate = ?,
                lastdate = ?,
                authors = ?,
                urgency = ?,
                statu = ?,
                okeydate = ?
                WHERE id = ?");

            $res = $stmt->execute([
                $data['FirmaAdi'] ?? '',
                $data['categoryName'] ?? '',
                $data['title'] ?? '',
                $data['mdesc'] ?? '',
                $data['startdate'] ?? '',
                $data['lastdate'] ?? '',
                $data['authors'] ?? '',
                $data['urgency'] ?? 'Orta',
                $statu,
                $okeyDate,
                $id
            ]);

            if ($res && function_exists('audit_log')) {
                audit_log('update', 'missions', "Görev güncellendi: " . ($data['title'] ?? ''), 'mission', $id, [
                    'title' => $data['title'] ?? '',
                    'firma' => $data['FirmaAdi'] ?? '',
                    'authors' => $data['authors'] ?? '',
                    'urgency' => $data['urgency'] ?? 'Orta',
                    'statu' => $statu,
                    'updated_by' => $userId
                ]);
            }

            return $res;
        } catch (PDOException $e) {
            error_log("MissionModel updateMission Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Görevi yumuşak silme (soft-delete).
     */
    public function softDelete($id, $userId = null, $force = false)
    {
        try {
            $id = (int)$id;
            if ($force || $userId === null) {
                $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted = 'yes' WHERE id = ?");
                $res = $stmt->execute([$id]);
            } else {
                $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted = 'yes' WHERE id = ? AND creativer = ?");
                $res = $stmt->execute([$id, (int)$userId]);
            }

            if ($res && function_exists('audit_log')) {
                audit_log('delete', 'missions', "Görev silindi (ID: {$id})", 'mission', $id, [
                    'deleted_by' => $userId
                ]);
            }

            return $res;
        } catch (PDOException $e) {
            error_log("MissionModel softDelete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Görev listesi için istatistikleri hesaplar.
     */
    public function getMissionStats($missions)
    {
        $stats = [
            'total' => count($missions),
            'pending' => 0,
            'completed' => 0,
            'urgent' => 0,
            'overdue' => 0
        ];

        $today = date('Y-m-d');

        foreach ($missions as $m) {
            $statu = (int)($m['statu'] ?? 0);
            if ($statu === 1) {
                $stats['completed']++;
            } else {
                $stats['pending']++;

                // Gecikme kontrolü
                $lastDateStr = trim($m['lastdate'] ?? '');
                if (!empty($lastDateStr)) {
                    $lastDateNorm = null;
                    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $lastDateStr, $matches)) {
                        $lastDateNorm = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $lastDateStr)) {
                        $lastDateNorm = substr($lastDateStr, 0, 10);
                    }

                    if ($lastDateNorm && $lastDateNorm < $today) {
                        $stats['overdue']++;
                    }
                }
            }

            if (($m['urgency'] ?? '') === 'Yüksek') {
                $stats['urgent']++;
            }
        }

        return $stats;
    }
}
