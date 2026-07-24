<?php
// API endpoint for DataTables server-side processing
header('Content-Type: application/json');

// Include bootstrap to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

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

use App\Helper\Helper;
use App\Helper\Security;

try {
    $ac->exec("CREATE TABLE IF NOT EXISTS service_accounting_receipt_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id INT UNSIGNED NOT NULL,
        action VARCHAR(20) NOT NULL,
        action_by INT UNSIGNED NOT NULL,
        action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_service_id (service_id),
        KEY idx_action_at (action_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Tablo oluşturulamasa da listeleme akışı devam etsin.
}

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

// Count total records
$count_query = "SELECT COUNT(*) as total " . $base_query;
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
// Column-specific search
if (!empty($requested_columns) && is_array($requested_columns)) {
    foreach ($requested_columns as $idx => $col) {
        $value = $col['search']['value'] ?? '';
        $idx = intval($idx);
        if ($value === '')
            continue;
        if ($idx === 7) {
            if (defined('SOZLESMEDURUMU')) {
                $matchIds = [];
                foreach (SOZLESMEDURUMU as $k => $v) {
                    if ($k === '')
                        continue;
                    if (stripos($v, $value) !== false) {
                        $matchIds[] = $k;
                    }
                }
                if (count($matchIds)) {
                    $inKeys = [];
                    foreach ($matchIds as $i => $mid) {
                        $pkey = ":cs_{$i}";
                        $inKeys[] = $pkey;
                        $params[$pkey] = $mid;
                    }
                    $where_conditions[] = "p.contract_statu IN (" . implode(',', $inKeys) . ")";
                } else {
                    $where_conditions[] = "p.contract_statu = -1";
                }
            }
        } else if ($idx === 11) {
            $paramKey = ":col_{$idx}";
            $where_conditions[] = "(CASE WHEN ar.action = 'received' THEN 'Teslim Alındı' ELSE 'Teslim Bekliyor' END) LIKE " . $paramKey;
            $params[$paramKey] = "%{$value}%";
        } else if (isset($filter_columns[$idx])) {
            $paramKey = ":col_{$idx}";
            $where_conditions[] = $filter_columns[$idx] . " LIKE " . $paramKey;
            $params[$paramKey] = "%{$value}%";
        }
    }
}

$where_clause = count($where_conditions) ? (" WHERE " . implode(" AND ", $where_conditions)) : "";

$filtered_count_query = "SELECT COUNT(*) as filtered " . $base_query . $where_clause;
$filtered_stmt = $ac->prepare($filtered_count_query);
foreach ($params as $k => $v) {
    $filtered_stmt->bindValue($k, $v);
}
$filtered_stmt->execute();
$filtered_records = $filtered_stmt->fetch(PDO::FETCH_ASSOC)['filtered'];

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

    // Data structure must match the table columns
    $row[] = ''; // Will be filled by DataTables with row number
    $row[] = htmlspecialchars($project['service_number']);
    $companyName = htmlspecialchars(shorted($project['company_name'], 40));
    $row[] = !empty($project['customer_deleted_at'])
        ? '<span class="text-muted">' . $companyName . ' <small class="badge badge-secondary">Silinmiş</small></span>'
        : $companyName;
    $row[] = htmlspecialchars($project['region_name']);
    $row[] = htmlspecialchars($project['service_title']);
    $row[] = htmlspecialchars($project['pregdate']);
    $row[] = htmlspecialchars($project['pstart_date']);

    // Contract status badge from constant mapping
    $row[] = getSozlesmeStatusBadge($project['contract_statu']);

    // Status badge
    $status_color = (!empty($project['status_color'])) ? $project['status_color'] : '#777';
    $status_title = $project['status_title'] ?? '';
    $row[] = "<span class='badge' style='background-color:{$status_color}'>{$status_title}</span>";

    $row[] = htmlspecialchars($project['creator_username']);
    $row[] = htmlspecialchars($project['updater_username'] ?: $project['creator_username']);

    $isAccountingReceived = ($project['accounting_action'] ?? '') === 'received';
    $accountingLabel = $isAccountingReceived ? 'Teslim Alındı' : 'Teslim Bekliyor';
    $accountingClass = $isAccountingReceived ? 'badge-success' : 'badge-warning';
    $accountingInfo = "<span class='badge {$accountingClass}'>{$accountingLabel}</span>";
    if (!empty($project['accounting_actor_username']) && !empty($project['accounting_action_at'])) {
        $accountingInfo .= "<div class='small text-muted'>" . htmlspecialchars($project['accounting_actor_username']) . " - " . htmlspecialchars($project['accounting_action_at']) . "</div>";
    }
    if ($canAccountingReceipt) {
        // Butonlar istenildiği gibi Işlemler kolonunda gösteriliyor.
    }
    $row[] = $accountingInfo;

    // Action buttons
    $pid = $project['id'];
    $actions = '';

    if ($canEdit || $canDel) {
        $actions .= '<div class="text-nowrap" style="display:inline-flex; flex-wrap:nowrap; gap:4px">';
        if ($canEdit) {
            $actions .= '<a href="index.php?p=service/manage&id=' . $pid . '" class="btn btn-sm btn-outline-info" data-tooltip="Düzenle"><i class="fa fa-pencil"></i></a>';
        }
        if ($canDel) {
            $actions .= '<button type="button" class="btn btn-sm btn-danger" data-tooltip="Sil" onClick="deleteRecord(\'' . $pid . ' nolu Servisi silmek istediğinize emin misiniz?\',\'' . $pid . '\',\'services\',\'projects\')"><i class="fa fa-trash"></i></button>';
        }
        $actions .= '<a href="index.php?p=service-view&id=' . Security::encrypt($pid) . '" target="_blank" class="btn btn-sm btn-secondary" data-tooltip="Detay"><i class="fa fa-info-circle"></i></a>';

        if ($canAccountingReceipt) {
            $buttonClass = $isAccountingReceived ? 'btn-outline-danger' : 'btn-outline-success';
            $buttonLabel = $isAccountingReceived ? 'İade Al' : 'Teslim Al';
            $confirmText = $isAccountingReceived
                ? 'Bu servis için muhasebe teslim kaydını iade almak istediğinize emin misiniz?'
                : 'Bu servisi muhasebe teslim alındı olarak işaretlemek istediğinize emin misiniz?';

            $actions .= '<button type="button" class="btn btn-sm ' . $buttonClass . ' js-accounting-receipt-toggle" data-service-id="' . (int) $pid . '" data-confirm="' . htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') . '">' . $buttonLabel . '</button>';
            $actions .= '<button type="button" class="btn btn-sm btn-dark js-accounting-log" data-service-id="' . (int) $pid . '" data-service-number="' . htmlspecialchars($project['service_number'], ENT_QUOTES, 'UTF-8') . '" data-tooltip="Muhasebe Teslim Log"><i class="fa fa-history"></i></button>';
        }

        $actions .= '</div>';
    } else {
        $actions .= '<div class="text-nowrap" style="display:inline-flex; flex-wrap:nowrap; gap:4px">';
        $actions .= '<a href="index.php?p=service-view&id=' . Security::encrypt($pid) . '" target="_blank" class="btn btn-sm btn-secondary" data-tooltip="Detay"><i class="fa fa-info-circle"></i></a>';
        if ($canAccountingReceipt) {
            $buttonClass = $isAccountingReceived ? 'btn-outline-danger' : 'btn-outline-success';
            $buttonLabel = $isAccountingReceived ? 'İade Al' : 'Teslim Al';
            $confirmText = $isAccountingReceived
                ? 'Bu servis için muhasebe teslim kaydını iade almak istediğinize emin misiniz?'
                : 'Bu servisi muhasebe teslim alındı olarak işaretlemek istediğinize emin misiniz?';

            $actions .= '<button type="button" class="btn btn-sm ' . $buttonClass . ' js-accounting-receipt-toggle" data-service-id="' . (int) $pid . '" data-confirm="' . htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') . '">' . $buttonLabel . '</button>';
            $actions .= '<button type="button" class="btn btn-sm btn-dark js-accounting-log" data-service-id="' . (int) $pid . '" data-service-number="' . htmlspecialchars($project['service_number'], ENT_QUOTES, 'UTF-8') . '" data-tooltip="Muhasebe Teslim Log"><i class="fa fa-history"></i></button>';
        }
        $actions .= '</div>';
    }

    $row[] = $actions;

    $response['data'][] = $row;
}

// Ensure no stray output breaks JSON
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
echo json_encode($response);
exit;
