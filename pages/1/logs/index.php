<?php
if (!in_array(sesset("id"), [1, 12])) {
    header("Location: index.php?error=nopermission");
    exit;
}

// ─── Tanımlar & Haritalamalar ───
$event_labels = [
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

$event_badges = [
    'view' => 'badge-log-view',
    'login' => 'badge-log-login',
    'logout' => 'badge-log-logout',
    'create' => 'badge-log-create',
    'update' => 'badge-log-update',
    'delete' => 'badge-log-delete',
    'export' => 'badge-log-export',
    'copy' => 'badge-log-copy',
    'status_change' => 'badge-log-status',
    'upload' => 'badge-log-upload',
    'download' => 'badge-log-download',
    'error' => 'badge-log-error',
];

$event_icons = [
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

$entity_labels = [
    'user' => 'Kullanıcı',
    'page' => 'Sayfa',
    'customer' => 'Firma',
    'customer_list' => 'Firma Listesi',
    'offer' => 'Teklif',
    'service' => 'Servis',
    'service_list' => 'Servis Listesi',
    'product' => 'Ürün/Hizmet',
    'purchase' => 'Satın Alma',
    'purchase_item' => 'Satın Alma Kalemi',
    'report' => 'Rapor',
    'backup' => 'Yedek',
    'task' => 'Görev',
];

$module_icons = [
    'services' => 'fa fa-wrench',
    'service' => 'fa fa-wrench',
    'offers' => 'fa fa-file-text-o',
    'customers' => 'fa fa-building-o',
    'purchases' => 'fa fa-shopping-cart',
    'kesif' => 'fa fa-search',
    'products' => 'fa fa-cube',
    'reports' => 'fa fa-bar-chart',
    'report' => 'fa fa-bar-chart',
    'auth' => 'fa fa-shield',
    'backup' => 'fa fa-database',
    'backup_settings' => 'fa fa-cogs',
    'backup_gdrive' => 'fa fa-cloud',
    'backup_sync' => 'fa fa-refresh',
    'navigation' => 'fa fa-compass',
    'tasks' => 'fa fa-tasks',
    'permission-edit' => 'fa fa-key',
];

// ─── Aktif Tab Belirleme (Varsayılan: 'dashboard') ───
$active_tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($active_tab, ['dashboard', 'logs', 'archive'], true)) {
    $active_tab = 'dashboard';
}
$list_table = $active_tab === 'archive' ? 'logs_archive' : 'logs';
$list_tab = $active_tab === 'archive' ? 'archive' : 'logs';

// ─── İstatistikleri ve Dashboard Verilerini Hesapla ───
try {
    // 1. Temel KPI'lar
    $total_logs = (int)$ac->query("SELECT (SELECT COUNT(*) FROM logs) + (SELECT COUNT(*) FROM logs_archive)")->fetchColumn();
    $total_today = (int)$ac->query("SELECT COUNT(*) FROM logs WHERE event_type IS NOT NULL AND event_type <> 'view' AND DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) = CURDATE()")->fetchColumn();
    $logins_today = (int)$ac->query("SELECT COUNT(*) FROM logs WHERE (event_type = 'login' OR action LIKE '%giriş yaptı%' OR message LIKE '%giriş yaptı%' OR summary LIKE '%giriş yaptı%') AND DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) = CURDATE()")->fetchColumn();
    $errors_today = (int)$ac->query("SELECT COUNT(*) FROM logs WHERE level IN ('ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY') AND DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) = CURDATE()")->fetchColumn();

    // 2. Son 14 Günlük Trend Verileri
    $trend_query = $ac->query("
        SELECT 
            DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) as log_date,
            COUNT(*) as total_count,
            SUM(CASE WHEN event_type = 'view' OR (event_type IS NULL AND message LIKE '%Sayfa Ziyareti%') THEN 1 ELSE 0 END) as view_count,
            SUM(CASE WHEN event_type IN ('create', 'update', 'delete', 'status_change', 'copy', 'export', 'upload', 'download') THEN 1 ELSE 0 END) as op_count,
            SUM(CASE WHEN event_type = 'login' OR action LIKE '%giriş yaptı%' OR message LIKE '%giriş yaptı%' THEN 1 ELSE 0 END) as login_count
        FROM logs
        WHERE COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY log_date
        ORDER BY log_date ASC
    ");
    $trend_raw = $trend_query->fetchAll(PDO::FETCH_ASSOC);

    // 14 günlük eksiksiz takvim dizisi oluştur
    $trend_dates = [];
    $trend_ops = [];
    $trend_views = [];
    $trend_logins = [];
    $trend_map = [];
    foreach ($trend_raw as $tr) {
        $trend_map[$tr['log_date']] = $tr;
    }

    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $d_display = date('d.m', strtotime($d));
        $trend_dates[] = $d_display;
        if (isset($trend_map[$d])) {
            $trend_ops[] = (int)$trend_map[$d]['op_count'];
            $trend_views[] = (int)$trend_map[$d]['view_count'];
            $trend_logins[] = (int)$trend_map[$d]['login_count'];
        } else {
            $trend_ops[] = 0;
            $trend_views[] = 0;
            $trend_logins[] = 0;
        }
    }

    // 3. İşlem Türü Dağılımı (Donut Grafiği İçin)
    $event_distribution_query = $ac->query("
        SELECT 
            COALESCE(NULLIF(event_type, ''), 'other') as event_key,
            COUNT(*) as cnt
        FROM logs
        WHERE COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY event_key
        ORDER BY cnt DESC
    ");
    $event_distribution_raw = $event_distribution_query->fetchAll(PDO::FETCH_ASSOC);

    $donut_labels = [];
    $donut_series = [];
    foreach ($event_distribution_raw as $ed) {
        $key = $ed['event_key'];
        $lbl = $event_labels[$key] ?? ($key === 'other' ? 'Diğer / Eski' : ucfirst($key));
        $donut_labels[] = $lbl;
        $donut_series[] = (int)$ed['cnt'];
    }

    // 4. Modül Bazlı İşlem Dağılımı (Bar Grafiği İçin - Navigation hariç)
    $module_distribution_query = $ac->query("
        SELECT 
            COALESCE(NULLIF(module, ''), 'Genel') as module_key,
            COUNT(*) as cnt
        FROM logs
        WHERE COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND (module <> 'navigation' OR module IS NULL)
        GROUP BY module_key
        ORDER BY cnt DESC
        LIMIT 7
    ");
    $module_distribution_raw = $module_distribution_query->fetchAll(PDO::FETCH_ASSOC);

    $bar_categories = [];
    $bar_series = [];
    foreach ($module_distribution_raw as $md) {
        $bar_categories[] = ucfirst($md['module_key']);
        $bar_series[] = (int)$md['cnt'];
    }

    // 5. En Aktif Kullanıcılar (Top 5 Leaderboard - Son 30 Gün)
    $top_users_query = $ac->query("
        SELECT 
            COALESCE(NULLIF(user_id, 0), author) as uid,
            COUNT(*) as total_ops,
            MAX(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) as last_active
        FROM logs
        WHERE COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND COALESCE(NULLIF(user_id, 0), author) > 0
        GROUP BY uid
        ORDER BY total_ops DESC
        LIMIT 5
    ");
    $top_users_raw = $top_users_query->fetchAll(PDO::FETCH_ASSOC);
    $top_user_max = !empty($top_users_raw) ? (int)$top_users_raw[0]['total_ops'] : 1;

    // 6. Son Kritik / Güvenlik Olayları Akışı (Son 5 Kayıt)
    $critical_recent_query = $ac->query("
        SELECT *
        FROM logs
        WHERE (event_type IN ('delete', 'error', 'status_change') OR level IN ('ERROR', 'CRITICAL', 'WARNING'))
        ORDER BY id DESC
        LIMIT 5
    ");
    $critical_recent = $critical_recent_query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_logs = 0;
    $total_today = 0;
    $logins_today = 0;
    $errors_today = 0;
    $trend_dates = [];
    $trend_ops = [];
    $trend_views = [];
    $trend_logins = [];
    $donut_labels = [];
    $donut_series = [];
    $bar_categories = [];
    $bar_series = [];
    $top_users_raw = [];
    $top_user_max = 1;
    $critical_recent = [];
}

// ─── Paginate ve Filtreler ───
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

// Filtre: Kullanıcı
if (!empty($_GET['filter_user'])) {
    $where[] = "(user_id = :filter_user_1 OR author = :filter_user_2)";
    $params[':filter_user_1'] = intval($_GET['filter_user']);
    $params[':filter_user_2'] = intval($_GET['filter_user']);
}

// Filtre: Seviye
if (!empty($_GET['filter_level'])) {
    $where[] = "level = :filter_level";
    $params[':filter_level'] = $_GET['filter_level'];
}

// Filtre: İşlem türü
$selected_event = $_GET['filter_event'] ?? 'all';
if ($selected_event === 'operations') {
    $where[] = "(
        (event_type IS NOT NULL AND event_type <> 'view')
        OR (
            event_type IS NULL
            AND COALESCE(summary, message, details, action, '') NOT LIKE '%Sayfa Ziyareti:%'
        )
    )";
} elseif ($selected_event === 'view') {
    $where[] = "(
        event_type = 'view'
        OR (
            event_type IS NULL
            AND COALESCE(summary, message, details, action, '') LIKE '%Sayfa Ziyareti:%'
        )
    )";
} elseif ($selected_event !== 'all' && isset($event_labels[$selected_event])) {
    $where[] = "event_type = :filter_event";
    $params[':filter_event'] = $selected_event;
}

// Filtre: Modül
if (!empty($_GET['filter_module'])) {
    $where[] = "module = :filter_module";
    $params[':filter_module'] = $_GET['filter_module'];
}

// Filtre: Arama Terimi
if (!empty($_GET['filter_search'])) {
    $where[] = "(action LIKE :search_1 OR details LIKE :search_2 OR url LIKE :search_3 OR ip_address LIKE :search_4 OR message LIKE :search_5 OR summary LIKE :search_6 OR entity_id LIKE :search_7)";
    $searchTerm = '%' . $_GET['filter_search'] . '%';
    $params[':search_1'] = $searchTerm;
    $params[':search_2'] = $searchTerm;
    $params[':search_3'] = $searchTerm;
    $params[':search_4'] = $searchTerm;
    $params[':search_5'] = $searchTerm;
    $params[':search_6'] = $searchTerm;
    $params[':search_7'] = $searchTerm;
}

// Filtre: Başlangıç Tarihi
if (!empty($_GET['filter_start_date'])) {
    $startDate = $_GET['filter_start_date'];
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $startDate)) {
        $parts = explode('-', $startDate);
        $startDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    $where[] = "COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}

// Filtre: Bitiş Tarihi
if (!empty($_GET['filter_end_date'])) {
    $endDate = $_GET['filter_end_date'];
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $endDate)) {
        $parts = explode('-', $endDate);
        $endDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    $where[] = "COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y')) <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Toplam satır sayısı
    $count_stmt = $ac->prepare("SELECT COUNT(*) FROM {$list_table} $where_sql");
    $count_stmt->execute($params);
    $total_rows = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Kayıtları çek
    $logs_stmt = $ac->prepare("SELECT * FROM {$list_table} $where_sql ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kullanıcı listesi
    $users_stmt = $ac->query("SELECT id, username FROM users ORDER BY username ASC");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    $modules_stmt = $ac->query("SELECT DISTINCT module FROM {$list_table} WHERE module IS NOT NULL AND module <> '' ORDER BY module");
    $all_modules = $modules_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Sütun filtre seçenek sayıları (Tüm veritabanı toplamları)
    $logFilterCounts = [
        'user' => [],
        'event' => [],
        'module' => []
    ];
    try {
        $stLogUsers = $ac->query("
            SELECT COALESCE(u.username, CONCAT('Kullanıcı #', l.author)) as uname, COUNT(*) as cnt 
            FROM {$list_table} l
            LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)
            WHERE u.username IS NOT NULL AND u.username != ''
            GROUP BY uname ORDER BY cnt DESC
        ");
        if ($stLogUsers) {
            $logFilterCounts['user'] = $stLogUsers->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        $stLogEvents = $ac->query("
            SELECT COALESCE(NULLIF(event_type, ''), 'view') as ev, COUNT(*) as cnt 
            FROM {$list_table}
            GROUP BY ev ORDER BY cnt DESC
        ");
        if ($stLogEvents) {
            $evRaw = $stLogEvents->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($evRaw as $ev => $cnt) {
                $lbl = $event_labels[$ev] ?? $ev;
                $logFilterCounts['event'][$lbl] = ($logFilterCounts['event'][$lbl] ?? 0) + $cnt;
            }
        }

        $stLogModules = $ac->query("
            SELECT module, COUNT(*) as cnt 
            FROM {$list_table}
            WHERE module IS NOT NULL AND module != '' 
            GROUP BY module ORDER BY cnt DESC
        ");
        if ($stLogModules) {
            $logFilterCounts['module'] = $stLogModules->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {}
} catch (PDOException $e) {
    $logs = [];
    $all_users = [];
    $all_modules = [];
    $logFilterCounts = ['user' => [], 'event' => [], 'module' => []];
    $total_rows = 0;
    $total_pages = 0;
    $error_msg = $e->getMessage();
}

// Göreceli zaman yardımcısı
if (!function_exists('formatRelativeTime')) {
    function formatRelativeTime($datetimeStr) {
        if (empty($datetimeStr)) return '-';
        $time = strtotime($datetimeStr);
        if (!$time) return $datetimeStr;
        $diff = time() - $time;
        if ($diff < 60) return 'Az önce';
        if ($diff < 3600) return floor($diff / 60) . ' dk önce';
        if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
        if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
        return date('d.m.Y H:i', $time);
    }
}
?>

<!-- ApexCharts Script Yüklemesi -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="pd-ltr-20 xs-pd-20-10 logs-master-wrapper">
    <div class="min-height-200px">
        
        <!-- 1. CRM & PREMIUM HERO BANNER -->
        <div class="crm-hero-banner logs-hero-banner animate-fade-in mb-3">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-12 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center mb-2">
                        <span class="crm-date-chip">
                            <i class="fa fa-shield"></i> Güvenlik & Denetim Merkezi
                        </span>
                        <span class="badge badge-log-login ml-2 d-none d-sm-inline-flex">
                            <i class="fa fa-circle text-success mr-1 blink-dot"></i> Canlı İzleme Aktif
                        </span>
                    </div>
                    <h2 class="crm-hero-title">Sistem Aktiviteleri & Denetim Paneli</h2>
                    <p class="crm-hero-subtitle m-0">Kullanıcı hareketleri, CRUD işlemleri, veri güncellemeleri ve kritik sistem logları.</p>
                </div>
                <div class="col-lg-5 col-md-12 text-lg-right">
                    <!-- Sekme Değiştirici Butonlar -->
                    <div class="logs-tab-switch-group">
                        <a href="index.php?p=logs/index&tab=dashboard" class="logs-switch-btn <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fa fa-pie-chart mr-1"></i> Aktivite Dashboard
                        </a>
                        <a href="index.php?p=logs/index&tab=logs" class="logs-switch-btn <?php echo $active_tab === 'logs' ? 'active' : ''; ?>">
                            <i class="fa fa-list mr-1"></i> Aktivite Günlüğü
                        </a>
                        <a href="index.php?p=logs/index&tab=archive" class="logs-switch-btn <?php echo $active_tab === 'archive' ? 'active' : ''; ?>">
                            <i class="fa fa-archive mr-1"></i> Arşiv
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hızlı Aksiyon & Filtre Şeridi -->
            <div class="crm-quick-actions">
                <span class="crm-quick-actions-label"><i class="fa fa-bolt"></i> Hızlı Filtreler:</span>
                <div class="crm-quick-actions-list">
                    <a href="index.php?p=logs/index&tab=logs" class="crm-quick-btn <?php echo empty($_GET['filter_event']) && empty($_GET['filter_start_date']) ? 'btn-primary-action' : ''; ?>">
                        <i class="fa fa-bars"></i> Tüm Kayıtlar
                    </a>
                    <a href="index.php?p=logs/index&tab=logs&filter_event=operations" class="crm-quick-btn <?php echo ($_GET['filter_event'] ?? '') === 'operations' ? 'btn-primary-action' : ''; ?>">
                        <i class="fa fa-hand-pointer-o"></i> Kullanıcı İşlemleri
                    </a>
                    <a href="index.php?p=logs/index&tab=logs&filter_event=login" class="crm-quick-btn <?php echo ($_GET['filter_event'] ?? '') === 'login' ? 'btn-primary-action' : ''; ?>">
                        <i class="fa fa-sign-in"></i> Girişler
                    </a>
                    <a href="index.php?p=logs/index&tab=logs&filter_event=delete" class="crm-quick-btn <?php echo ($_GET['filter_event'] ?? '') === 'delete' ? 'btn-primary-action' : ''; ?>">
                        <i class="fa fa-trash-o"></i> Silmeler
                    </a>
                    <a href="index.php?p=logs/index&tab=logs&filter_start_date=<?php echo date('d-m-Y'); ?>&filter_end_date=<?php echo date('d-m-Y'); ?>" class="crm-quick-btn">
                        <i class="fa fa-calendar-check-o"></i> Bugünkü Hareketler
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. EXECUTIVE KPI GRID (Teklifler Sayfasıyla Uyumlu CRM KPI Kartları) -->
        <div class="row mx-0 mb-3 kpi-summary-collapse animate-fade-in">
            <!-- Toplam Log -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Toplam Sistem Logu</span>
                            <div class="crm-kpi-value text-primary"><?php echo number_format($total_logs, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-primary">
                            <i class="fa fa-database"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11"><i class="fa fa-server mr-1"></i> Tüm Zamanlar</span>
                        <span class="crm-badge-soft soft-primary">Kayıt Arşivi</span>
                    </div>
                </div>
            </div>

            <!-- Bugünkü İşlemler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bugünkü İşlemler</span>
                            <div class="crm-kpi-value"><?php echo number_format($total_today, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-emerald">
                            <i class="fa fa-bolt"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11"><i class="fa fa-clock-o mr-1"></i> CRUD & Aksiyonlar</span>
                        <span class="crm-badge-soft soft-emerald">Bugün</span>
                    </div>
                </div>
            </div>

            <!-- Bugünkü Girişler -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Bugünkü Girişler</span>
                            <div class="crm-kpi-value"><?php echo number_format($logins_today, 0, ',', '.'); ?></div>
                        </div>
                        <div class="crm-kpi-icon icon-amber">
                            <i class="fa fa-sign-in"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="text-muted font-11"><i class="fa fa-user-circle mr-1"></i> Oturumlar</span>
                        <span class="crm-badge-soft soft-amber">Giriş Trafiği</span>
                    </div>
                </div>
            </div>

            <!-- Kritik / Hatalar -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3 mb-xl-0 px-1">
                <div class="crm-kpi-card">
                    <div class="crm-kpi-header">
                        <div>
                            <span class="crm-kpi-label">Kritik & Hata Olayı</span>
                            <div class="crm-kpi-value <?php echo $errors_today > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($errors_today, 0, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="crm-kpi-icon <?php echo $errors_today > 0 ? 'icon-rose' : 'icon-emerald'; ?>">
                            <i class="fa <?php echo $errors_today > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                        </div>
                    </div>
                    <div class="crm-kpi-footer">
                        <span class="font-11 <?php echo $errors_today > 0 ? 'text-danger font-weight-bold' : 'text-success'; ?>">
                            <?php echo $errors_today > 0 ? 'İnceleme Gerekli' : 'Sistem Kararlı'; ?>
                        </span>
                        <span class="crm-badge-soft <?php echo $errors_today > 0 ? 'soft-rose' : 'soft-emerald'; ?>">
                            <?php echo $errors_today > 0 ? 'Hata Var' : 'Sorunsuz'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($active_tab === 'dashboard'): ?>
        <!-- ========================================================================= -->
        <!-- 3. DASHBOARD ANALİTİK GÖRÜNÜMÜ -->
        <!-- ========================================================================= -->
        <div class="logs-dashboard-view animate-fade-in">
            
            <!-- 14 Günlük Aktivite Trend Grafiği -->
            <div class="form-card mb-4 mx-1">
                <div class="form-card-header d-flex justify-content-between align-items-center">
                    <div class="header-left-inner">
                        <div class="card-icon">
                            <i class="fa fa-line-chart"></i>
                        </div>
                        <div>
                            <h5>Son 14 Günlük Aktivite Trendi</h5>
                            <p>Günlük operasyonel işlem sayısı, gezinme trafiği ve oturum açma hacmi</p>
                        </div>
                    </div>
                    <div>
                        <span class="badge badge-log-create"><i class="fa fa-calendar mr-1"></i>Son 14 Gün</span>
                    </div>
                </div>
                <div class="p-3">
                    <div id="activityTrendChart" style="min-height: 320px; width: 100%;"></div>
                </div>
            </div>

            <!-- 2 Kolonlu Grafik & Analiz Kartları -->
            <div class="row mx-0">
                <!-- Sol Kolon: İşlem Türü Dağılımı -->
                <div class="col-lg-6 col-md-12 mb-4 px-1">
                    <div class="form-card h-100">
                        <div class="form-card-header d-flex justify-content-between align-items-center">
                            <div class="header-left-inner">
                                <div class="card-icon icon-sky">
                                    <i class="fa fa-pie-chart"></i>
                                </div>
                                <div>
                                    <h5>İşlem Türü Dağılımı</h5>
                                    <p>Son 30 günde gerçekleştirilen aksiyon oranları</p>
                                </div>
                            </div>
                            <span class="badge badge-log-update">Son 30 Gün</span>
                        </div>
                        <div class="p-3 d-flex align-items-center justify-content-center">
                            <div id="eventTypeChart" style="width: 100%; min-height: 290px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon: Modül Bazlı Aktivite -->
                <div class="col-lg-6 col-md-12 mb-4 px-1">
                    <div class="form-card h-100">
                        <div class="form-card-header d-flex justify-content-between align-items-center">
                            <div class="header-left-inner">
                                <div class="card-icon icon-primary">
                                    <i class="fa fa-cubes"></i>
                                </div>
                                <div>
                                    <h5>Modül Bazlı İşlem Yoğunluğu</h5>
                                    <p>En çok hareket gören iş modülleri</p>
                                </div>
                            </div>
                            <span class="badge badge-log-status">Modül Hacmi</span>
                        </div>
                        <div class="p-3">
                            <div id="moduleBarChart" style="min-height: 290px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- En Aktif Kullanıcılar & Son Kritik Olaylar (Minimal & Kompakt Tasarım) -->
            <div class="row mx-0">
                <!-- En Aktif Kullanıcılar Leaderboard (Minimal Tablo) -->
                <div class="col-lg-6 col-md-12 mb-4 px-1">
                    <div class="form-card h-100">
                        <div class="form-card-header d-flex justify-content-between align-items-center">
                            <div class="header-left-inner">
                                <div class="card-icon icon-amber">
                                    <i class="fa fa-trophy"></i>
                                </div>
                                <div>
                                    <h5>En Aktif Kullanıcılar (Top 5)</h5>
                                    <p>Son 30 günde en çok işlem gerçekleştiren ekip üyeleri</p>
                                </div>
                            </div>
                            <span class="badge badge-log-export">Liderlik</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($top_users_raw)): ?>
                                <div class="text-center text-muted p-4 font-13">Kullanıcı işlem verisi bulunamadı.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-modern table-hover m-0 no-filter">
                                        <thead>
                                            <tr>
                                                <th class="col-fit text-center">#</th>
                                                <th class="col-main">Kullanıcı</th>
                                                <th class="col-fit text-center">İşlem</th>
                                                <th class="col-fit text-center" style="min-width: 100px;">Yoğunluk</th>
                                                <th class="col-fit text-right">Son Hareket</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $rank = 1;
                                            foreach ($top_users_raw as $tu): 
                                                $u_name = getUsername($tu['uid']) ?: 'Kullanıcı #' . $tu['uid'];
                                                $pct = round(($tu['total_ops'] / max(1, $top_user_max)) * 100);
                                                $initial = mb_strtoupper(mb_substr($u_name, 0, 1, 'UTF-8'), 'UTF-8');
                                                $rankClass = $rank === 1 ? 'rank-badge-1' : ($rank === 2 ? 'rank-badge-2' : ($rank === 3 ? 'rank-badge-3' : 'rank-badge-default'));
                                            ?>
                                                <tr>
                                                    <td class="col-fit text-center">
                                                        <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></span>
                                                    </td>
                                                    <td class="col-main">
                                                        <div class="d-flex align-items-center">
                                                            <span class="user-mini-avatar mr-2 font-11"><?php echo $initial; ?></span>
                                                            <span class="weight-600 font-12 text-dark cell-ellipsis" title="<?php echo htmlspecialchars($u_name, ENT_QUOTES, 'UTF-8'); ?>">
                                                                <?php echo htmlspecialchars($u_name, ENT_QUOTES, 'UTF-8'); ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="col-fit text-center">
                                                        <span class="badge badge-log-create font-11 px-2 py-1"><?php echo number_format($tu['total_ops']); ?></span>
                                                    </td>
                                                    <td class="col-fit text-center">
                                                        <div class="d-flex align-items-center justify-content-center" style="gap: 6px;">
                                                            <div class="progress flex-grow-1" style="height: 5px; width: 45px; border-radius: 3px; background: #e2e8f0;">
                                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pct; ?>%;"></div>
                                                            </div>
                                                            <span class="font-11 text-muted weight-600">%<?php echo $pct; ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="col-fit text-right text-nowrap">
                                                        <span class="text-muted font-11"><i class="fa fa-clock-o mr-1"></i><?php echo formatRelativeTime($tu['last_active']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php 
                                                $rank++;
                                            endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Son Kritik & Güvenlik Hareketleri Akışı (Minimal Tablo) -->
                <div class="col-lg-6 col-md-12 mb-4 px-1">
                    <div class="form-card h-100">
                        <div class="form-card-header d-flex justify-content-between align-items-center">
                            <div class="header-left-inner">
                                <div class="card-icon icon-rose">
                                    <i class="fa fa-shield"></i>
                                </div>
                                <div>
                                    <h5>Son Kritik Sistem Hareketleri</h5>
                                    <p>Silme, hata ve durum değişikliklerinin anlık akışı</p>
                                </div>
                            </div>
                            <a href="index.php?p=logs/index&tab=logs&filter_event=delete" class="btn btn-sm btn-link text-primary font-12 p-0">Tümünü İncele <i class="fa fa-angle-right"></i></a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($critical_recent)): ?>
                                <div class="text-center text-muted p-4 font-13">Son dönemde kayıtlı kritik olay bulunmuyor.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-modern table-hover m-0 no-filter">
                                        <thead>
                                            <tr>
                                                <th class="col-fit">İşlem Türü</th>
                                                <th class="col-main">Açıklama / Detay</th>
                                                <th class="col-fit">Kullanıcı</th>
                                                <th class="col-fit text-right">Zaman</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($critical_recent as $cr): 
                                                $cr_uid = $cr['user_id'] ?: $cr['author'];
                                                $cr_uname = getUsername($cr_uid) ?: 'Sistem / Kullanıcı #' . $cr_uid;
                                                $cr_event = $cr['event_type'] ?? 'Aksiyon';
                                                $cr_badge = $event_badges[$cr_event] ?? 'badge-log-view';
                                                $cr_icon = $event_icons[$cr_event] ?? 'fa fa-circle';
                                                $cr_date = !empty($cr['created_at']) ? formatRelativeTime($cr['created_at']) : $cr['dates'];
                                                $cr_msg = !empty($cr['summary']) ? $cr['summary'] : ($cr['message'] ?: $cr['action']);
                                            ?>
                                                <tr>
                                                    <td class="col-fit">
                                                        <span class="badge <?php echo $cr_badge; ?> font-11 px-2 py-1">
                                                            <i class="<?php echo $cr_icon; ?> mr-1"></i><?php echo htmlspecialchars($event_labels[$cr_event] ?? $cr_event, ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="col-main">
                                                        <div class="cell-ellipsis font-12 text-dark weight-500" title="<?php echo htmlspecialchars($cr_msg, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($cr_msg, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </td>
                                                    <td class="col-fit text-nowrap">
                                                        <span class="font-11 text-muted"><i class="fa fa-user mr-1"></i><?php echo htmlspecialchars($cr_uname, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </td>
                                                    <td class="col-fit text-right text-nowrap">
                                                        <span class="font-11 text-muted"><i class="fa fa-clock-o mr-1"></i><?php echo $cr_date; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- 4. AKTİVİTE GÜNLÜĞÜ VE GELİŞMİŞ FİLTRELEME (TAB = LOGS) -->
        <!-- ========================================================================= -->
        <div class="logs-table-view <?php echo !in_array($active_tab, ['logs', 'archive'], true) ? 'd-none' : ''; ?>">
            
            <!-- Teklifler Tarzı Liste Card -->
            <div class="form-card animate-fade-in mx-1 mb-4">
                <div class="form-card-header d-flex justify-content-between align-items-center">
                    <div class="header-left-inner">
                        <div class="card-icon">
                            <i class="fa fa-list"></i>
                        </div>
                        <div>
                            <h5><?php echo $active_tab === 'archive' ? 'Arşivlenmiş Aktiviteler' : 'Aktivite Listesi & Filtreleme'; ?></h5>
                            <p><?php echo $active_tab === 'archive' ? 'Saklama politikası kapsamında aktif tablodan taşınan geçmiş kayıtlar' : 'Anlık arama, sütun filtreleme ve sistem işlem kayıtları'; ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <button type="button" id="btnToggleFilters" class="btn btn-outline-secondary btn-action-outline" style="height: 34px;">
                            <i class="fa fa-filter"></i> <span class="d-none d-sm-inline">Filtreleri Göster / Gizle</span>
                        </button>
                        <a href="index.php?p=logs/index&tab=<?php echo $list_tab; ?>" class="btn btn-outline-secondary btn-action-outline" style="height: 34px;" title="Filtreleri Sıfırla">
                            <i class="fa fa-undo"></i> <span class="d-none d-sm-inline">Sıfırla</span>
                        </a>
                    </div>
                </div>
                
                <!-- Filtreleme Alanı (Varsayılan Olarak Gizli) -->
                <div id="filtersCollapse" class="filters-form p-3" style="display: none; border-bottom: 1px solid #e5e7eb;">
                    <form method="GET" action="index.php" id="activityFilterForm">
                        <input type="hidden" name="p" value="logs/index">
                        <input type="hidden" name="tab" value="<?php echo $list_tab; ?>">
                        <div class="row">
                            <!-- Kullanıcı -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">Kullanıcı</label>
                                <select name="filter_user" class="form-control activity-filter-select" style="width: 100%;">
                                    <option value="">Tüm Kullanıcılar</option>
                                    <?php foreach ($all_users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['filter_user']) && $_GET['filter_user'] == $u['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- İşlem Türü -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">İşlem Türü</label>
                                <select name="filter_event" class="form-control activity-filter-select" style="width: 100%;">
                                    <option value="all" <?php echo $selected_event === 'all' ? 'selected' : ''; ?>>Tümü (Ziyaretler Dahil)</option>
                                    <option value="operations" <?php echo $selected_event === 'operations' ? 'selected' : ''; ?>>Kullanıcı İşlemleri (CRUD)</option>
                                    <?php foreach ($event_labels as $event_key => $event_label): ?>
                                        <option value="<?php echo htmlspecialchars($event_key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_event === $event_key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Modül -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">Modül</label>
                                <select name="filter_module" class="form-control activity-filter-select" style="width: 100%;">
                                    <option value="">Tüm Modüller</option>
                                    <?php foreach ($all_modules as $module_name): ?>
                                        <option value="<?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($_GET['filter_module'] ?? '') === $module_name) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($module_name), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Log Seviyesi -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">Log Seviyesi</label>
                                <select name="filter_level" class="form-control activity-filter-select" style="width: 100%;">
                                    <option value="">Tüm Seviyeler</option>
                                    <option value="INFO" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'INFO') ? 'selected' : ''; ?>>INFO (Normal)</option>
                                    <option value="WARNING" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'WARNING') ? 'selected' : ''; ?>>WARNING (Uyarı)</option>
                                    <option value="ERROR" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'ERROR') ? 'selected' : ''; ?>>ERROR (Hata)</option>
                                    <option value="CRITICAL" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'CRITICAL') ? 'selected' : ''; ?>>CRITICAL (Kritik)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row align-items-end">
                            <!-- Başlangıç Tarihi -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <input type="text" name="filter_start_date" class="form-control date-picker" value="<?php echo htmlspecialchars($_GET['filter_start_date'] ?? ''); ?>" placeholder="gg-aa-yyyy" autocomplete="off">
                            </div>
                            <!-- Bitiş Tarihi -->
                            <div class="col-md-3 mb-10">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="text" name="filter_end_date" class="form-control date-picker" value="<?php echo htmlspecialchars($_GET['filter_end_date'] ?? ''); ?>" placeholder="gg-aa-yyyy" autocomplete="off">
                            </div>
                            <!-- Arama -->
                            <div class="col-md-4 mb-10">
                                <label class="form-label">Anahtar Kelime</label>
                                <input type="text" name="filter_search" class="form-control" placeholder="İşlem detayı, URL, IP adresi..." value="<?php echo htmlspecialchars($_GET['filter_search'] ?? ''); ?>">
                            </div>
                            <!-- Filtrele Butonu -->
                            <div class="col-md-2 mb-10">
                                <button type="submit" class="btn btn-success btn-block" style="border-radius: 8px; padding: 8px 20px; font-weight: 600;">
                                    <i class="fa fa-search mr-1"></i> ARA
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger m-3"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <!-- Tablo Alanı (DataTables Server-Side AJAX) -->
                <div class="responsive p-2">
                    <table id="logsTable" class="data-table table-hover table-bordered" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 14%; min-width: 125px;" data-filter-type="date">Tarih / Saat</th>
                                <th style="width: 15%; min-width: 130px;" data-filter-type="select">Kullanıcı</th>
                                <th style="width: 14%; min-width: 115px;" data-filter-type="select">İşlem Türü</th>
                                <th style="width: 12%; min-width: 100px;" class="log-col-module" data-filter-type="select">Modül</th>
                                <th style="width: 33%; min-width: 240px;" data-filter-type="text">Yapılan İşlem / Detay</th>
                                <th style="width: 12%; min-width: 110px;" class="log-col-entity" data-filter-type="text">İlgili Kayıt</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. GELİŞMİŞ LOG DETAY MODALI -->
<!-- ========================================================================= -->
<div class="modal fade" id="logDetailModal" tabindex="-1" role="dialog" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content custom-log-modal-content">
            <div class="modal-header custom-log-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="modal-header-icon mr-3">
                        <i class="fa fa-shield"></i>
                    </div>
                    <div>
                        <h5 class="modal-title weight-700 font-16 mb-0" id="logDetailModalLabel">Aktivite & İşlem Detayları</h5>
                        <span class="font-11 text-muted" id="modal-date-subtitle">-</span>
                    </div>
                </div>
                <button type="button" class="close btn-log-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Üst Özet Kartları -->
                <div class="row mb-3">
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Kullanıcı</span>
                            <strong id="modal-user" class="meta-val">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">İşlem Türü</span>
                            <strong id="modal-event" class="meta-val">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Modül / Kayıt</span>
                            <strong class="meta-val"><span id="modal-module">-</span> · <span id="modal-entity">-</span></strong>
                        </div>
                    </div>
                </div>

                <!-- İşlem Özeti -->
                <div class="summary-highlight-box mb-3 p-3">
                    <span class="d-block font-11 text-muted uppercase weight-600 mb-1">İşlem Özeti / Mesaj</span>
                    <strong id="modal-summary" class="font-14 text-dark d-block">-</strong>
                </div>

                <!-- İstek & Ağ Bilgileri -->
                <div class="row mb-3">
                    <div class="col-sm-4 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">Log Seviyesi</span>
                            <span id="modal-level" class="badge badge-log-login font-11">-</span>
                        </div>
                    </div>
                    <div class="col-sm-8 mb-2">
                        <div class="modal-meta-box">
                            <span class="meta-label">İstemci IP & Metod</span>
                            <strong class="font-12 font-mono"><span id="modal-ip">-</span> (<span id="modal-method">-</span>)</strong>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="modal-meta-box">
                        <span class="meta-label">İşlem URL</span>
                        <span id="modal-url" class="font-12 font-mono break-all text-muted">-</span>
                    </div>
                </div>

                <!-- Değişen Alanlar (Diff Viewer) -->
                <div id="modal-changes" class="mb-3" style="display:none;">
                    <span class="d-block font-12 text-muted uppercase weight-600 mb-2">
                        <i class="fa fa-exchange mr-1 text-primary"></i>Değişen Alanlar (Önceki / Yeni)
                    </span>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 modal-diff-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%">Alan Adı</th>
                                    <th style="width: 35%">Önceki Değer</th>
                                    <th style="width: 35%">Yeni Değer</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- JSON Context Verisi -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-12 text-muted uppercase weight-600">
                            <i class="fa fa-code mr-1"></i>Teknik Context / JSON Verisi
                        </span>
                        <button type="button" class="btn btn-xs btn-outline-secondary font-11" id="btnCopyJson">
                            <i class="fa fa-copy mr-1"></i>Panoya Kopyala
                        </button>
                    </div>
                    <pre id="modal-json" class="modal-json-viewer p-3 border-radius-8 font-12 font-mono m-0 overflow-auto">-</pre>
                </div>

            </div>
            <div class="modal-footer custom-log-modal-footer d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-secondary px-4 font-13 weight-600 btn-log-modal-close" data-dismiss="modal" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. PREMIUM THEME CSS STYLES (NET, CANLI, VİVİD ROZETLER & YATAY SAYFALAMA)-->
<!-- ========================================================================= -->
<style>
/* Base Wrapper & Cards */
.logs-master-wrapper {
    font-family: var(--font-sans, 'Geist', sans-serif);
    width: 100%;
}

.logs-hero-banner {
    position: relative;
    border-radius: 14px;
    padding: 24px 28px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.1);
    color: #ffffff;
    overflow: hidden;
}

.logs-tab-switch-group {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 4px;
    display: inline-flex;
    gap: 4px;
}

.logs-switch-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #cbd5e1 !important;
    text-decoration: none !important;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.logs-switch-btn:hover {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.1);
}

.logs-switch-btn.active {
    background: #3b82f6;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
}

/* Teklifler Tarzı Form Card */
.form-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.form-card-header {
    padding: 14px 20px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
}

.form-card-header .header-left-inner {
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-card-header .card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.form-card-header h5 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}

.form-card-header p {
    margin: 1px 0 0 0;
    font-size: 11.5px;
    color: #64748b;
}

.btn-action-outline {
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s ease;
}

.filters-form .form-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 5px;
    display: block;
}

.filters-form .form-control,
.filters-form .bootstrap-select .btn {
    border-radius: 8px !important;
    border: 1.5px solid #e5e7eb !important;
    padding: 7px 12px;
    font-size: 13px;
    background: #fafafa;
}

/* ========================================================================= */
/* VİVİD & HIGH CONTRAST ROZETLER (100% NET, CANLI VE OKUNABİLİR)            */
/* ========================================================================= */
.data-table .badge-log-view,
.badge-log-view {
    background-color: #64748b !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(100, 116, 139, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-login,
.badge-log-login {
    background-color: #16a34a !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(22, 163, 74, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-logout,
.badge-log-logout {
    background-color: #475569 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    border: none !important;
}

.data-table .badge-log-create,
.badge-log-create {
    background-color: #2563eb !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-update,
.badge-log-update {
    background-color: #0284c7 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-delete,
.badge-log-delete {
    background-color: #dc2626 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(220, 38, 38, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-export,
.badge-log-export {
    background-color: #d97706 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(217, 119, 6, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-copy,
.badge-log-copy {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    border: none !important;
}

.data-table .badge-log-status,
.badge-log-status {
    background-color: #9333ea !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(147, 51, 234, 0.3) !important;
    border: none !important;
}

.data-table .badge-log-upload,
.badge-log-upload {
    background-color: #0d9488 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    border: none !important;
}

.data-table .badge-log-download,
.badge-log-download {
    background-color: #0891b2 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    border: none !important;
}

.data-table .badge-log-error,
.badge-log-error {
    background-color: #e11d48 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    padding: 4px 9px !important;
    border-radius: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    box-shadow: 0 1px 3px rgba(225, 29, 72, 0.3) !important;
    border: none !important;
}

.blink-dot {
    animation: blinkAnimation 1.5s infinite;
}
@keyframes blinkAnimation {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* ========================================================================= */
/* DATA TABLES PAGINATION - KESİN YATAY HİZALAMA VE STİL                     */
/* ========================================================================= */
.logs-pagination-row .pagination,
.dataTables_paginate ul.pagination {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-end !important;
    list-style: none !important;
    padding-left: 0 !important;
    margin: 0 !important;
    gap: 3px !important;
}

.logs-pagination-row .pagination .page-item,
.dataTables_paginate ul.pagination .page-item {
    display: inline-flex !important;
    margin: 0 !important;
    padding: 0 !important;
    list-style-type: none !important;
}

.logs-pagination-row .pagination .page-link,
.dataTables_paginate ul.pagination .page-link {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 10px !important;
    border-radius: 6px !important;
    border: 1px solid #e2e8f0 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background-color: #ffffff !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
}

.logs-pagination-row .pagination .page-link:hover,
.dataTables_paginate ul.pagination .page-link:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
    border-color: #cbd5e1 !important;
}

.logs-pagination-row .pagination .page-item.active .page-link,
.dataTables_paginate ul.pagination .page-item.active .page-link {
    background-color: var(--theme-primary, #2563eb) !important;
    border-color: var(--theme-primary, #2563eb) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px var(--theme-primary-shadow, rgba(37, 99, 235, 0.3)) !important;
}

.logs-pagination-row .pagination .page-item.disabled .page-link,
.dataTables_paginate ul.pagination .page-item.disabled .page-link {
    color: #94a3b8 !important;
    background-color: #f8fafc !important;
    border-color: #e2e8f0 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

/* ========================================================================= */
/* MODERN COMPACT TABLES & RANK BADGES (DASHBOARDS İLE BİREBİR UYUMLU)       */
/* ========================================================================= */
.table-modern {
    width: 100% !important;
    margin-bottom: 0 !important;
}
.table-modern thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    border-bottom: 2px solid #e2e8f0;
    padding: 8px 10px;
    vertical-align: middle;
    white-space: nowrap;
}
.table-modern tbody td {
    padding: 8px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
}
.table-modern tbody tr:last-child td {
    border-bottom: none;
}
.table-modern tbody tr:hover td {
    background: #f8fafc;
}
.table-modern .col-fit {
    width: 1%;
    white-space: nowrap;
}
.table-modern .col-main {
    width: 100%;
    max-width: 0;
}
.table-modern .col-main .cell-ellipsis,
.cell-ellipsis {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    width: 100%;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
}
.rank-badge-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.rank-badge-2 { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.rank-badge-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.rank-badge-default { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

.user-mini-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #475569;
    font-weight: 700;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.module-tag {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 2px 6px;
    color: #334155;
    font-weight: 500;
    display: inline-block;
}

.entity-pill {
    background: #f1f5f9;
    border-radius: 10px;
    padding: 2px 7px;
    color: #475569;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    display: inline-block;
}

.btn-detail-toggle {
    border-radius: 5px !important;
    padding: 2px 7px !important;
    font-weight: 600;
    white-space: nowrap;
}

/* ========================================================================= */
/* LOG DETAIL MODAL - ULTRA CLEAN, MODERN & ROUNDED (RADIUS)                 */
/* ========================================================================= */
#logDetailModal .modal-dialog {
    max-width: 820px;
    margin: 1.75rem auto;
}

#logDetailModal .modal-content,
.custom-log-modal-content {
    border-radius: 16px !important;
    overflow: hidden !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    background: #ffffff !important;
}

#logDetailModal .modal-header,
.custom-log-modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 18px 24px !important;
    border-top-left-radius: 16px !important;
    border-top-right-radius: 16px !important;
}

#logDetailModal .modal-title {
    color: #0f172a !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    margin: 0 !important;
    display: block !important;
}

#logDetailModal .modal-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    border: 1px solid #dbeafe;
    flex-shrink: 0;
}

#logDetailModal .modal-body {
    padding: 24px !important;
    background: #ffffff !important;
}

#logDetailModal .modal-footer,
.custom-log-modal-footer {
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 14px 24px !important;
    border-bottom-left-radius: 16px !important;
    border-bottom-right-radius: 16px !important;
}

#logDetailModal .btn-log-modal-close {
    font-size: 24px;
    color: #64748b;
    opacity: 0.75;
    transition: all 0.2s ease;
    border: none;
    background: transparent;
    cursor: pointer;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 6px;
}

#logDetailModal .btn-log-modal-close:hover {
    color: #0f172a;
    background: #f1f5f9;
    opacity: 1;
}

.modal-meta-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    height: 100%;
}

.modal-meta-box .meta-label {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 3px;
    letter-spacing: 0.3px;
}

.modal-meta-box .meta-val {
    font-size: 13px;
    color: #0f172a;
    font-weight: 600;
}

.summary-highlight-box {
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
    border-top: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
}

.modal-diff-table {
    font-size: 12px;
    border-radius: 8px;
    overflow: hidden;
}

.modal-diff-table th {
    background: #f1f5f9;
    font-weight: 600;
    color: #475569;
}

.modal-json-viewer {
    background: #0f172a;
    color: #38bdf8;
    max-height: 250px;
    border-radius: 10px;
}

/* ========================================================================= */
/* DARK MODE OVERRIDES (TEKLİFLERLE TAM UYUMLU)                             */
/* ========================================================================= */
.dark-mode .form-card,
.dark-mode .form-card-header,
.dark-mode .modal-content,
.dark-mode #logDetailModal .modal-content,
.dark-mode .custom-log-modal-content {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
.dark-mode .form-card-header h5,
.dark-mode #logDetailModal .modal-title,
.dark-mode .modal-meta-box .meta-val,
.dark-mode #modal-summary,
.dark-mode .user-chip-cell span {
    color: #f8fafc !important;
}
.dark-mode #logDetailModal .modal-header,
.dark-mode .custom-log-modal-header {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}
.dark-mode #logDetailModal .modal-header-icon {
    background: #0f172a !important;
    color: #60a5fa !important;
    border-color: #334155 !important;
}
.dark-mode #logDetailModal .modal-body {
    background: #1e293b !important;
}
.dark-mode #logDetailModal .btn-log-modal-close {
    color: #94a3b8;
}
.dark-mode #logDetailModal .btn-log-modal-close:hover {
    color: #ffffff;
    background: #334155;
}
.dark-mode .filters-form {
    border-bottom-color: #334155 !important;
}
.dark-mode .filters-form .form-label {
    color: #cbd5e1 !important;
}
.dark-mode .filters-form .form-control,
.dark-mode .filters-form .bootstrap-select .btn {
    background: #0f172a !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
}
.dark-mode .table-modern thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
}
.dark-mode .table-modern tbody td {
    border-bottom-color: #334155 !important;
    color: #e2e8f0 !important;
}
.dark-mode .table-modern tbody tr:hover td {
    background: #1e293b !important;
}
.dark-mode .rank-badge-1 { background: rgba(254, 243, 199, 0.15); color: #f59e0b; border-color: rgba(253, 230, 138, 0.3); }
.dark-mode .rank-badge-2 { background: rgba(241, 245, 249, 0.15); color: #cbd5e1; border-color: rgba(226, 232, 240, 0.3); }
.dark-mode .rank-badge-3 { background: rgba(255, 237, 213, 0.15); color: #fb923c; border-color: rgba(254, 215, 170, 0.3); }
.dark-mode .rank-badge-default { background: #0f172a; color: #94a3b8; border-color: #334155; }

.dark-mode .modal-meta-box,
.dark-mode .summary-highlight-box,
.dark-mode .modal-footer,
.dark-mode #logDetailModal .modal-footer,
.dark-mode .custom-log-modal-footer {
    background: #0f172a !important;
    border-color: #334155 !important;
}
.dark-mode .module-tag,
.dark-mode .entity-pill {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .user-mini-avatar {
    background: #334155;
    color: #f8fafc;
}
.dark-mode .modal-diff-table th {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .modal-diff-table td {
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .logs-pagination-row .pagination .page-link {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .logs-pagination-row .pagination .page-item.active .page-link {
    background-color: var(--theme-primary, #2563eb) !important;
    border-color: var(--theme-primary, #2563eb) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px var(--theme-primary-shadow, rgba(37, 99, 235, 0.3)) !important;
}

@media (max-width: 991px) {
    .log-col-module { display: none; }
}
@media (max-width: 767px) {
    .log-col-entity { display: none; }
}
</style>

<!-- ========================================================================= -->
<!-- 7. APEXCHARTS & INTERAKTİF JS SCRIPTLERİ -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {
    // Aktivite filtrelerindeki tüm seçim alanlarını Select2 ile etkinleştir.
    if ($.fn.select2) {
        $('#activityFilterForm .activity-filter-select').select2({
            width: '100%',
            minimumResultsForSearch: 0,
            language: {
                noResults: function() {
                    return 'Sonuç bulunamadı';
                },
                searching: function() {
                    return 'Aranıyor...';
                }
            }
        });
    }

    // Filtre Göster / Gizle Butonu
    $('#btnToggleFilters').on('click', function() {
        $('#filtersCollapse').slideToggle(200);
    });

    // Global Modal Kapatma
    window.closeLogDetailModal = function() {
        var $modal = $('#logDetailModal');
        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
        }
        var modalElement = document.getElementById('logDetailModal');
        if (window.bootstrap && window.bootstrap.Modal && modalElement) {
            var modalInstance = window.bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    };

    $(document).on('click', '.btn-log-modal-close, [data-dismiss="modal"], [data-bs-dismiss="modal"]', function(event) {
        if ($(this).closest('#logDetailModal').length) {
            event.preventDefault();
            window.closeLogDetailModal();
        }
    });

    // Global Detay Modalını Aç
    window.openLogDetail = function(triggerEl) {
        var btn = $(triggerEl);
        if (!btn.length) return;

        var jsonVal = btn.attr('data-json') || btn.data('json') || '';
        var ip = btn.attr('data-ip') || btn.data('ip') || '-';
        var url = btn.attr('data-url') || btn.data('url') || '-';
        var method = btn.attr('data-method') || btn.data('method') || '-';
        var level = btn.attr('data-level') || btn.data('level') || 'INFO';
        var date = btn.attr('data-date') || btn.data('date') || '-';
        var user = btn.attr('data-user') || btn.data('user') || '-';
        var eventName = btn.attr('data-event') || btn.data('event') || 'İşlem';
        var moduleName = btn.attr('data-module') || btn.data('module') || '-';
        var entityName = btn.attr('data-entity') || btn.data('entity') || '-';
        var summary = btn.attr('data-summary') || btn.data('summary') || '-';
        
        var formattedJson = "-";
        var detailData = {};
        try {
            if (jsonVal && typeof jsonVal === 'string' && jsonVal.trim() !== '') {
                var parsed = JSON.parse(jsonVal);
                if (parsed && typeof parsed === 'object') {
                    detailData = (parsed.context && parsed.context.data && typeof parsed.context.data === 'object')
                        ? parsed.context.data
                        : ((parsed.context && typeof parsed.context === 'object') ? parsed.context : parsed);
                }
            } else if (jsonVal && typeof jsonVal === 'object') {
                detailData = jsonVal;
            }
            
            if (detailData && typeof detailData === 'object' && Object.keys(detailData).length > 0) {
                formattedJson = JSON.stringify(detailData, null, 4);
            } else {
                formattedJson = 'Bu işlem için ek veri bulunmuyor.';
            }
        } catch(e) {
            formattedJson = (typeof jsonVal === 'string' && jsonVal.trim() !== '') ? jsonVal : 'Ek veri bulunmuyor.';
            detailData = {};
        }

        if (!detailData || typeof detailData !== 'object') {
            detailData = {};
        }

        $('#modal-date-subtitle').text(date);
        $('#modal-user').text(user);
        $('#modal-ip').text(ip);
        $('#modal-url').text(url);
        $('#modal-method').text(method);
        $('#modal-json').text(formattedJson);
        $('#modal-event').text(eventName);
        $('#modal-module').text(moduleName);
        $('#modal-entity').text(entityName);
        $('#modal-summary').text(summary);

        var changesBox = $('#modal-changes');
        var changesBody = changesBox.find('tbody').empty();
        var changedFields = (detailData && typeof detailData.changed_fields === 'object' && detailData.changed_fields !== null) ? detailData.changed_fields : {};
        var fieldLabels = {
            company: 'Firma Adı',
            email: 'E-posta',
            address: 'Adres',
            city: 'İl',
            ilce: 'İlçe',
            gsm: 'Telefon',
            yetkili: 'Yetkili',
            grp: 'Firma Grubu',
            OdemeVade: 'Ödeme Vadesi',
            region: 'Bölge',
            represant: 'Satış Temsilcisi',
            offerNumber: 'Teklif No',
            cid: 'Firma',
            company_authors: 'Firma Yetkilileri',
            offer_subject: 'Teklif Konusu',
            currency: 'Para Birimi',
            payment_period: 'Ödeme Dönemi',
            statu: 'Durum',
            description: 'Açıklama',
            offer_date: 'Teklif Tarihi',
            total_price: 'Toplam Tutar',
            price: 'Fiyat',
            pstatu: 'Servis Durumu'
        };

        var hasChanges = Object.keys(changedFields).length > 0;
        if (hasChanges) {
            Object.keys(changedFields).forEach(function(field) {
                var change = changedFields[field] || {};
                var row = $('<tr>');
                $('<td>').addClass('weight-600').text(fieldLabels[field] || field).appendTo(row);
                $('<td>').html('<span class="badge badge-log-view">' + (change.old === null || change.old === undefined || change.old === '' ? '(Boş)' : change.old) + '</span>').appendTo(row);
                $('<td>').html('<span class="badge badge-log-create">' + (change.new === null || change.new === undefined || change.new === '' ? '(Boş)' : change.new) + '</span>').appendTo(row);
                changesBody.append(row);
            });
            changesBox.show();
        } else {
            changesBox.hide();
        }

        var badge = $('#modal-level');
        badge.text(level);
        badge.removeClass('badge-log-login badge-log-export badge-log-delete badge-log-create badge-log-view');
        
        if (level === 'INFO') badge.addClass('badge-log-login');
        else if (level === 'WARNING') badge.addClass('badge-log-export');
        else if (['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].indexOf(level) !== -1) badge.addClass('badge-log-delete');
        else if (level === 'DEBUG') badge.addClass('badge-log-create');
        else badge.addClass('badge-log-view');

        var modalElement = document.getElementById('logDetailModal');
        if (modalElement && modalElement.parentNode !== document.body) {
            document.body.appendChild(modalElement);
        }

        if (typeof $('#logDetailModal').modal === 'function') {
            $('#logDetailModal').modal('show');
        } else if (window.bootstrap && window.bootstrap.Modal && modalElement) {
            var modalInstance = (typeof window.bootstrap.Modal.getOrCreateInstance === 'function')
                ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
                : (window.bootstrap.Modal.getInstance(modalElement) || new window.bootstrap.Modal(modalElement));
            modalInstance.show();
        }
    };

    // Detay Modalını Aç (Event Delegation)
    $(document).on('click', '.btn-detail-toggle', function(event) {
        event.preventDefault();
        window.openLogDetail(this);
    });

    // Panoya Kopyalama
    $('#btnCopyJson').on('click', function() {
        var jsonText = $('#modal-json').text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(jsonText).then(function() {
                if (window.toastr) toastr.success('JSON verisi panoya kopyalandı.');
                else alert('Kopyalandı!');
            });
        }
    });

    // Tooltipleri aktif et
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // ─── LOGS SERVER-SIDE DATATABLE ───
    if ($('#logsTable').length) {
        var logsTable = $('#logsTable').DataTable({
            retrieve: true,
            processing: false,
            serverSide: true,
            pageLength: 50,
            lengthMenu: [[25, 50, 100, 250], [25, 50, 100, 250]],
            ajax: {
                url: 'App/api/get-logs.php',
                type: 'POST',
                data: function(d) {
                    d.filters = {
                        filter_user: $('select[name="filter_user"]').val(),
                        filter_event: $('select[name="filter_event"]').val(),
                        filter_module: $('select[name="filter_module"]').val(),
                        filter_level: $('select[name="filter_level"]').val(),
                        filter_start_date: $('input[name="filter_start_date"]').val(),
                        filter_end_date: $('input[name="filter_end_date"]').val(),
                        filter_search: $('input[name="filter_search"]').val()
                    };
                    d.source = <?php echo json_encode($active_tab === 'archive' ? 'archive' : 'active'); ?>;
                },
                error: function(xhr, error, thrown) {
                    console.error("DataTables Logs AJAX Error:", xhr, error, thrown);
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 0, orderable: true },
                { data: 1, orderable: true },
                { data: 2, orderable: true },
                { data: 3, orderable: true },
                { data: 4, orderable: false },
                { data: 5, orderable: false }
            ],
            language: {
                emptyTable: "Kayıt bulunamadı.",
                info: "Toplam <strong>_TOTAL_</strong> kayıttan <strong>_START_ - _END_</strong> arası gösteriliyor.",
                infoEmpty: "Kayıt yok",
                infoFiltered: "(_MAX_ kayıt içerisinden filtrelendi)",
                lengthMenu: "Sayfada _MENU_ kayıt göster",
                loadingRecords: "Veriler Yükleniyor...",
                processing: "İşleniyor...",
                search: "Listede ara:",
                zeroRecords: "Eşleşen kayıt bulunamadı",
                paginate: {
                    first: "İlk",
                    last: "Son",
                    next: "Sonraki",
                    previous: "Önceki"
                }
            }
        });

        $('#activityFilterForm').on('submit', function(e) {
            e.preventDefault();
            logsTable.ajax.reload();
        });
    }

    <?php if ($active_tab === 'dashboard'): ?>
    // ─── APEXCHARTS İNİTİALİZATİON ───
    var isDarkMode = $('body').hasClass('dark-mode') || $('html').hasClass('dark-mode');
    var chartThemeMode = isDarkMode ? 'dark' : 'light';

    // 1. Aktivite Trend Çizgisi (Spline Area Chart)
    var trendOptions = {
        series: [
            {
                name: 'Kullanıcı İşlemleri (CRUD)',
                data: <?php echo json_encode($trend_ops); ?>
            },
            {
                name: 'Sayfa Gezinmeleri',
                data: <?php echo json_encode($trend_views); ?>
            },
            {
                name: 'Girişler',
                data: <?php echo json_encode($trend_logins); ?>
            }
        ],
        chart: {
            type: 'area',
            height: 320,
            fontFamily: 'Geist, sans-serif',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        colors: ['#3b82f6', '#94a3b8', '#10b981'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: <?php echo json_encode($trend_dates); ?>,
            labels: {
                style: { colors: '#64748b', fontSize: '12px' }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#64748b', fontSize: '12px' }
            }
        },
        grid: {
            borderColor: isDarkMode ? '#334155' : '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            theme: chartThemeMode
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: isDarkMode ? '#cbd5e1' : '#475569' }
        }
    };
    var trendChart = new ApexCharts(document.querySelector("#activityTrendChart"), trendOptions);
    trendChart.render();

    // 2. İşlem Türü Dağılımı (Donut Chart)
    var donutOptions = {
        series: <?php echo json_encode($donut_series); ?>,
        labels: <?php echo json_encode($donut_labels); ?>,
        chart: {
            type: 'donut',
            height: 290,
            fontFamily: 'Geist, sans-serif'
        },
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#7c3aed', '#ef4444', '#06b6d4', '#64748b', '#8b5cf6'],
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam İşlem',
                            fontSize: '13px',
                            fontWeight: 600,
                            color: isDarkMode ? '#cbd5e1' : '#475569',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            labels: { colors: isDarkMode ? '#cbd5e1' : '#475569' }
        },
        tooltip: { theme: chartThemeMode }
    };
    var donutChart = new ApexCharts(document.querySelector("#eventTypeChart"), donutOptions);
    donutChart.render();

    // 3. Modül Dağılımı (Horizontal Bar Chart)
    var barOptions = {
        series: [{
            name: 'İşlem Hacmi',
            data: <?php echo json_encode($bar_series); ?>
        }],
        chart: {
            type: 'bar',
            height: 290,
            fontFamily: 'Geist, sans-serif',
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
                barHeight: '55%',
                distributed: true
            }
        },
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#7c3aed', '#ec4899', '#06b6d4', '#64748b'],
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: { colors: ['#fff'], fontSize: '11px', fontWeight: 600 },
            formatter: function (val, opt) {
                return val.toLocaleString();
            },
            offsetX: 0
        },
        xaxis: {
            categories: <?php echo json_encode($bar_categories); ?>,
            labels: {
                style: { colors: '#64748b', fontSize: '11px' }
            }
        },
        yaxis: {
            labels: {
                style: { colors: isDarkMode ? '#cbd5e1' : '#334155', fontSize: '12px', fontWeight: 500 }
            }
        },
        grid: {
            borderColor: isDarkMode ? '#334155' : '#f1f5f9'
        },
        tooltip: { theme: chartThemeMode },
        legend: { show: false }
    };
    var barChart = new ApexCharts(document.querySelector("#moduleBarChart"), barOptions);
    barChart.render();
    <?php endif; ?>
});
</script>
