<?php
// API endpoint for DataTables server-side processing for customers
header('Content-Type: application/json');

// Include bootstrap to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

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

// Column-specific search
// index mappings:
// 1 => c.company, 2 => cg.title, 3 => c.represant, 5 => c.email, 6 => c.gsm, 7 => c.regdate
$filter_columns = [
    1 => 'c.company',
    2 => 'cg.title',
    3 => 'c.represant',
    5 => 'c.email',
    6 => 'c.gsm',
    7 => 'c.regdate',
];

if (!empty($requested_columns) && is_array($requested_columns)) {
    foreach ($requested_columns as $idx => $col) {
        $value = $col['search']['value'] ?? '';
        $idx = intval($idx);
        if ($value === '') {
            continue;
        }
        if (isset($filter_columns[$idx])) {
            $paramKey = ":col_{$idx}";
            if ($idx === 7) {
                $where_conditions[] = "(DATE_FORMAT(c.regdate, '%d.%m.%Y') LIKE {$paramKey} OR c.regdate LIKE {$paramKey})";
            } else {
                $where_conditions[] = $filter_columns[$idx] . " LIKE " . $paramKey;
            }
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
    $row[] = $cid;

    // Column 1: Firma Adı (company link)
    $link = $canEdit ? "index.php?p=customers/manage&id=" . $cid : "#";
    $company_name = htmlspecialchars($row_data['company']);
    $short_company = htmlspecialchars(shorted($row_data['company'], 40));
    $row[] = '<a href="' . $link . '" data-toggle="tooltip" data-tooltip="' . $company_name . '">' . $short_company . '</a>';

    // Column 2: Grup (group_title)
    $row[] = htmlspecialchars($row_data['group_title'] ?? '');

    // Column 3: Satış Temsilcisi (represant)
    $row[] = htmlspecialchars($row_data['represant'] ?? '');

    // Column 4: Teklif/Servis Sayısı (offer_count / project_count)
    $row[] = intval($row_data['offer_count']) . ' / ' . intval($row_data['project_count']);

    // Column 5: E-Posta Adresi (email)
    $row[] = htmlspecialchars($row_data['email'] ?? '');

    // Column 6: GSM (gsm)
    $row[] = htmlspecialchars($row_data['gsm'] ?? '');

    // Column 7: Kayıt Tarihi
    $regDate = !empty($row_data['regdate']) && $row_data['regdate'] !== '0000-00-00 00:00:00'
        ? date('d.m.Y', strtotime($row_data['regdate']))
        : '-';
    $row[] = htmlspecialchars($regDate);

    // Column 8: İşlem
    $actions = '<div class="text-nowrap" style="display:inline-flex; flex-wrap:nowrap; gap:4px">';
    if ($canEdit) {
        $actions .= '<a href="index.php?p=customers/manage&id=' . $cid . '" class="btn btn-sm btn-outline-info" data-tooltip="Görüntüle-Düzenle">
                <i class="fa fa-pencil"></i>
        </a>';
    }
    if ($canDel) {
        $actions .= '<a href="#" class="btn btn-sm btn-danger" data-tooltip="Sil" onClick="deleteRecord(\'Firma aktif müşteri listesinden kaldırılacaktır. Firmaya bağlı teklif ve servis kayıtları korunacaktır. Devam etmek istiyor musunuz?\',\'' . $cid . '\',\'customers\')">
                <i class="fa fa-trash"></i>
        </a>';
    }

    // Dropdown menu
    $encrypted_cid = encrypt($cid);
    $actions .= '<div class="dropdown d-inline">
        <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu_' . $cid . '" data-toggle="dropdown">
            <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu_' . $cid . '">
            <a href="index.php?p=customer-label&id=' . $cid . '" target="_blank" class="dropdown-item" type="button">
                <i class="fa fa-print mr-2"></i>
                Etiket Göster</a>
            <a href="index.php?p=customer-label" target="_blank" class="dropdown-item" type="button">
                <i class="fa fa-send mr-2"></i>
                Sms Gönder</a>
            <a href="index.php?p=send-mail&customer=' . urlencode($encrypted_cid) . '" target="_blank" class="dropdown-item" type="button">
                <i class="fa fa-envelope-o mr-2"></i>
                Email Gönder</a>
            <a class="btn-detail btn dropdown-item" data-id="' . $cid . '" type="button">
                <i class="fa fa-copy mr-2"></i>
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
