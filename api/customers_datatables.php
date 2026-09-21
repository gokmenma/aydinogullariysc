<?php
// API endpoint for DataTables server-side processing for customers
header('Content-Type: application/json');

// Include bootstrap to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

// Permission check: return JSON instead of redirect
if (!permtrue("customeredit") && !permtrue("customeradd") && !permtrue("customerdelete")) {
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
$requested_columns = $_GET['columns'] ?? [];

// Column names for ordering (DataTables indexes)
// 0: c.id, 1: c.company, 2: cg.title, 3: c.represant, 4: (offers/projects count, ignore order or order by ID), 5: c.email, 6: c.gsm, 7: c.regdate
$columns = ['c.id', 'c.company', 'cg.title', 'c.represant', 'c.id', 'c.email', 'c.gsm', 'c.regdate'];
$order_by = $columns[$order_column] ?? 'c.id';

// Base query with JOINs
$base_query = "
    FROM customers c
    LEFT JOIN cgroups cg ON cg.id = c.grp
";

// Count total records
$count_query = "SELECT COUNT(*) as total " . $base_query . " WHERE c.deleted_at IS NULL";
$count_stmt = $ac->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count filtered records
$where_conditions = ["c.deleted_at IS NULL"];
$params = [];

// Global search
if ($search_value !== '') {
    $where_conditions[] = "(
        c.id LIKE :search OR
        c.company LIKE :search OR
        cg.title LIKE :search OR
        c.represant LIKE :search OR
        c.email LIKE :search OR
        c.gsm LIKE :search OR
        DATE_FORMAT(c.regdate, '%d.%m.%Y') LIKE :search OR
        c.regdate LIKE :search
    )";
    $params[':search'] = "%{$search_value}%";
}

use App\Helper\DataTableFilter;

// Column configs for DataTableFilter
$column_configs = [
    1 => ['expr' => 'c.company', 'type' => 'text'],
    2 => ['expr' => 'cg.title', 'type' => 'text'],
    3 => ['expr' => 'c.represant', 'type' => 'text'],
    5 => ['expr' => 'c.email', 'type' => 'text'],
    6 => ['expr' => 'c.gsm', 'type' => 'text'],
    7 => ['expr' => 'c.regdate', 'type' => 'date'],
];

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
            $cond = DataTableFilter::buildCondition($cfg['expr'], $filter, $params, $prefix, $cfg['type'] ?? 'text');
            if (!empty($cond)) {
                $where_conditions[] = $cond;
            }
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

// Main data query with scalar subqueries for counts (optimized)
$data_query = "
    SELECT 
        c.id,
        c.company,
        c.grp,
        c.represant,
        c.email,
        c.gsm,
        c.regdate,
        cg.title as group_title,
        (SELECT COUNT(*) FROM offers o WHERE o.cid = c.id) as offer_count,
        (SELECT COUNT(*) FROM projects p WHERE p.pcid = c.id) as project_count
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

$customers = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response
$response = [
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => []
];

$canEdit = permtrue("customeredit");
$canDel = permtrue("customerdelete");

