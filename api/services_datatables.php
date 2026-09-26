<?php
// API endpoint for DataTables server-side processing
header('Content-Type: application/json');

// Include bootstrap to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

// Permission check: return JSON instead of redirect
if (!permtrue("serviceView")) {
    http_response_code(403);
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Forbidden'
    ]);
    exit;
}

use App\Helper\DataTableFilter;
use App\Helper\Helper;
use App\Helper\Security;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_accounting_receipt') {
        if (!permtrue("muhasebe_teslim_alma_yetkisi")) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok.'
            ]);
            exit;
        }

        $serviceId = intval($_POST['service_id'] ?? 0);
        if ($serviceId <= 0) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz servis ID.'
            ]);
            exit;
        }

        $serviceCheck = $ac->prepare("SELECT id FROM projects WHERE id = ? LIMIT 1");
        $serviceCheck->execute([$serviceId]);
        if (!$serviceCheck->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Servis kaydı bulunamadı.'
            ]);
            exit;
        }

        $lastActionQuery = $ac->prepare("SELECT action FROM service_accounting_receipt_logs WHERE service_id = ? ORDER BY id DESC LIMIT 1");
        $lastActionQuery->execute([$serviceId]);
        $lastAction = $lastActionQuery->fetch(PDO::FETCH_ASSOC);

        $isCurrentlyReceived = ($lastAction['action'] ?? '') === 'received';
        $newAction = $isCurrentlyReceived ? 'removed' : 'received';
        $actionBy = intval(sesset('id'));

        $insertLog = $ac->prepare("INSERT INTO service_accounting_receipt_logs (service_id, action, action_by, action_at) VALUES (?, ?, ?, NOW())");
        $insertLog->execute([$serviceId, $newAction, $actionBy]);
        audit_log(
            "status_change",
            "services",
            $newAction === 'received'
                ? "Servis muhasebe tarafından teslim alındı"
                : "Servisin muhasebe teslim kaydı kaldırıldı",
            "service",
            $serviceId,
            ['accounting_status' => $newAction]
        );

        echo json_encode([
            'success' => true,
            'message' => $newAction === 'received' ? 'Muhasebe teslim alındı olarak işaretlendi.' : 'Muhasebe teslim kaydı kaldırıldı.',
            'status' => $newAction,
            'action_by' => getUsername($actionBy)
        ]);
        exit;
    }

    if ($action === 'get_accounting_receipt_logs') {
        if (!permtrue("muhasebe_teslim_alma_yetkisi")) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz yok.'
            ]);
            exit;
        }

        $serviceId = intval($_POST['service_id'] ?? 0);
        if ($serviceId <= 0) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz servis ID.'
            ]);
            exit;
        }

        $logQuery = $ac->prepare("SELECT l.action, l.action_at, u.username as action_by_name
            FROM service_accounting_receipt_logs l
            LEFT JOIN users u ON u.id = l.action_by
            WHERE l.service_id = ?
            ORDER BY l.id DESC");
        $logQuery->execute([$serviceId]);
        $logs = $logQuery->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz aksiyon.'
    ]);
    exit;
}

// Check if the request is from DataTables
if (!isset($_GET['draw'])) {
    die(json_encode(['error' => 'Invalid request']));
}

// Get DataTables parameters
$draw = intval($_GET['draw']);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$search_value = $_GET['search']['value'] ?? '';
$order_column = intval($_GET['order'][0]['column'] ?? 0);
$order_dir = $_GET['order'][0]['dir'] ?? 'desc';
// Column-specific search values
$requested_columns = $_GET['columns'] ?? [];

// Column names for ordering (DataTables indexes)
$columns = ['p.id', 'p.service_number', 'c.company', 'r.title', 's.title', 'p.pregdate', 'p.pstart_date', 'p.contract_statu', 'p.pstatu', 'u.username', 'uu.username', 'ar.action_at'];
$order_by = $columns[$order_column] ?? 'p.id';

