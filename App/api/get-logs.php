<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json');

if (!in_array(sesset("id"), [1, 12])) {
    http_response_code(403);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Yetkisiz erişim'
    ]);
    exit;
}

global $ac;

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
        return date('d.m.Y', $time);
    }
}

// DataTables Parameters
$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 50);
if ($length <= 0) $length = 50;

$search_value = trim($_POST['search']['value'] ?? '');
$order_col_idx = intval($_POST['order'][0]['column'] ?? 0);
$order_dir = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$col_map = [
    0 => 'l.id',
    1 => 'u.username',
    2 => 'l.event_type',
    3 => 'l.module',
    4 => 'l.summary',
    5 => 'l.entity_id'
];
$order_column = $col_map[$order_col_idx] ?? 'l.id';

$where = [];
$params = [];

// Global search
if ($search_value !== '') {
    $where[] = "(l.action LIKE :gsearch OR l.details LIKE :gsearch OR l.url LIKE :gsearch OR l.ip_address LIKE :gsearch OR l.message LIKE :gsearch OR l.summary LIKE :gsearch OR l.entity_id LIKE :gsearch OR u.username LIKE :gsearch)";
    $params[':gsearch'] = "%{$search_value}%";
}

// TableFilter column filters check
$columns_post = $_POST['columns'] ?? [];
$has_any_col_filter = false;
$has_col_filter = [];
foreach ($columns_post as $cIdx => $cData) {
    $cVal = trim($cData['search']['value'] ?? '');
    if ($cVal !== '') {
        $has_col_filter[intval($cIdx)] = true;
        $has_any_col_filter = true;
    }
}

// Form filters (only applied if not overridden by column-specific TableFilter)
$filters = $_POST['filters'] ?? [];
if (!empty($filters['filter_user']) && empty($has_col_filter[1])) {
    $where[] = "(l.user_id = :f_user_1 OR l.author = :f_user_2)";
    $params[':f_user_1'] = intval($filters['filter_user']);
    $params[':f_user_2'] = intval($filters['filter_user']);
}
if (!empty($filters['filter_level'])) {
    $where[] = "l.level = :f_level";
    $params[':f_level'] = $filters['filter_level'];
}
if (!empty($filters['filter_event']) && empty($has_col_filter[2]) && empty($has_col_filter[1])) {
    $fe = $filters['filter_event'];
    if ($fe === 'operations') {
        $where[] = "((l.event_type IS NOT NULL AND l.event_type <> 'view') OR (l.event_type IS NULL AND COALESCE(l.summary, l.message, l.details, l.action, '') NOT LIKE '%Sayfa Ziyareti:%'))";
    } elseif ($fe === 'view') {
        $where[] = "(l.event_type = 'view' OR (l.event_type IS NULL AND COALESCE(l.summary, l.message, l.details, l.action, '') LIKE '%Sayfa Ziyareti:%'))";
    } elseif ($fe !== 'all' && isset($event_labels[$fe])) {
        $where[] = "l.event_type = :f_event";
        $params[':f_event'] = $fe;
    }
}
if (!empty($filters['filter_module']) && empty($has_col_filter[3]) && empty($has_col_filter[1])) {
    $where[] = "l.module = :f_module";
    $params[':f_module'] = $filters['filter_module'];
}
if (!empty($filters['filter_search'])) {
    $where[] = "(l.action LIKE :f_srch OR l.details LIKE :f_srch OR l.url LIKE :f_srch OR l.ip_address LIKE :f_srch OR l.message LIKE :f_srch OR l.summary LIKE :f_srch OR l.entity_id LIKE :f_srch)";
    $params[':f_srch'] = '%' . $filters['filter_search'] . '%';
}
if (!empty($filters['filter_start_date']) && empty($has_col_filter[0]) && empty($has_col_filter[1])) {
    $startDate = $filters['filter_start_date'];
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $startDate)) {
        $parts = explode('-', $startDate);
        $startDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    $where[] = "COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y')) >= :f_start_date";
    $params[':f_start_date'] = $startDate . ' 00:00:00';
}
if (!empty($filters['filter_end_date']) && empty($has_col_filter[0]) && empty($has_col_filter[1])) {
    $endDate = $filters['filter_end_date'];
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $endDate)) {
        $parts = explode('-', $endDate);
        $endDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    $where[] = "COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y')) <= :f_end_date";
    $params[':f_end_date'] = $endDate . ' 23:59:59';
}