foreach ($customers as $row_data) {
    $row = [];
    $cid = $row_data['id'];

    // Column 0: Sıra (id)
    $row[] = '<span class="row-index-badge">' . $cid . '</span>';

    // Column 1: Firma Adı (company link)
    $link = $canEdit ? "index.php?p=customers/manage&id=" . $cid : "#";
    $company_name = htmlspecialchars($row_data['company']);
    $short_company = htmlspecialchars(shorted($row_data['company'], 45));
    $row[] = '<div class="customer-title-cell"><a href="' . $link . '" class="font-weight-600 text-primary-hover" data-toggle="tooltip" data-tooltip="' . $company_name . '">' . $short_company . '</a></div>';

    // Column 2: Grup (group_title)
    $group_title = trim($row_data['group_title'] ?? '');
    if ($group_title !== '') {
        $row[] = '<span class="badge-group">' . htmlspecialchars($group_title) . '</span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 3: Satış Temsilcisi (represant)
    $represant = trim($row_data['represant'] ?? '');
    if ($represant !== '') {
        $row[] = '<span class="badge-represant"><i class="fa fa-user-circle-o text-muted mr-1"></i>' . htmlspecialchars($represant) . '</span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 4: Teklif/Servis Sayısı (offer_count / project_count)
    $offer_cnt = intval($row_data['offer_count']);
    $proj_cnt = intval($row_data['project_count']);
    $stats_html = '<div class="d-inline-flex align-items-center" style="gap: 4px;">';
    $stats_html .= '<span class="badge-stat-tag badge-stat-offers" title="Teklif Sayısı"><i class="fa fa-file-text-o mr-1"></i>' . $offer_cnt . '</span>';
    $stats_html .= '<span class="badge-stat-tag badge-stat-services" title="Servis/Proje Sayısı"><i class="fa fa-wrench mr-1"></i>' . $proj_cnt . '</span>';
    $stats_html .= '</div>';
    $row[] = $stats_html;

    // Column 5: E-Posta Adresi (email)
    $email = trim($row_data['email'] ?? '');
    if ($email !== '') {
        $row[] = '<a href="mailto:' . htmlspecialchars($email) . '" class="text-muted text-nowrap font-12" title="' . htmlspecialchars($email) . '"><i class="fa fa-envelope-o mr-1 text-primary"></i>' . htmlspecialchars(shorted($email, 22)) . '</a>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 6: GSM (gsm)
    $gsm = trim($row_data['gsm'] ?? '');
    if ($gsm !== '') {
        $row[] = '<a href="tel:' . htmlspecialchars($gsm) . '" class="text-muted text-nowrap font-12"><i class="fa fa-phone mr-1 text-success"></i>' . htmlspecialchars($gsm) . '</a>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 7: Kayıt Tarihi
    $regDate = !empty($row_data['regdate']) && $row_data['regdate'] !== '0000-00-00 00:00:00'
        ? date('d.m.Y', strtotime($row_data['regdate']))
        : '-';
    $row[] = '<span class="badge-date"><i class="fa fa-calendar-o mr-1"></i>' . htmlspecialchars($regDate) . '</span>';

    // Column 8: İşlem
    $actions = '<div class="action-btn-group">';
    if ($canEdit) {
        $actions .= '<a href="index.php?p=customers/manage&id=' . $cid . '" class="btn btn-sm btn-outline-info action-btn" data-tooltip="Görüntüle / Düzenle">
                <i class="fa fa-pencil"></i>
        </a>';
    }
    if ($canDel) {
        $actions .= '<a href="#" class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord(\'Firma aktif müşteri listesinden kaldırılacaktır. Firmaya bağlı teklif ve servis kayıtları korunacaktır. Devam etmek istiyor musunuz?\',\'' . $cid . '\',\'customers\')">
                <i class="fa fa-trash"></i>
        </a>';
    }

    // Dropdown menu
    $encrypted_cid = encrypt($cid);
    $actions .= '<div class="dropdown d-inline">
        <button class="btn btn-outline-secondary btn-sm action-btn" type="button" id="dropdownMenu_' . $cid . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Diğer İşlemler">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu_' . $cid . '">
            <a href="index.php?p=customer-label&id=' . $cid . '" target="_blank" class="dropdown-item">
                <i class="fa fa-print mr-2 text-primary"></i>
                Etiket Göster</a>
            <a href="index.php?p=customer-label" target="_blank" class="dropdown-item">
                <i class="fa fa-send mr-2 text-info"></i>
                SMS Gönder</a>
            <a href="index.php?p=send-mail&customer=' . urlencode($encrypted_cid) . '" target="_blank" class="dropdown-item">
                <i class="fa fa-envelope-o mr-2 text-success"></i>
                Email Gönder</a>
            <div class="dropdown-divider"></div>
            <a class="btn-detail btn dropdown-item" data-id="' . $cid . '" type="button">
                <i class="fa fa-info-circle mr-2 text-secondary"></i>
                Detay Bilgisi</a>
        </div>
    </div></div>';

    $row[] = $actions;
    $response['data'][] = $row;
}

// Clear buffers to make sure no stray output breaks JSON
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

echo json_encode($response);
exit;
