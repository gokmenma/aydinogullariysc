<?php
namespace App\Model;

use App\Dashboard\DashboardWidgetRegistry;
use PDO;
use Exception;

class DashboardLayoutModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('user_dashboard_layouts');
    }

    /**
     * Sistemde tanımlı tüm widget'ların meta verilerini ve varsayılan boyutlarını döner.
     *
     * @param int $userId
     * @return array
     */
    public function getWidgetCatalog(int $userId = 0): array
    {
        $canViewLogs = in_array($userId, [1, 12]);
        $canViewOffers = (isset($_SESSION['lid']) && function_exists('permtrue')) ? permtrue('offerview') : true;

        $catalog = [
            'widget_kpi_summary' => [
                'id' => 'widget_kpi_summary',
                'title' => 'Özet İstatistikler (KPI)',
                'subtitle' => 'Aktif Servisler, Bekleyen & Kazanılan Teklifler, Portföy',
                'icon' => 'fa fa-tachometer',
                'badge' => 'Genel',
                'badge_class' => 'soft-blue',
                'default_w' => 12,
                'default_h' => 2,
                'min_w' => 4,
                'min_h' => 2,
                'is_allowed' => true,
                'default_order' => 1,
            ],
            'widget_service_board' => [
                'id' => 'widget_service_board',
                'title' => 'Servis Planlama Panosu',
                'subtitle' => '15 Günlük Liste ve Aylık Takvim Görünümü',
                'icon' => 'fa fa-calendar',
                'badge' => 'Servis',
                'badge_class' => 'soft-blue',
                'default_w' => 12,
                'default_h' => 4,
                'min_w' => 6,
                'min_h' => 4,
                'is_allowed' => true,
                'default_order' => 2,
            ],
            'widget_recent_offers' => [
                'id' => 'widget_recent_offers',
                'title' => 'Son Teklifler',
                'subtitle' => 'En son hazırlanan ve güncellenen tekliflerin listesi',
                'icon' => 'fa fa-file-text-o',
                'badge' => 'Teklif',
                'badge_class' => 'soft-emerald',
                'default_w' => 6,
                'default_h' => 6,
                'min_w' => 4,
                'min_h' => 6,
                'is_allowed' => $canViewOffers,
                'default_order' => 3,
            ],
            'widget_recent_services' => [
                'id' => 'widget_recent_services',
                'title' => 'Son Eklenen Servisler',
                'subtitle' => 'Sisteme yeni kaydedilen servis kayıtları ve durumları',
                'icon' => 'fa fa-wrench',
                'badge' => 'Servis',
                'badge_class' => 'soft-blue',
                'default_w' => 6,
                'default_h' => 6,
                'min_w' => 4,
                'min_h' => 6,
                'is_allowed' => true,
                'default_order' => 4,
            ],
            'widget_team_status' => [
                'id' => 'widget_team_status',
                'title' => 'Ekip ve Operasyon Durumu',
                'subtitle' => 'Personel görev sayıları ve anlık çalışma durumları',
                'icon' => 'fa fa-users',
                'badge' => 'Ekip',
                'badge_class' => 'soft-amber',
                'default_w' => 6,
                'default_h' => 6,
                'min_w' => 4,
                'min_h' => 6,
                'is_allowed' => true,
                'default_order' => 5,
            ],
            'widget_todo_list' => [
                'id' => 'widget_todo_list',
                'title' => 'Yapılacaklar & Hatırlatıcılar',
                'subtitle' => 'Bekleyen görevler, hatırlatıcılar ve notlar',
                'icon' => 'fa fa-check-square-o',
                'badge' => 'Görevler',
                'badge_class' => 'soft-purple',
                'default_w' => 6,
                'default_h' => 6,
                'min_w' => 4,
                'min_h' => 6,
                'is_allowed' => true,
                'default_order' => 6,
            ],
            'widget_system_logs' => [
                'id' => 'widget_system_logs',
                'title' => 'Son Aktiviteler & İşlem Akışı',
                'subtitle' => 'Sistemde yapılan son işlemler, güncellemeler ve hareketler',
                'icon' => 'fa fa-bolt',
                'badge' => 'Aktiviteler',
                'badge_class' => 'soft-blue',
                'default_w' => 12,
                'default_h' => 10,
                'min_w' => 4,
                'min_h' => 10,
                'is_allowed' => $canViewLogs,
                'default_order' => 7,
            ],
        ];

        global $ac;
        if ($ac instanceof PDO) {
            $catalog = array_merge($catalog, (new DashboardWidgetRegistry($ac))->definitions($userId));
        }

        uasort($catalog, static fn(array $left, array $right): int => ($left['default_order'] ?? 999) <=> ($right['default_order'] ?? 999));
        return $catalog;
    }

    /**
     * Kullanıcı için varsayılan yerleşim düzenini üretir.
     *
     * @param int $userId
     * @return array
     */
    public function getDefaultLayout(int $userId = 0): array
    {
        $catalog = $this->getWidgetCatalog($userId);
        $layout = [];

        $y = 0;
        // 1. KPI
        if (!empty($catalog['widget_kpi_summary']['is_allowed'])) {
            $layout[] = [
                'id' => 'widget_kpi_summary',
                'x' => 0,
                'y' => 0,
                'w' => 12,
                'h' => 2,
                'minW' => 4,
                'minH' => 2,
                'visible' => true,
            ];
            $y += 2;
        }

        // 2. Servis Planlama
        if (!empty($catalog['widget_service_board']['is_allowed'])) {
            $layout[] = [
                'id' => 'widget_service_board',
                'x' => 0,
                'y' => $y,
                'w' => 12,
                'h' => 4,
                'minW' => 6,
                'minH' => 4,
                'visible' => true,
            ];
            $y += 4;
        }

        // 3. Günlük operasyon kartları
        foreach ([
            ['id' => 'widget_currency_rates', 'x' => 0],
            ['id' => 'widget_today_work', 'x' => 4],
            ['id' => 'widget_upcoming_work', 'x' => 8],
        ] as $widget) {
            if (!empty($catalog[$widget['id']]['is_allowed'])) {
                $layout[] = [
                    'id' => $widget['id'], 'x' => $widget['x'], 'y' => $y, 'w' => 4, 'h' => 5,
                    'minW' => 4, 'minH' => 5, 'visible' => true,
                ];
            }
        }
        $y += 5;

        if (!empty($catalog['widget_purchase_approvals']['is_allowed'])) {
            $layout[] = [
                'id' => 'widget_purchase_approvals', 'x' => 0, 'y' => $y, 'w' => 12, 'h' => 4,
                'minW' => 3, 'minH' => 3, 'visible' => true,
            ];
            $y += 4;
        }

        // Son Teklifler & Son Servisler
        $hasOffers = !empty($catalog['widget_recent_offers']['is_allowed']);
        $hasServices = !empty($catalog['widget_recent_services']['is_allowed']);

        if ($hasOffers && $hasServices) {
            $layout[] = [
                'id' => 'widget_recent_offers',
                'x' => 0,
                'y' => $y,
                'w' => 6,
                'h' => 6,
                'minW' => 4,
                'minH' => 6,
                'visible' => true,
            ];
            $layout[] = [
                'id' => 'widget_recent_services',
                'x' => 6,
                'y' => $y,
                'w' => 6,
                'h' => 6,
                'minW' => 4,
                'minH' => 6,
                'visible' => true,
            ];
            $y += 6;
        } elseif ($hasOffers) {
            $layout[] = [
                'id' => 'widget_recent_offers',
                'x' => 0,
                'y' => $y,
                'w' => 12,
                'h' => 6,
                'minW' => 4,
                'minH' => 6,
                'visible' => true,
            ];
            $y += 6;
        } elseif ($hasServices) {
            $layout[] = [
                'id' => 'widget_recent_services',
                'x' => 0,
                'y' => $y,
                'w' => 12,
                'h' => 6,
                'minW' => 4,
                'minH' => 6,
                'visible' => true,
            ];
            $y += 6;
        }

        // 4. Ekip & Todo
        $layout[] = [
            'id' => 'widget_team_status',
            'x' => 0,
            'y' => $y,
            'w' => 6,
            'h' => 6,
            'minW' => 4,
            'minH' => 6,
            'visible' => true,
        ];
        $layout[] = [
            'id' => 'widget_todo_list',
            'x' => 6,
            'y' => $y,
            'w' => 6,
            'h' => 6,
            'minW' => 4,
            'minH' => 6,
            'visible' => true,
        ];
        $y += 6;

        // 5. Sistem Logları
        if (!empty($catalog['widget_system_logs']['is_allowed'])) {
            $layout[] = [
                'id' => 'widget_system_logs',
                'x' => 0,
                'y' => $y,
                'w' => 12,
                'h' => 10,
                'minW' => 4,
                'minH' => 10,
                'visible' => true,
            ];
        }

        return $layout;
    }

    /**
     * Kullanıcının kayıtlı yerleşim düzenini getirir. Kayıt yoksa varsayılanı döner.
     *
     * @param int $userId
     * @return array
     */
    public function getEffectiveLayout(int $userId): array
    {
        $defaultLayout = $this->getDefaultLayout($userId);
        $catalog = $this->getWidgetCatalog($userId);

        if ($userId <= 0) {
            return $defaultLayout;
        }

        $saved = $this->getRawLayoutByUserId($userId);
        if (empty($saved) || !is_array($saved)) {
            return $defaultLayout;
        }

        // Kayıtlı veriyi katalog ve yetkilerle doğrula / birleştir
        $validLayout = [];
        $existingWidgetIds = [];

        foreach ($saved as $item) {
            $wId = $item['id'] ?? '';
            if (empty($wId) || !isset($catalog[$wId])) {
                continue;
            }

            // Yetki kontrolü: Kullanıcının bu widget'a yetkisi var mı?
            if (empty($catalog[$wId]['is_allowed'])) {
                continue;
            }

            $meta = $catalog[$wId];
            $width = isset($item['w']) ? max((int)$meta['min_w'], min(12, (int)$item['w'])) : (int)$meta['default_w'];
            $x = isset($item['x']) ? max(0, min(12 - $width, (int)$item['x'])) : 0;
            $validLayout[] = [
                'id' => $wId,
                'x' => $x,
                'y' => isset($item['y']) ? max(0, min(1000, (int)$item['y'])) : 0,
                'w' => $width,
                'h' => isset($item['h']) ? max((int)$meta['min_h'], min(50, (int)$item['h'])) : (int)$meta['default_h'],
                'minW' => (int)$meta['min_w'],
                'minH' => (int)$meta['min_h'],
                'visible' => !empty($item['visible']),
                'settings' => $this->sanitizeWidgetSettings($wId, $item['settings'] ?? []),
            ];
            $existingWidgetIds[] = $wId;
        }

        // Sonradan eklenen widget'lar mevcut kullanıcı panosunu bozmaz; katalogda kapalı gelir.
        foreach ($catalog as $wId => $meta) {
            if (!empty($meta['is_allowed']) && !in_array($wId, $existingWidgetIds)) {
                $validLayout[] = [
                    'id' => $wId,
                    'x' => 0,
                    'y' => 999, // En alta ekle
                    'w' => (int)$meta['default_w'],
                    'h' => (int)$meta['default_h'],
                    'minW' => (int)$meta['min_w'],
                    'minH' => (int)$meta['min_h'],
                    'visible' => false,
                    'settings' => $this->sanitizeWidgetSettings($wId, []),
                ];
            }
        }

        return $validLayout;
    }

    /**
     * Veritabanından ham JSON verisini çeker.
     *
     * @param int $userId
     * @return array|null
     */
    public function getRawLayoutByUserId(int $userId): ?array
    {
        try {
            if ($userId <= 0) {
                return null;
            }
            $stmt = $this->db->prepare("SELECT `layout_data` FROM `{$this->table}` WHERE `user_id` = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['layout_data'])) {
                $decoded = json_decode($row['layout_data'], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("DashboardLayoutModel::getRawLayoutByUserId Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Kullanıcıya ait yerleşim düzenini kaydeder / günceller (Upsert).
     *
     * @param int $userId
     * @param array $layoutData
     * @return bool
     */
    public function saveLayout(int $userId, array $layoutData): bool
    {
        try {
            if ($userId <= 0) {
                error_log("DashboardLayoutModel::saveLayout Error: Invalid userId " . $userId);
                return false;
            }

            $catalog = $this->getWidgetCatalog($userId);
            $cleanLayout = [];
            $seenWidgetIds = [];

            foreach ($layoutData as $item) {
                $wId = $item['id'] ?? '';
                if (empty($wId) || !isset($catalog[$wId]) || empty($catalog[$wId]['is_allowed']) || isset($seenWidgetIds[$wId])) {
                    continue;
                }

                $meta = $catalog[$wId];
                $width = isset($item['w']) ? max((int)$meta['min_w'], min(12, (int)$item['w'])) : (int)$meta['default_w'];
                $seenWidgetIds[$wId] = true;
                $cleanLayout[] = [
                    'id' => $wId,
                    'x' => isset($item['x']) ? max(0, min(12 - $width, (int)$item['x'])) : 0,
                    'y' => isset($item['y']) ? max(0, min(1000, (int)$item['y'])) : 0,
                    'w' => $width,
                    'h' => isset($item['h']) ? max((int)$meta['min_h'], min(50, (int)$item['h'])) : (int)$meta['default_h'],
                    'minW' => (int)$meta['min_w'],
                    'minH' => (int)$meta['min_h'],
                    'visible' => isset($item['visible']) ? (bool)$item['visible'] : true,
                    'settings' => $this->sanitizeWidgetSettings($wId, $item['settings'] ?? []),
                ];
            }

            $json = json_encode($cleanLayout, JSON_UNESCAPED_UNICODE);
            $now = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare("
                INSERT INTO `{$this->table}` (`user_id`, `layout_data`, `created_at`, `updated_at`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `layout_data` = ?, `updated_at` = ?
            ");
            $res = $stmt->execute([$userId, $json, $now, $now, $json, $now]);
            if (!$res) {
                error_log("DashboardLayoutModel::saveLayout SQL Error: " . json_encode($stmt->errorInfo()));
            }
            return $res;
        } catch (Exception $e) {
            error_log("DashboardLayoutModel::saveLayout Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kullanıcıya ait yerleşim düzenini sıfırlar (Varsayılana döndürür).
     *
     * @param int $userId
     * @return bool
     */
    public function resetLayout(int $userId): bool
    {
        try {
            if ($userId <= 0) {
                return false;
            }
            $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `user_id` = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("DashboardLayoutModel::resetLayout Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Widget'a özel ve cihazlar arasında taşınacak tercihleri beyaz listeyle temizler.
     */
    private function sanitizeWidgetSettings(string $widgetId, $settings): array
    {
        if (!is_array($settings) || $widgetId !== 'widget_service_board') {
            return [];
        }

        $view = ($settings['view'] ?? '') === 'month_cal' ? 'month_cal' : 'single_row';
        $heights = is_array($settings['heights'] ?? null) ? $settings['heights'] : [];

        return [
            'view' => $view,
            'expanded' => $view === 'month_cal' && !empty($settings['expanded']),
            'heights' => [
                'single_row' => max(4, min(20, (int)($heights['single_row'] ?? 4))),
                'month_cal' => max(11, min(20, (int)($heights['month_cal'] ?? 11))),
            ],
        ];
    }
}