// Process TableFilter column filters
foreach ($columns_post as $idx => $cData) {
    $idx = intval($idx);
    $rawVal = trim($cData['search']['value'] ?? '');
    if ($rawVal === '') continue;

    $json = json_decode($rawVal, true);
    if (is_array($json)) {
        if (isset($json['colIndex'])) {
            $idx = intval($json['colIndex']);
        }
        // Multi-select values
        if (!empty($json['values']) && is_array($json['values'])) {
            $valClauses = [];
            foreach ($json['values'] as $vIdx => $v) {
                $pKey = ":tf_col_{$idx}_{$vIdx}";
                if ($idx === 1) { // Kullanıcı
                    if ($v === 'Sistem') {
                        $valClauses[] = "(COALESCE(NULLIF(l.user_id, 0), l.author) = 0 OR u.username IS NULL OR u.username = '' OR u.username = 'Sistem')";
                    } else {
                        // Resolve all matching user IDs from DB
                        static $cachedUsers = null;
                        if ($cachedUsers === null) {
                            $stU = $ac->query("SELECT id, TRIM(username) as username FROM users WHERE username IS NOT NULL AND username != ''");
                            $cachedUsers = $stU ? $stU->fetchAll(PDO::FETCH_KEY_PAIR) : [];
                        }
                        $matchedUserIds = [];
                        $cleanV = trim($v);
                        if (preg_match('/^Kullanıcı #(\d+)$/i', $cleanV, $mId)) {
                            $matchedUserIds[] = (int)$mId[1];
                        }
                        foreach ($cachedUsers as $uId => $uName) {
                            if (mb_strtolower(trim($uName), 'UTF-8') === mb_strtolower($cleanV, 'UTF-8')) {
                                $matchedUserIds[] = (int)$uId;
                            }
                        }
                        $subConds = ["TRIM(u.username) = $pKey", "u.username = $pKey", "CONCAT('Kullanıcı #', l.author) = $pKey", "CONCAT('Kullanıcı #', l.user_id) = $pKey"];
                        if (!empty($matchedUserIds)) {
                            $uIdList = implode(',', array_unique($matchedUserIds));
                            $subConds[] = "l.user_id IN ($uIdList)";
                            $subConds[] = "l.author IN ($uIdList)";
                        }
                        $valClauses[] = "(" . implode(' OR ', $subConds) . ")";
                        $params[$pKey] = $cleanV;
                    }
                } elseif ($idx === 2) { // İşlem Türü
                    if ($v === 'Eski Kayıt') {
                        $valClauses[] = "(l.event_type IS NULL OR l.event_type = '' OR l.event_type = 'Eski Kayıt')";
                    } elseif ($v === 'Sayfa Ziyareti') {
                        $valClauses[] = "(l.event_type = 'view' OR ((l.event_type IS NULL OR l.event_type = '') AND (l.summary LIKE '%Sayfa Ziyareti%' OR l.action LIKE '%Sayfa Ziyareti%' OR l.message LIKE '%Sayfa Ziyareti%')))";
                    } else {
                        $mappedKey = array_search($v, $event_labels);
                        if ($mappedKey !== false) {
                            $valClauses[] = "l.event_type = $pKey";
                            $params[$pKey] = $mappedKey;
                        } else {
                            $valClauses[] = "l.event_type = $pKey";
                            $params[$pKey] = $v;
                        }
                    }
                } elseif ($idx === 3) { // Modül
                    $valClauses[] = "l.module = $pKey";
                    $params[$pKey] = $v;
                }
            }
            if (!empty($valClauses)) {
                $where[] = "(" . implode(' OR ', $valClauses) . ")";
            }
        } elseif (!empty($json['rules']) && is_array($json['rules'])) {
            $logic = (isset($json['logic']) && strtolower($json['logic']) === 'or') ? ' OR ' : ' AND ';
            $ruleConds = [];
            foreach ($json['rules'] as $rIdx => $r) {
                $op = $r['operator'] ?? 'contains';
                $rVal = trim($r['value'] ?? '');
                $pKey = ":tf_r_{$idx}_{$rIdx}";

                if ($idx === 0) { // Tarih
                    if ($op === 'equals') {
                        $ruleConds[] = "DATE(COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y'))) = $pKey";
                        $params[$pKey] = $rVal;
                    } elseif ($op === 'after' || $op === 'gt') {
                        $ruleConds[] = "DATE(COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y'))) > $pKey";
                        $params[$pKey] = $rVal;
                    } elseif ($op === 'before' || $op === 'lt') {
                        $ruleConds[] = "DATE(COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y'))) < $pKey";
                        $params[$pKey] = $rVal;
                    } elseif ($op === 'gte') {
                        $ruleConds[] = "DATE(COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y'))) >= $pKey";
                        $params[$pKey] = $rVal;
                    } elseif ($op === 'lte') {
                        $ruleConds[] = "DATE(COALESCE(l.created_at, STR_TO_DATE(l.dates, '%d-%m-%Y'))) <= $pKey";
                        $params[$pKey] = $rVal;
                    }
                } elseif ($idx === 4) { // Yapılan İşlem / Detay
                    if ($op === 'contains') {
                        $ruleConds[] = "(l.summary LIKE $pKey OR l.message LIKE $pKey OR l.action LIKE $pKey)";
                        $params[$pKey] = "%{$rVal}%";
                    } elseif ($op === 'not_contains') {
                        $ruleConds[] = "(l.summary NOT LIKE $pKey AND l.message NOT LIKE $pKey AND l.action NOT LIKE $pKey)";
                        $params[$pKey] = "%{$rVal}%";
                    }
                } elseif ($idx === 5) { // İlgili Kayıt
                    if ($op === 'contains') {
                        $ruleConds[] = "(l.entity_type LIKE $pKey OR l.entity_id LIKE $pKey)";
                        $params[$pKey] = "%{$rVal}%";
                    }
                }
            }
            if (!empty($ruleConds)) {
                $where[] = "(" . implode($logic, $ruleConds) . ")";
            }
        }
    } else {
        // Plain-text search fallback
        $pKey = ":tf_plain_{$idx}";
        if ($idx === 1) {
            $where[] = "(u.username LIKE $pKey OR CONCAT('Kullanıcı #', l.author) LIKE $pKey)";
            $params[$pKey] = "%{$rawVal}%";
        } elseif ($idx === 2) {
            $where[] = "(l.event_type LIKE $pKey OR l.summary LIKE $pKey)";
            $params[$pKey] = "%{$rawVal}%";
        } elseif ($idx === 3) {
            $where[] = "l.module LIKE $pKey";
            $params[$pKey] = "%{$rawVal}%";
        } elseif ($idx === 4) {
            $where[] = "(l.summary LIKE $pKey OR l.message LIKE $pKey OR l.action LIKE $pKey)";
            $params[$pKey] = "%{$rawVal}%";
        } elseif ($idx === 5) {
            $where[] = "(l.entity_type LIKE $pKey OR l.entity_id LIKE $pKey)";
            $params[$pKey] = "%{$rawVal}%";
        }
    }
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$base_from = "FROM logs l LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author)";

// Total count
$total_query = $ac->query("SELECT COUNT(*) FROM logs");
$recordsTotal = (int)$total_query->fetchColumn();

// Filtered count
$filtered_stmt = $ac->prepare("SELECT COUNT(*) $base_from $where_sql");
$filtered_stmt->execute($params);
$recordsFiltered = (int)$filtered_stmt->fetchColumn();

// Data query
$data_sql = "SELECT l.*, u.username as u_username $base_from $where_sql ORDER BY $order_column $order_dir LIMIT :lim OFFSET :off";
$data_stmt = $ac->prepare($data_sql);
foreach ($params as $k => $v) {
    $data_stmt->bindValue($k, $v);
}
$data_stmt->bindValue(':lim', $length, PDO::PARAM_INT);
$data_stmt->bindValue(':off', $start, PDO::PARAM_INT);
$data_stmt->execute();
$logs = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($logs as $row) {
    // Tarih biçimlendirme
    $log_date = '';
    $raw_date_for_rel = '';
    if (!empty($row['created_at'])) {
        $log_date = date('d.m.Y H:i:s', strtotime($row['created_at']));
        $raw_date_for_rel = $row['created_at'];
    } else {
        $log_date = $row['dates'] . ' ' . $row['clock'];
        $raw_date_for_rel = $row['dates'] . ' ' . $row['clock'];
    }
    $rel_time = formatRelativeTime($raw_date_for_rel);

    // Kullanıcı adı
    $uid = $row['user_id'] ?: $row['author'];
    $u_name = $row['u_username'];
    $initial = 'S';
    if (empty($u_name)) {
        $u_name = ($uid == 0) ? 'Sistem' : 'Kullanıcı #' . $uid;
        $initial = 'S';
    } else {
        $initial = mb_strtoupper(mb_substr($u_name, 0, 1, 'UTF-8'), 'UTF-8');
        $u_name = htmlspecialchars($u_name, ENT_QUOTES, 'UTF-8');
    }

    // Mesaj
    $details_arr = json_decode($row['details'] ?? '', true);
    $display_message = '';
    if (!empty($row['summary'])) {
        $display_message = $row['summary'];
    } elseif ($details_arr && isset($details_arr['message'])) {
        $display_message = $details_arr['message'];
    } else {
        $display_message = $row['message'] ?: $row['action'];
    }
    $display_message = preg_replace('/^\[[^\]]+\]\s+[^\s]+:\s*/u', '', trim((string)$display_message));
    $display_message = preg_replace('/\s+\{.*\}\s+\[\]\s*$/su', '', (string)$display_message);

    $lvl = $row['level'] ?: 'INFO';
    $event_type = $row['event_type'] ?? '';
    if ($event_type === '' && stripos($display_message, 'Sayfa Ziyareti') !== false) {
        $event_type = 'view';
    }
    $event_label = $event_labels[$event_type] ?? ($lvl === 'INFO' ? 'Eski Kayıt' : $lvl);
    $event_badge = $event_badges[$event_type] ?? 'badge-log-view';
    $event_icon = $event_icons[$event_type] ?? 'fa fa-circle';
    $module_name = $row['module'] ?: ($details_arr['channel'] ?? 'Genel');
    $mod_icon = $module_icons[$module_name] ?? 'fa fa-folder-o';

    $entity_label = '-';
    if (!empty($row['entity_type']) || !empty($row['entity_id'])) {
        $entity_type = $row['entity_type'] ?? '';
        $entity_name = $entity_labels[$entity_type] ?? $entity_type;
        $entity_label = trim($entity_name . ' #' . ($row['entity_id'] ?? ''), ' #');
    }

    $col_date = '<div class="d-flex flex-column"><span class="weight-600 font-12 text-dark">' . $log_date . '</span><span class="font-11 text-muted">' . $rel_time . '</span></div>';
    
    $col_user = '<div class="d-flex align-items-center" style="gap: 8px;"><div class="user-mini-avatar" style="background:#e0e7ff; color:#3730a3; font-weight:700; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px;">' . $initial . '</div><span class="font-12 weight-600 text-dark">' . $u_name . '</span></div>';

    $col_event = '<span class="badge ' . $event_badge . ' font-11"><i class="' . $event_icon . ' mr-1"></i>' . htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8') . '</span>';

    $col_module = '<span class="module-tag font-11"><i class="' . $mod_icon . ' mr-1 text-muted"></i>' . htmlspecialchars(ucfirst($module_name), ENT_QUOTES, 'UTF-8') . '</span>';

    $detail_btn = '<button type="button" class="btn btn-xs btn-outline-primary btn-detail-toggle font-11" onclick="window.openLogDetail && window.openLogDetail(this)" title="Detay görüntüle" '
        . 'data-json="' . htmlspecialchars($row['details'] ?? '', ENT_QUOTES, 'UTF-8') . '" '
        . 'data-ip="' . htmlspecialchars($row['ip_address'] ?: '0.0.0.0', ENT_QUOTES, 'UTF-8') . '" '
        . 'data-url="' . htmlspecialchars($row['url'] ?: '-', ENT_QUOTES, 'UTF-8') . '" '
        . 'data-method="' . htmlspecialchars($row['method'] ?: '-', ENT_QUOTES, 'UTF-8') . '" '
        . 'data-level="' . htmlspecialchars($lvl, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-event="' . htmlspecialchars($event_label, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-module="' . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-entity="' . htmlspecialchars($entity_label, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-summary="' . htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-date="' . htmlspecialchars($log_date, ENT_QUOTES, 'UTF-8') . '" '
        . 'data-user="' . htmlspecialchars(strip_tags($u_name), ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-eye mr-1"></i>Detay</button>';

    $col_action = '<div class="log-action-cell d-flex align-items-center" style="gap: 8px;">' . $detail_btn . '<span class="log-message font-12 text-truncate" style="max-width: 380px;" data-toggle="tooltip" title="' . htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8') . '</span></div>';

    $col_entity = ($entity_label !== '-') ? '<span class="entity-pill font-11">' . htmlspecialchars($entity_label, ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="font-12 text-muted">-</span>';

    $data[] = [
        $col_date,
        $col_user,
        $col_event,
        $col_module,
        $col_action,
        $col_entity
    ];
}

// Full database counts for select filter options
$columnCounts = [];
try {
    // 1: Kullanıcı
    $stUsers = $ac->query("SELECT COALESCE(NULLIF(u.username, ''), IF(COALESCE(NULLIF(l.user_id, 0), l.author) = 0, 'Sistem', CONCAT('Kullanıcı #', COALESCE(NULLIF(l.user_id, 0), l.author)))) as uname, COUNT(*) as cnt FROM logs l LEFT JOIN users u ON u.id = COALESCE(NULLIF(l.user_id, 0), l.author) GROUP BY uname ORDER BY cnt DESC");
    if ($stUsers) {
        $columnCounts[1] = $stUsers->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    // 2: İşlem Türü
    $stEvents = $ac->query("SELECT COALESCE(NULLIF(event_type, ''), 'view') as ev, COUNT(*) as cnt FROM logs GROUP BY ev ORDER BY cnt DESC");
    if ($stEvents) {
        $evRaw = $stEvents->fetchAll(PDO::FETCH_KEY_PAIR);
        $evCounts = [];
        foreach ($evRaw as $ev => $cnt) {
            $lbl = $event_labels[$ev] ?? $ev;
            $evCounts[$lbl] = ($evCounts[$lbl] ?? 0) + $cnt;
        }
        $columnCounts[2] = $evCounts;
    }
    // 3: Modül
    $stModules = $ac->query("SELECT module, COUNT(*) as cnt FROM logs WHERE module IS NOT NULL AND module != '' GROUP BY module ORDER BY cnt DESC");
    if ($stModules) {
        $columnCounts[3] = $stModules->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (Exception $e) {}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data,
    'columnCounts' => $columnCounts
]);
exit;
