<?php
namespace App\Model;

use PDO;
use Exception;

class MenuOrderModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('user_menu_orders');
    }

    /**
     * Kullanıcıya ait menü sıralamasını getirir.
     *
     * @param int $userId
     * @return array|null
     */
    public function getOrderByUserId(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT menu_order FROM {$this->table} WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['menu_order'])) {
                $decoded = json_decode($row['menu_order'], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("MenuOrderModel::getOrderByUserId Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kullanıcıya ait menü sıralamasını kaydeder veya günceller (Upsert).
     *
     * @param int $userId
     * @param array $orderData
     * @return bool
     */
    public function saveOrder(int $userId, array $orderData): bool
    {
        try {
            $json = json_encode($orderData, JSON_UNESCAPED_UNICODE);
            $now = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (user_id, menu_order, created_at, updated_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE menu_order = VALUES(menu_order), updated_at = VALUES(updated_at)
            ");
            return $stmt->execute([$userId, $json, $now, $now]);
        } catch (Exception $e) {
            error_log("MenuOrderModel::saveOrder Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kullanıcıya ait menü sıralamasını siler (Varsayılana döndürür).
     *
     * @param int $userId
     * @return bool
     */
    public function resetOrder(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("MenuOrderModel::resetOrder Error: " . $e->getMessage());
            return false;
        }
    }
}
