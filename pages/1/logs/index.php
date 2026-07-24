<?php
if (!in_array(sesset("id"), [1, 12])) {
    header("Location: index.php?error=nopermission");
    exit;
}

$event_labels = [
    'view' => 'Sayfa görüntüleme',
    'login' => 'Giriş',
    'logout' => 'Çıkış',
    'create' => 'Oluşturma',
    'update' => 'Güncelleme',
    'delete' => 'Silme',
    'export' => 'Dışa aktarma',
    'copy' => 'Kopyalama',
    'status_change' => 'Durum değişikliği',
    'upload' => 'Dosya yükleme',
    'download' => 'Dosya indirme',
    'error' => 'Hata',
];

$event_badges = [
    'view' => 'badge-light',
    'login' => 'badge-success',
    'logout' => 'badge-secondary',
    'create' => 'badge-primary',
    'update' => 'badge-info',
    'delete' => 'badge-danger',
    'export' => 'badge-warning',
    'copy' => 'badge-dark',
    'status_change' => 'badge-purple',
    'error' => 'badge-danger',
];

$entity_labels = [
    'user' => 'Kullanıcı',
    'page' => 'Sayfa',
    'customer' => 'Firma',
    'customer_list' => 'Firma listesi',
    'offer' => 'Teklif',
    'service' => 'Servis',
    'service_list' => 'Servis listesi',
    'product' => 'Ürün/Hizmet',
    'purchase' => 'Satın alma',
    'purchase_item' => 'Satın alma kalemi',
    'report' => 'Rapor',
];