// Column names for filtering (align with table columns displayed)
// 0: row number (ignored), 1: service_number, 2: company_name, 3: region_name, 4: service_title,
// 5: pregdate, 6: pstart_date, 7: contract_status (special-case via SOZLESMEDURUMU),
// 8: status_title, 9: creator_username, 10: updater_username, 11: accounting_status, 12: actions (ignored)
$filter_columns = [
    1 => 'p.service_number',
    2 => 'c.company',
    3 => 'r.title',
    4 => 's.title',
    5 => 'p.pregdate',
    6 => 'p.pstart_date',
    8 => 'st.title',
    9 => 'u.username',
    10 => 'uu.username',
];

// Base query with JOINs (optimized)
$base_query = "
    FROM projects p
    LEFT JOIN customers c ON c.id = p.pcid
    LEFT JOIN units r ON r.id = p.region
    LEFT JOIN units s ON s.id = p.servicestype
    LEFT JOIN users u ON u.id = p.pcreativer
    LEFT JOIN users uu ON uu.id = p.updater
    LEFT JOIN units st ON st.id = p.pstatu
    LEFT JOIN (
        SELECT l.service_id, l.action, l.action_by, l.action_at
        FROM service_accounting_receipt_logs l
        INNER JOIN (
            SELECT service_id, MAX(id) as max_id
            FROM service_accounting_receipt_logs
            GROUP BY service_id
        ) lm ON lm.max_id = l.id
    ) ar ON ar.service_id = p.id
    LEFT JOIN users au ON au.id = ar.action_by
";

// Toplam kayıt için JOIN çalıştırmaya gerek yok.
$count_query = "SELECT COUNT(*) as total FROM projects";
$count_stmt = $ac->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count filtered records
$where_conditions = [];
$params = [];
// Global search
if ($search_value !== '') {
    $where_conditions[] = "(
        p.service_number LIKE :search OR
        c.company LIKE :search OR
        r.title LIKE :search OR
        s.title LIKE :search OR
        u.username LIKE :search OR
        uu.username LIKE :search OR
        (CASE WHEN ar.action = 'received' THEN 'Teslim Alındı' ELSE 'Teslim Bekliyor' END) LIKE :search OR
        p.pregdate LIKE :search
    )";
    $params[':search'] = "%{$search_value}%";
}
// Column configs for DataTableFilter
$column_configs = [
    1 => ['expr' => 'p.service_number', 'type' => 'text'],
    2 => ['expr' => 'c.company', 'type' => 'text'],
    3 => ['expr' => 'r.title', 'type' => 'text'],
    4 => ['expr' => 's.title', 'type' => 'text'],
    5 => ['expr' => 'p.pregdate', 'type' => 'datetime'],
    6 => ['expr' => 'p.pstart_date', 'type' => 'text'],
    7 => [
        'expr' => 'p.contract_statu',
        'type' => 'select',
        'builder' => function($filter, &$params, $prefix) {
            $values = $filter['values'] ?? [];
            if (!empty($filter['rules'])) {
                foreach ($filter['rules'] as $r) {
                    if (!empty($r['value'])) $values[] = $r['value'];
                }
            }
            if (empty($values)) return '';

            $matchedIds = [];
            foreach ($values as $v) {
                $vLower = mb_strtolower(trim($v), 'UTF-8');
                if (strpos($vLower, 'bekli') !== false) $matchedIds[] = 1;
                if (strpos($vLower, 'yapıldı') !== false || strpos($vLower, 'sözleşmeli') !== false) $matchedIds[] = 2;
                if (strpos($vLower, 'yapılma') !== false) $matchedIds[] = 3;
                if (strpos($vLower, 'kapsamında değil') !== false || strpos($vLower, 'değildir') !== false) $matchedIds[] = 4;
            }
            $matchedIds = array_unique($matchedIds);
            if (count($matchedIds)) {
                $inKeys = [];
                foreach ($matchedIds as $i => $mid) {
                    $pKey = ":{$prefix}cs_{$i}";
                    $params[$pKey] = $mid;
                    $inKeys[] = $pKey;
                }
                return "p.contract_statu IN (" . implode(', ', $inKeys) . ")";
            }
            return "p.contract_statu = -1";
        }
    ],
    8 => ['expr' => 'st.title', 'type' => 'text'],
    9 => ['expr' => 'u.username', 'type' => 'text'],
    10 => ['expr' => 'COALESCE(uu.username, u.username)', 'type' => 'text'],
    11 => [
        'expr' => 'ar.action',
        'type' => 'select',
        'builder' => function($filter, &$params, $prefix) {
            $values = $filter['values'] ?? [];
            if (!empty($filter['rules'])) {
                foreach ($filter['rules'] as $r) {
                    if (!empty($r['value'])) $values[] = $r['value'];
                }
            }
            if (empty($values)) return '';

            $hasReceived = false;
            $hasPending = false;
            foreach ($values as $v) {
                $vLower = mb_strtolower(trim($v), 'UTF-8');
                if (strpos($vLower, 'alındı') !== false) $hasReceived = true;
                if (strpos($vLower, 'bekliyor') !== false || strpos($vLower, 'bekle') !== false) $hasPending = true;
            }

            if ($hasReceived && $hasPending) {
                return '';
            }
            if ($hasReceived) {
                return "ar.action = 'received'";
            }
            if ($hasPending) {
                return "(ar.action != 'received' OR ar.action IS NULL)";
            }
            return '';
        }
    ]
];