// ─── İstatistikleri Hesapla ───
try {
    $total_logs = $ac->query("SELECT COUNT(*) FROM logs")->fetchColumn();
    $total_today = $ac->query("SELECT COUNT(*) FROM logs WHERE event_type IS NOT NULL AND event_type <> 'view' AND DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) = CURDATE()")->fetchColumn();
    $logins_today = $ac->query("SELECT COUNT(*) FROM logs WHERE (event_type = 'login' OR action LIKE '%giriş yaptı%' OR message LIKE '%giriş yaptı%' OR summary LIKE '%giriş yaptı%') AND DATE(COALESCE(created_at, STR_TO_DATE(dates, '%d-%m-%Y'))) = CURDATE()")->fetchColumn();
    $errors_today = $ac->query("SELECT COUNT(*) FROM logs WHERE level IN ('ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY') AND DATE(created_at) = CURDATE()")->fetchColumn();
} catch (PDOException $e) {
    $total_logs = 0;
    $total_today = 0;
    $logins_today = 0;
    $errors_today = 0;
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

// Filtre: İşlem türü. Varsayılan görünüm ziyaret gürültüsünü dışarıda bırakır.
$selected_event = $_GET['filter_event'] ?? 'operations';
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
    // Eğer dd-mm-yyyy formatındaysa (datepicker'dan geldiyse), yyyy-mm-dd formatına çevir
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
    // Eğer dd-mm-yyyy formatındaysa, yyyy-mm-dd formatına çevir
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
    $count_stmt = $ac->prepare("SELECT COUNT(*) FROM logs $where_sql");
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Kayıtları çek
    $logs_stmt = $ac->prepare("SELECT * FROM logs $where_sql ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $logs_stmt->execute($params);
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kullanıcı listesi
    $users_stmt = $ac->query("SELECT id, username FROM users ORDER BY username ASC");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    $modules_stmt = $ac->query("SELECT DISTINCT module FROM logs WHERE module IS NOT NULL AND module <> '' ORDER BY module");
    $all_modules = $modules_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $logs = [];
    $all_users = [];
    $all_modules = [];
    $total_rows = 0;
    $total_pages = 0;
    $error_msg = $e->getMessage();
}

?>

<div class="pd-ltr-20 xs-pd-20-10">
    <div class="min-height-200px">
        
        <!-- Premium Header & Stats Card -->
        <div class="logs-header-card box-shadow mb-4 animate-fade-in">
            <div class="header-overlay"></div>
            <div class="row align-items-center relative-layout">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="header-icon-box">
                        <i class="fa fa-history text-white"></i>
                    </div>
                    <div class="header-title-box">
                        <h4 class="text-white weight-600 mb-1">Sistem Aktiviteleri</h4>
                        <p class="text-light-blue mb-0">Tüm kullanıcı hareketleri ve kritik sistem işlemleri</p>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row stats-row">
                        <!-- Toplam Log -->
                        <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-24 weight-700"><?php echo number_format($total_logs); ?></span>
                                <span class="d-block stat-label text-light-blue font-12">Toplam Log</span>
                            </div>
                        </div>
                        <!-- Bugün Oluşan Log -->
                        <div class="col-sm-3 col-6 mb-2 mb-sm-0">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-24 weight-700"><?php echo number_format($total_today); ?></span>
                                <span class="d-block stat-label text-light-blue font-12">Bugünkü İşlem</span>
                            </div>
                        </div>
                        <!-- Girişler -->
                        <div class="col-sm-3 col-6">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-white font-24 weight-700"><?php echo number_format($logins_today); ?></span>
                                <span class="d-block stat-label text-light-blue font-12">Bugünkü Girişler</span>
                            </div>
                        </div>
                        <!-- Hatalar -->
                        <div class="col-sm-3 col-6">
                            <div class="stat-item text-center">
                                <span class="d-block stat-num text-danger-light font-24 weight-700"><?php echo number_format($errors_today); ?></span>
                                <span class="d-block stat-label text-light-blue font-12">Bugünkü Hatalar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="pd-20 bg-white border-radius-8 box-shadow mb-30 filter-card animate-fade-in">
            <h5 class="text-blue weight-600 mb-3"><i class="fa fa-filter mr-2"></i>Aktivite Filtreleme</h5>
            <form method="GET" action="index.php">
                <input type="hidden" name="p" value="logs/index">
                <div class="row">
                    <!-- Kullanıcı -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">Kullanıcı</label>
                        <select name="filter_user" class="form-control selectpicker" data-style="btn-outline-secondary" data-live-search="true">
                            <option value="">Tüm Kullanıcılar</option>
                            <?php foreach ($all_users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['filter_user']) && $_GET['filter_user'] == $u['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Seviye -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">İşlem Türü</label>
                        <select name="filter_event" class="form-control selectpicker" data-style="btn-outline-secondary">
                            <option value="operations" <?php echo $selected_event === 'operations' ? 'selected' : ''; ?>>Kullanıcı İşlemleri</option>
                            <option value="all" <?php echo $selected_event === 'all' ? 'selected' : ''; ?>>Tümü (Ziyaretler Dahil)</option>
                            <?php foreach ($event_labels as $event_key => $event_label): ?>
                                <option value="<?php echo htmlspecialchars($event_key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_event === $event_key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">Modül</label>
                        <select name="filter_module" class="form-control selectpicker" data-style="btn-outline-secondary">
                            <option value="">Tüm Modüller</option>
                            <?php foreach ($all_modules as $module_name): ?>
                                <option value="<?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($_GET['filter_module'] ?? '') === $module_name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($module_name), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">Log Seviyesi</label>
                        <select name="filter_level" class="form-control selectpicker" data-style="btn-outline-secondary">
                            <option value="">Tüm Seviyeler</option>
                            <option value="DEBUG" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'DEBUG') ? 'selected' : ''; ?>>DEBUG</option>
                            <option value="INFO" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'INFO') ? 'selected' : ''; ?>>INFO</option>
                            <option value="WARNING" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'WARNING') ? 'selected' : ''; ?>>WARNING</option>
                            <option value="ERROR" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'ERROR') ? 'selected' : ''; ?>>ERROR</option>
                            <option value="CRITICAL" <?php echo (isset($_GET['filter_level']) && $_GET['filter_level'] == 'CRITICAL') ? 'selected' : ''; ?>>CRITICAL</option>
                        </select>
                    </div>
                    <!-- Başlangıç Tarihi -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">Başlangıç Tarihi</label>
                        <input type="text" name="filter_start_date" class="form-control date-picker" value="<?php echo htmlspecialchars($_GET['filter_start_date'] ?? ''); ?>" placeholder="gg-aa-yyyy" autocomplete="off">
                    </div>
                    <!-- Bitiş Tarihi -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="font-13 weight-600 text-muted">Bitiş Tarihi</label>
                        <input type="text" name="filter_end_date" class="form-control date-picker" value="<?php echo htmlspecialchars($_GET['filter_end_date'] ?? ''); ?>" placeholder="gg-aa-yyyy" autocomplete="off">
                    </div>
                    <!-- Arama -->
                    <div class="col-lg-4 col-md-8 col-sm-12 mb-3">
                        <label class="font-13 weight-600 text-muted">Arama Terimi</label>
                        <div class="input-group">
                            <input type="text" name="filter_search" class="form-control" placeholder="İşlem, detay, IP..." value="<?php echo htmlspecialchars($_GET['filter_search'] ?? ''); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <a href="index.php?p=logs/index" class="btn btn-link text-muted btn-sm"><i class="fa fa-undo mr-1"></i>Filtreleri Temizle</a>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="pd-20 bg-white border-radius-8 box-shadow mb-30 animate-fade-in">
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-bordered log-table" style="width:100%">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 14%">Tarih / Saat</th>
                            <th scope="col" style="width: 13%">Kullanıcı</th>
                            <th scope="col" style="width: 12%">İşlem Türü</th>
                            <th scope="col" style="width: 10%">Modül</th>
                            <th scope="col" style="width: 28%">Yapılan İşlem</th>
                            <th scope="col" style="width: 13%">İlgili Kayıt</th>
                            <th scope="col" style="width: 7%">IP</th>
                            <th scope="col" style="width: 3%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted pd-20">Kayıt bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $row): 
                                // Tarih biçimlendirme
                                $log_date = '';
                                if (!empty($row['created_at'])) {
                                    $log_date = date('d.m.Y H:i:s', strtotime($row['created_at']));
                                } else {
                                    $log_date = $row['dates'] . ' ' . $row['clock'];
                                }

                                // Kullanıcı adı bulma
                                $uid = $row['user_id'] ?: $row['author'];
                                $u_name = getUsername($uid);
                                if (empty($u_name)) {
                                    $u_name = ($uid == 0) ? '<span class="text-muted italic">System</span>' : 'User #' . $uid;
                                } else {
                                    $u_name = htmlspecialchars($u_name);
                                }

                                // Mesaj ve detayları çözümleme
                                $details_arr = json_decode($row['details'] ?? '', true);
                                $display_message = '';
                                if (!empty($row['summary'])) {
                                    $display_message = $row['summary'];
                                } elseif ($details_arr && isset($details_arr['message'])) {
                                    $display_message = $details_arr['message'];
                                } else {
                                    $display_message = $row['message'] ?: $row['action'];
                                }

                                $lvl = $row['level'] ?: 'INFO';
                                $event_type = $row['event_type'] ?? '';
                                if ($event_type === '' && stripos($display_message, 'Sayfa Ziyareti') !== false) {
                                    $event_type = 'view';
                                }
                                $event_label = $event_labels[$event_type] ?? ($lvl === 'INFO' ? 'Eski Kayıt' : $lvl);
                                $event_badge = $event_badges[$event_type] ?? 'badge-secondary';
                                $module_name = $row['module'] ?: ($details_arr['channel'] ?? '-');
                                $entity_label = '-';
                                if (!empty($row['entity_type']) || !empty($row['entity_id'])) {
                                    $entity_type = $row['entity_type'] ?? '';
                                    $entity_name = $entity_labels[$entity_type] ?? $entity_type;
                                    $entity_label = trim($entity_name . ' #' . ($row['entity_id'] ?? ''), ' #');
                                }

                                $is_new_format = !empty($row['details']);
                            ?>
                                <tr>
                                    <td><span class="weight-500 font-13"><?php echo $log_date; ?></span></td>
                                    <td><span class="badge badge-outline-dark font-12"><i class="fa fa-user mr-1 text-muted"></i><?php echo $u_name; ?></span></td>
                                    <td><span class="badge <?php echo $event_badge; ?> font-11"><?php echo htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><span class="font-12 text-capitalize"><?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td>
                                        <span class="log-message font-13" data-toggle="tooltip" title="<?php echo htmlspecialchars($display_message); ?>">
                                            <?php echo shorted(htmlspecialchars($display_message), 85); ?>
                                        </span>
                                    </td>
                                    <td><span class="font-12 text-muted"><?php echo htmlspecialchars($entity_label, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><span class="text-dark font-13 font-mono"><?php echo htmlspecialchars($row['ip_address'] ?: '0.0.0.0'); ?></span></td>
                                    <td class="text-center">
                                        <?php if ($is_new_format): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-detail-toggle" data-json='<?php echo htmlspecialchars($row['details'], ENT_QUOTES, 'UTF-8'); ?>' data-ip="<?php echo htmlspecialchars($row['ip_address'], ENT_QUOTES, 'UTF-8'); ?>" data-url="<?php echo htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8'); ?>" data-method="<?php echo htmlspecialchars($row['method'], ENT_QUOTES, 'UTF-8'); ?>" data-level="<?php echo htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8'); ?>" data-event="<?php echo htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8'); ?>" data-module="<?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?>" data-entity="<?php echo htmlspecialchars($entity_label, ENT_QUOTES, 'UTF-8'); ?>" data-summary="<?php echo htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8'); ?>" data-date="<?php echo htmlspecialchars($log_date, ENT_QUOTES, 'UTF-8'); ?>" data-user="<?php echo htmlspecialchars(strip_tags($u_name), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-detail-toggle" data-json='<?php echo htmlspecialchars(json_encode(["message" => $row['message']]), ENT_QUOTES, 'UTF-8'); ?>' data-ip="0.0.0.0" data-url="-" data-method="-" data-level="INFO" data-date="<?php echo $log_date; ?>" data-user="<?php echo strip_tags($u_name); ?>">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="row mt-4">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info font-13 text-muted" role="status" aria-live="polite">
                            Toplam <strong><?php echo $total_rows; ?></strong> kayıttan <strong><?php echo ($offset + 1) . '-' . min($total_rows, $offset + $limit); ?></strong> arası gösteriliyor.
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <nav aria-label="Page navigation" class="pull-right">
                            <ul class="pagination pagination-sm mb-0 d-flex flex-row flex-wrap" style="display: flex !important; flex-flow: row wrap !important; padding-left: 0; list-style: none;">
                                <?php 
                                    $query_params = $_GET;
                                    
                                    // Önceki Sayfa
                                    $prev_page = max(1, $page - 1);
                                    $query_params['page'] = $prev_page;
                                    $prev_link = "index.php?" . http_build_query($query_params);
                                    $disabled = ($page == 1) ? 'disabled' : '';
                                    echo '<li class="page-item ' . $disabled . '"><a class="page-item page-link" href="' . $prev_link . '">Önceki</a></li>';

                                    // Sayfa Numaraları
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++) {
                                        $query_params['page'] = $i;
                                        $link = "index.php?" . http_build_query($query_params);
                                        $active = ($page == $i) ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-item page-link" href="' . $link . '">' . $i . '</a></li>';
                                    }

                                    // Sonraki Sayfa
                                    $next_page = min($total_pages, $page + 1);
                                    $query_params['page'] = $next_page;
                                    $next_link = "index.php?" . http_build_query($query_params);
                                    $disabled = ($page == $total_pages) ? 'disabled' : '';
                                    echo '<li class="page-item ' . $disabled . '"><a class="page-item page-link" href="' . $next_link . '">Sonraki</a></li>';
                                ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1" role="dialog" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-radius-8">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-blue weight-600" id="logDetailModalLabel"><i class="fa fa-history mr-2"></i>Aktivite Detayları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <span class="d-block font-12 text-muted uppercase">Tarih</span>
                        <strong id="modal-date" class="font-14 text-dark">-</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="d-block font-12 text-muted uppercase">Kullanıcı</span>
                        <strong id="modal-user" class="font-14 text-dark">-</strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <span class="d-block font-12 text-muted uppercase">İşlem Türü</span>
                        <strong id="modal-event" class="font-13">-</strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="d-block font-12 text-muted uppercase">Modül</span>
                        <strong id="modal-module" class="font-13">-</strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="d-block font-12 text-muted uppercase">İlgili Kayıt</span>
                        <strong id="modal-entity" class="font-13">-</strong>
                    </div>
                </div>
                <div class="mb-3 p-3 bg-light border-radius-8">
                    <span class="d-block font-12 text-muted uppercase">İşlem Özeti</span>
                    <strong id="modal-summary" class="font-14 text-dark">-</strong>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <span class="d-block font-12 text-muted uppercase">Seviye</span>
                        <span id="modal-level" class="badge badge-success font-11">-</span>
                    </div>
                    <div class="col-sm-4">
                        <span class="d-block font-12 text-muted uppercase">IP / İstek</span>
                        <strong class="font-13 font-mono"><span id="modal-ip">-</span> · <span id="modal-method">-</span></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="d-block font-12 text-muted uppercase">URL</span>
                    <span id="modal-url" class="font-13 font-mono break-all text-secondary">-</span>
                </div>
                <div>
                    <span class="d-block font-12 text-muted uppercase mb-2">İşlem Verileri</span>
                    <div id="modal-changes" class="table-responsive mb-3" style="display:none;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Değişen Alan</th>
                                    <th>Önceki Değer</th>
                                    <th>Yeni Değer</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <pre id="modal-json" class="bg-dark text-light p-3 border-radius-8 font-13 font-mono m-0 overflow-auto" style="max-height: 350px;">-</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Premium Styling and Micro-Animations */
    .logs-header-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5986 50%, #3b7dd8 100%);
        border-radius: 12px;
        padding: 30px;
        position: relative;
        overflow: hidden;
    }
    .logs-header-card .header-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url('src/images/pattern.png') repeat;
        opacity: 0.05;
    }
    .logs-header-card .relative-layout {
        position: relative;
        z-index: 2;
    }
    .logs-header-card .header-icon-box {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        float: left;
        margin-right: 20px;
        backdrop-filter: blur(10px);
    }
    .logs-header-card .header-title-box {
        overflow: hidden;
    }
    .logs-header-card .stats-row {
        border-left: 1px solid rgba(255, 255, 255, 0.15);
        padding-left: 20px;
    }
    @media (max-width: 767px) {
        .logs-header-card .stats-row {
            border-left: none;
            padding-left: 0;
            margin-top: 20px;
        }
    }
    .logs-header-card .stat-item {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 10px;
        padding: 12px 10px;
        backdrop-filter: blur(5px);
        transition: transform 0.3s ease, background 0.3s ease;
    }
    .logs-header-card .stat-item:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.1);
    }
    .text-light-blue {
        color: #b0d4ff !important;
    }
    .text-danger-light {
        color: #ff9f9f !important;
    }
    
    .filter-card {
        border-top: 4px solid #3b7dd8;
    }
    
    .font-mono {
        font-family: 'Courier New', Courier, monospace;
    }
    .break-all {
        word-break: break-all;
    }
    .log-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
    }
    .log-table td {
        vertical-align: middle !important;
    }
    .log-table tr:hover td {
        background-color: #f8fafc;
    }
    .log-message {
        display: block;
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .log-url {
        display: block;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .btn-detail-toggle {
        padding: 4px 8px !important;
        border-radius: 6px !important;
    }
    .badge-purple {
        color: #fff;
        background-color: #7c3aed;
    }

    .dark-mode .filter-card,
    .dark-mode .log-table,
    .dark-mode #logDetailModal .modal-content {
        background: #1f2937 !important;
        color: #e5e7eb !important;
    }
    .dark-mode .log-table th,
    .dark-mode .log-table tr:hover td,
    .dark-mode #logDetailModal .modal-header,
    .dark-mode #logDetailModal .bg-light {
        background: #111827 !important;
        color: #e5e7eb !important;
        border-color: #374151 !important;
    }
    .dark-mode .log-table td,
    .dark-mode #modal-changes td,
    .dark-mode #modal-changes th {
        border-color: #374151 !important;
    }
    
    .pagination .page-link {
        border-radius: 6px !important;
        margin: 0 2px;
        color: #475569;
    }
    .pagination .page-item.active .page-link {
        background-color: #3b7dd8;
        border-color: #3b7dd8;
        color: #fff;
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
$(document).ready(function() {
    // Detay Modalını Aç
    $('.btn-detail-toggle').on('click', function() {
        var btn = $(this);
        var jsonVal = btn.attr('data-json');
        var ip = btn.data('ip') || '-';
        var url = btn.data('url') || '-';
        var method = btn.data('method') || '-';
        var level = btn.data('level') || 'INFO';
        var date = btn.data('date') || '-';
        var user = btn.data('user') || '-';
        var eventName = btn.data('event') || 'Eski Kayıt';
        var moduleName = btn.data('module') || '-';
        var entityName = btn.data('entity') || '-';
        var summary = btn.data('summary') || '-';
        
        // JSON parse & format
        var formattedJson = "-";
        var detailData = {};
        try {
            var parsed = JSON.parse(jsonVal);
            detailData = parsed.context && parsed.context.data
                ? parsed.context.data
                : (parsed.context || parsed);
            formattedJson = Object.keys(detailData).length
                ? JSON.stringify(detailData, null, 4)
                : 'Bu işlem için ek veri bulunmuyor.';
        } catch(e) {
            formattedJson = jsonVal;
        }

        // Modal elemanlarını doldur
        $('#modal-date').text(date);
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
        var changedFields = detailData.changed_fields || {};
        Object.keys(changedFields).forEach(function(field) {
            var change = changedFields[field] || {};
            var row = $('<tr>');
            $('<td>').addClass('weight-600').text(field).appendTo(row);
            $('<td>').text(change.old === null || change.old === '' ? '-' : change.old).appendTo(row);
            $('<td>').text(change.new === null || change.new === '' ? '-' : change.new).appendTo(row);
            changesBody.append(row);
        });
        changesBox.toggle(Object.keys(changedFields).length > 0);
        
        // Seviye badge stili
        var badge = $('#modal-level');
        badge.text(level);
        badge.removeClass('badge-success badge-warning badge-danger badge-info badge-secondary');
        
        if (level === 'INFO') badge.addClass('badge-success');
        else if (level === 'WARNING') badge.addClass('badge-warning');
        else if (['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].indexOf(level) !== -1) badge.addClass('badge-danger');
        else if (level === 'DEBUG') badge.addClass('badge-info');
        else badge.addClass('badge-secondary');
        
        // Modalı göster
        $('#logDetailModal').modal('show');
    });

    // Tooltipleri aktif et
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