// Column-specific search
if (!empty($requested_columns) && is_array($requested_columns)) {
    foreach ($requested_columns as $idx => $col) {
        $rawSearch = $col['search']['value'] ?? '';
        $idx = intval($idx);
        $filter = DataTableFilter::parse($rawSearch);
        if (!$filter) {
            continue;
        }

        if (isset($column_configs[$idx])) {
            $cfg = $column_configs[$idx];
            $prefix = "col_{$idx}_";
            if (isset($cfg['builder']) && is_callable($cfg['builder'])) {
                $cond = $cfg['builder']($filter, $params, $prefix);
            } else {
                $cond = DataTableFilter::buildCondition($cfg['expr'], $filter, $params, $prefix, $cfg['type'] ?? 'text');
            }
            if (!empty($cond)) {
                $where_conditions[] = $cond;
            }
        }
    }
}

$where_clause = count($where_conditions) ? (" WHERE " . implode(" AND ", $where_conditions)) : "";

if ($where_clause === '') {
    $filtered_records = $total_records;
} else {
    $filtered_count_query = "SELECT COUNT(*) as filtered " . $base_query . $where_clause;
    $filtered_stmt = $ac->prepare($filtered_count_query);
    foreach ($params as $k => $v) {
        $filtered_stmt->bindValue($k, $v);
    }
    $filtered_stmt->execute();
    $filtered_records = $filtered_stmt->fetch(PDO::FETCH_ASSOC)['filtered'];
}

// Main data query
$data_query = "
    SELECT 
        p.id,
        p.service_number,
        c.company as company_name,
        c.deleted_at as customer_deleted_at,
        r.title as region_name,
        s.title as service_title,
        p.pregdate,
        p.pstart_date,
        p.contract_statu,
        p.pstatu,
        st.title as status_title,
        st.colour as status_color,
        u.username as creator_username,
        uu.username as updater_username,
        ar.action as accounting_action,
        ar.action_at as accounting_action_at,
        au.username as accounting_actor_username
" . $base_query . $where_clause . "
    ORDER BY {$order_by} {$order_dir}
    LIMIT :start, :length
";

$data_stmt = $ac->prepare($data_query);
foreach ($params as $k => $v) {
    $data_stmt->bindValue($k, $v);
}
$data_stmt->bindParam(':start', $start, PDO::PARAM_INT);
$data_stmt->bindParam(':length', $length, PDO::PARAM_INT);
$data_stmt->execute();

$projects = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response
$response = [
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => []
];

// Yetki kontrollerini döngü dışında yap
$canEdit = permtrue("serviceEdit");
$canDel = permtrue("serviceDel");
$canAccountingReceipt = permtrue("muhasebe_teslim_alma_yetkisi");

// Ensure database connection is available
if (!isset($ac)) {
    die(json_encode(['error' => 'Database connection not available']));
}

// Format data for DataTables
foreach ($projects as $project) {
    $row = [];
    $pid = $project['id'];

    // 0: Row number
    $row[] = '';

    // 1: Servis Numarası
    $servisNo = htmlspecialchars($project['service_number'] ?? '');
    $row[] = '<span class="badge-sku"><i class="fa fa-wrench mr-1 text-primary"></i>' . $servisNo . '</span>';

    // 2: Firma Adı
    $fullCompanyName = htmlspecialchars($project['company_name'] ?? '');
    if (!empty($project['customer_deleted_at'])) {
        $row[] = '<div class="service-company-cell" data-toggle="tooltip" title="' . $fullCompanyName . '"><span class="text-muted">' . $fullCompanyName . '</span> <small class="crm-badge-soft soft-amber font-11">Silinmiş</small></div>';
    } else {
        $row[] = '<div class="service-company-cell" data-toggle="tooltip" title="' . $fullCompanyName . '"><span class="font-weight-600 text-dark">' . $fullCompanyName . '</span></div>';
    }

    // 3: Bölge
    $regionName = htmlspecialchars($project['region_name'] ?? '');
    if ($regionName !== '') {
        $row[] = '<div class="font-12 text-dark" style="line-height:1.25;">' . $regionName . '</div>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 4: Servis Konusu
    $serviceTitle = htmlspecialchars($project['service_title'] ?? '');
    $row[] = '<div class="service-title-cell" style="line-height:1.25;" data-toggle="tooltip" title="' . $serviceTitle . '">' . $serviceTitle . '</div>';

    // 5: İş Emri Tarihi
    $regDateRaw = $project['pregdate'] ?? '';
    if ($regDateRaw) {
        $timestamp = strtotime($regDateRaw);
        $dateFormatted = $timestamp ? date('d.m.Y', $timestamp) : $regDateRaw;
        $timeFormatted = $timestamp ? date('H:i', $timestamp) : '';
        $row[] = '<div class="text-center font-12" style="line-height:1.25;"><span class="text-dark">' . $dateFormatted . '</span>' . ($timeFormatted ? '<br><span class="text-muted font-11">' . $timeFormatted . '</span>' : '') . '</div>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 6: Planlama Tarihi
    $startDateRaw = $project['pstart_date'] ?? '';
    if ($startDateRaw && $startDateRaw !== '-') {
        $timestampStart = strtotime($startDateRaw);
        $startDateFormatted = $timestampStart ? date('d.m.Y', $timestampStart) : $startDateRaw;
        $row[] = '<div class="text-center font-12 text-muted"><i class="fa fa-clock-o mr-1"></i>' . $startDateFormatted . '</div>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 7: Sözleşme Durumu
    $contractStatu = (int)($project['contract_statu'] ?? 0);
    if ($contractStatu === 4) {
        $row[] = '<span class="crm-badge-soft soft-rose font-11" style="padding:2px 6px; display:inline-block; line-height:1.2;">S.Kapsamında Değildir</span>';
    } elseif ($contractStatu === 2) {
        $row[] = '<span class="crm-badge-soft soft-emerald font-11" style="padding:2px 6px; display:inline-block;">Sözleşmeli</span>';
    } elseif ($contractStatu === 1) {
        $row[] = '<span class="crm-badge-soft soft-amber font-11" style="padding:2px 6px; display:inline-block;">Bekliyor</span>';
    } elseif ($contractStatu === 3) {
        $row[] = '<span class="crm-badge-soft soft-rose font-11" style="padding:2px 6px; display:inline-block;">Yapılmadı</span>';
    } else {
        $row[] = getSozlesmeStatusBadge($project['contract_statu']);
    }

    // 8: Servis Durumu (Kompakt Rozet)
    $status_color = (!empty($project['status_color'])) ? $project['status_color'] : '#64748b';
    $status_title = htmlspecialchars($project['status_title'] ?? '-');
    $row[] = "<span class='badge badge-status' style='background-color:{$status_color}; color:#fff; font-weight:600; padding:3px 6px; font-size:10.5px; border-radius:4px; display:inline-block; white-space:normal; line-height:1.2; word-break:break-word; max-width:100%;'>{$status_title}</span>";

    // 9: İş Emrini Oluşturan
    $creator = htmlspecialchars($project['creator_username'] ?? '-');
    $row[] = '<div class="font-12 text-dark" style="line-height:1.25;" data-toggle="tooltip" title="' . $creator . '">' . $creator . '</div>';

    // 10: Son İşlem Yapan
    $updater = htmlspecialchars($project['updater_username'] ?: ($project['creator_username'] ?? '-'));
    $row[] = '<div class="font-12 text-muted" style="line-height:1.25;" data-toggle="tooltip" title="' . $updater . '">' . $updater . '</div>';

    // 11: Muhasebe Teslim
    $isAccountingReceived = ($project['accounting_action'] ?? '') === 'received';
    $accountingLabel = $isAccountingReceived ? 'Teslim Alındı' : 'Teslim Bekliyor';
    $accountingSoftClass = $isAccountingReceived ? 'soft-emerald' : 'soft-amber';
    $accountingIcon = $isAccountingReceived ? 'fa-check' : 'fa-clock-o';
    $accountingInfo = "<span class='crm-badge-soft {$accountingSoftClass}' style='padding:2px 7px; font-size:11px; display:inline-block; line-height:1.2;'><i class='fa {$accountingIcon} mr-1'></i>{$accountingLabel}</span>";
    $row[] = $accountingInfo;

    // 12: İşlem Butonları (Açılır Liste / Dropdown Menu)
    $actions = '<div class="dropdown d-inline-block text-center">';
    $actions .= '<button class="btn btn-sm btn-outline-secondary dropdown-toggle action-dropdown-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding: 3px 8px; font-size: 11.5px; border-radius: 5px; font-weight: 500;">';
    $actions .= '<i class="fa fa-cog mr-1 text-muted"></i>İşlem';
    $actions .= '</button>';
    $actions .= '<div class="dropdown-menu dropdown-menu-right shadow border-0 dropdown-menu-detail" style="border-radius: 8px; font-size: 12px; z-index: 1050; min-width: 175px;">';

    if ($canEdit) {
        $actions .= '<a href="index.php?p=service/manage&id=' . $pid . '" class="dropdown-item"><i class="fa fa-pencil text-primary mr-2"></i> Düzenle</a>';
    }
    
    $actions .= '<a href="index.php?p=service-view&id=' . Security::encrypt($pid) . '" target="_blank" class="dropdown-item"><i class="fa fa-info-circle text-info mr-2"></i> Detay Görüntüle</a>';

    if ($canAccountingReceipt) {
        if ($isAccountingReceived) {
            $confirmText = 'Bu servis için muhasebe teslim kaydını iade almak istediğinize emin misiniz?';
            $actions .= '<button type="button" class="dropdown-item js-accounting-receipt-toggle text-warning" data-service-id="' . (int) $pid . '" data-confirm="' . htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-undo text-warning mr-2"></i> Muhasebe İade Al</button>';
        } else {
            $confirmText = 'Bu servisi muhasebe teslim alındı olarak işaretlemek istediğinize emin misiniz?';
            $actions .= '<button type="button" class="dropdown-item js-accounting-receipt-toggle text-success" data-service-id="' . (int) $pid . '" data-confirm="' . htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-check text-success mr-2"></i> Muhasebe Teslim Al</button>';
        }
        $actions .= '<button type="button" class="dropdown-item js-accounting-log" data-service-id="' . (int) $pid . '" data-service-number="' . htmlspecialchars($project['service_number'], ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-history text-dark mr-2"></i> Muhasebe Logları</button>';
    }

    if ($canDel) {
        $actions .= '<div class="dropdown-divider"></div>';
        $actions .= '<button type="button" class="dropdown-item text-danger" onClick="deleteRecord(\'' . $pid . ' nolu Servisi silmek istediğinize emin misiniz?\',\'' . $pid . '\',\'services\',\'projects\',\'service/list\')"><i class="fa fa-trash text-danger mr-2"></i> Sil</button>';
    }

    $actions .= '</div></div>';

    $row[] = $actions;

    $response['data'][] = $row;
}

// Sütun filtre seçenek sayıları (TableFilter için veritabanı toplamları)
$columnCounts = [];
try {
    // 3: Bölge
    $stBolge = $ac->query("SELECT r.title, COUNT(*) as cnt FROM projects p JOIN units r ON r.id = p.region AND r.title != '' GROUP BY r.id, r.title ORDER BY cnt DESC");
    if ($stBolge) { $columnCounts[3] = $stBolge->fetchAll(PDO::FETCH_KEY_PAIR); }

    // 4: Hizmet Türü
    $stHizmet = $ac->query("SELECT s.title, COUNT(*) as cnt FROM projects p JOIN units s ON s.id = p.servicestype AND s.title != '' GROUP BY s.id, s.title ORDER BY cnt DESC");
    if ($stHizmet) { $columnCounts[4] = $stHizmet->fetchAll(PDO::FETCH_KEY_PAIR); }

    // 7: Sözleşme Durumu
    $stSozlesme = $ac->query("SELECT 
        CASE 
            WHEN p.contract_statu = 2 THEN 'Sözleşmeli'
            WHEN p.contract_statu = 1 THEN 'Bekliyor'
            WHEN p.contract_statu = 3 THEN 'Yapılmadı'
            WHEN p.contract_statu = 4 THEN 'S.Kapsamında Değildir'
            ELSE 'Belirtilmedi'
        END as lbl, COUNT(*) as cnt FROM projects p GROUP BY p.contract_statu ORDER BY cnt DESC");
    if ($stSozlesme) { $columnCounts[7] = $stSozlesme->fetchAll(PDO::FETCH_KEY_PAIR); }

    // 8: Servis Durumu
    $stDurum = $ac->query("SELECT st.title, COUNT(*) as cnt FROM projects p JOIN units st ON st.id = p.pstatu AND st.title != '' GROUP BY st.id, st.title ORDER BY cnt DESC");
    if ($stDurum) { $columnCounts[8] = $stDurum->fetchAll(PDO::FETCH_KEY_PAIR); }

    // 9: İş Emrini Oluşturan
    $stCreator = $ac->query("SELECT u.username, COUNT(*) as cnt FROM projects p JOIN users u ON u.id = p.pcreativer AND u.username != '' GROUP BY u.id, u.username ORDER BY cnt DESC");
    if ($stCreator) { $columnCounts[9] = $stCreator->fetchAll(PDO::FETCH_KEY_PAIR); }

    // 11: Muhasebe Durumu
    $stMuhasebe = $ac->query("SELECT 
        CASE WHEN ar.action = 'received' THEN 'Teslim Alındı' ELSE 'Teslim Bekliyor' END as lbl, 
        COUNT(*) as cnt 
        FROM projects p 
        LEFT JOIN (
            SELECT l.service_id, l.action 
            FROM service_accounting_receipt_logs l
            INNER JOIN (
                SELECT service_id, MAX(id) as max_id FROM service_accounting_receipt_logs GROUP BY service_id
            ) lm ON lm.max_id = l.id
        ) ar ON ar.service_id = p.id
        GROUP BY lbl ORDER BY cnt DESC");
    if ($stMuhasebe) { $columnCounts[11] = $stMuhasebe->fetchAll(PDO::FETCH_KEY_PAIR); }
} catch (Exception $e) {}

$response['columnCounts'] = $columnCounts;

// Ensure no stray output breaks JSON
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
echo json_encode($response);
exit;
