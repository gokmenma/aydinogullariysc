<?php
// API endpoint for DataTables server-side processing for reports
header('Content-Type: application/json');

// Include bootstrap and functions to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

// Permission check
if (!permtrue("reportview")) {
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
// 0: r.id, 1: r.report_number, 2: c.company, 3: rt.reportName, 4: r.isemrino, 5: r.control_date, 6: r.validity_date, 7: r.create_time, 8: u.username
$columns = ['r.id', 'r.report_number', 'c.company', 'rt.reportName', 'r.isemrino', 'r.control_date', 'r.validity_date', 'r.create_time', 'u.username'];
$order_by = $columns[$order_column] ?? 'r.id';

// Base query with JOINs
$base_query = "
    FROM reports r 
    LEFT JOIN report_types rt on rt.id = r.report_type  
    LEFT JOIN customers c on c.id = r.customer_id
    LEFT JOIN users u on u.id = r.creator
";

// Count total records
$count_query = "SELECT COUNT(*) as total FROM reports";
$count_stmt = $ac->prepare($count_query);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Count filtered records
$where_conditions = [];
$params = [];

// Global search
if ($search_value !== '') {
    $where_conditions[] = "(
        r.id LIKE :search OR
        r.report_number LIKE :search OR
        c.company LIKE :search OR
        rt.reportName LIKE :search OR
        r.isemrino LIKE :search OR
        r.control_date LIKE :search OR
        r.validity_date LIKE :search OR
        r.create_time LIKE :search OR
        u.username LIKE :search
    )";
    $params[':search'] = "%{$search_value}%";
}

use App\Helper\DataTableFilter;

// Column configs for DataTableFilter
$column_configs = [
    0 => ['expr' => 'r.id', 'type' => 'number'],
    1 => ['expr' => 'r.report_number', 'type' => 'text'],
    2 => ['expr' => 'c.company', 'type' => 'text'],
    3 => ['expr' => 'rt.reportName', 'type' => 'text'],
    4 => ['expr' => 'r.isemrino', 'type' => 'text'],
    5 => ['expr' => 'r.control_date', 'type' => 'date'],
    6 => ['expr' => 'r.validity_date', 'type' => 'date'],
    7 => ['expr' => 'r.create_time', 'type' => 'datetime'],
    8 => ['expr' => 'u.username', 'type' => 'text'],
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
$filtered_records = $filtered_stmt->fetch(PDO::FETCH_ASSOC)['filtered'] ?? 0;

// Main data query
$data_query = "
    SELECT 
        r.id,
        r.report_number,
        r.customer_id,
        r.isemrino,
        r.control_date,
        r.validity_date,
        r.create_time,
        rt.reportName,
        rt.page_link,
        c.company,
        c.deleted_at as customer_deleted_at,
        u.username as creator_username
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

$reports_list = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response
$response = [
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => []
];

$canEdit = permtrue("reportedit");
$canDel = permtrue("reportdel");
$canViewOffer = permtrue("offerview");

foreach ($reports_list as $row_data) {
    $row = [];
    $rid = $row_data['id'];
    $pageLink = $row_data['page_link'] ?? '';

    // Page links
    $newpagelink = "index.php?p=reports/" . $pageLink . "/report-new-" . $pageLink;
    $edit_file = ($pageLink == "yas") ? "report-new-" : "report-edit-";
    $editpagelink = "index.php?p=reports/" . $pageLink . "/" . $edit_file . $pageLink . "&id=" . $rid;
    $viewpagelink = "index.php?p=reports/" . $pageLink . "/report-view-" . $pageLink . "&id=" . $rid;
    $send_mail_link = "index.php?p=report-send-as-mail&type=" . $pageLink . "&id=" . $rid;

    // 0: ID
    $row[] = htmlspecialchars($rid);

    // 1: Rapor No (Tıklanınca Raporu Gösterir)
    $reportNo = htmlspecialchars($row_data['report_number'] ?? '');
    if (!empty($pageLink) && $reportNo !== '') {
        $row[] = '<a href="' . htmlspecialchars($viewpagelink) . '" target="_blank" class="report-num-link font-weight-600 text-primary" data-toggle="tooltip" title="Raporu Göster">' . $reportNo . '</a>';
    } else {
        $row[] = $reportNo !== '' ? $reportNo : '-';
    }

    // 2: Firma (Tıklanınca Firma Detayına Gider)
    $customerId = (int)($row_data['customer_id'] ?? 0);
    $companyName = (string)($row_data['company'] ?? '');
    $fullName = htmlspecialchars($companyName);
    $shortName = htmlspecialchars(shorted($companyName, 40));

    if (!empty($row_data['customer_deleted_at'])) {
        $row[] = '<span class="text-nowrap" data-toggle="tooltip" title="' . $fullName . '"><span class="text-muted">' . $shortName . '</span> <small class="crm-badge-soft soft-amber font-11">Silinmiş</small></span>';
    } elseif ($customerId > 0 && $companyName !== '') {
        $customerLink = "index.php?p=customers/manage&id=" . $customerId;
        $row[] = '<span class="text-nowrap" data-toggle="tooltip" title="' . $fullName . '"><a href="' . htmlspecialchars($customerLink) . '" class="report-company-link font-weight-600 text-dark">' . $shortName . '</a></span>';
    } elseif ($companyName !== '') {
        $row[] = '<span class="text-nowrap" data-toggle="tooltip" title="' . $fullName . '">' . $shortName . '</span>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 3: Rapor Türü
    $row[] = htmlspecialchars($row_data['reportName'] ?? '');

    // 4: İş Emri No
    $row[] = htmlspecialchars($row_data['isemrino'] ?? '');

    // 5: Kontrol Tarihi
    $row[] = htmlspecialchars($row_data['control_date'] ?? '');

    // 6: Geçerlilik Tarihi
    $row[] = htmlspecialchars($row_data['validity_date'] ?? '');

    // 7: Kayıt Tarihi
    $createTimeRaw = $row_data['create_time'] ?? '';
    if ($createTimeRaw) {
        $timestamp = strtotime($createTimeRaw);
        $dateFormatted = $timestamp ? date('d.m.Y', $timestamp) : htmlspecialchars($createTimeRaw);
        $timeFormatted = $timestamp ? date('H:i', $timestamp) : '';
        $row[] = '<div class="text-center font-12" style="line-height:1.25;"><span class="text-dark font-weight-500">' . $dateFormatted . '</span>' . ($timeFormatted ? '<br><span class="text-muted font-11">' . $timeFormatted . '</span>' : '') . '</div>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 8: Kayıt Yapan
    $creatorUsername = htmlspecialchars($row_data['creator_username'] ?? '');
    if ($creatorUsername !== '') {
        $row[] = '<div class="text-center font-12"><span class="font-weight-500 text-dark"><i class="fa fa-user-circle text-secondary mr-1"></i>' . $creatorUsername . '</span></div>';
    } else {
        $row[] = '<span class="text-muted text-center d-block">-</span>';
    }

    // 9: İşlem (Actions)
    $actions = '<div class="action-btn-group text-center text-nowrap">';
    if ($canEdit) {
        $actions .= '<a type="button" href="' . htmlspecialchars($editpagelink) . '" class="btn btn-sm btn-outline-primary action-btn" data-tooltip="Düzenle">
            <i class="fa fa-pencil"></i>
        </a>';
    }
    
    if ($canDel) {
        $confirmMsg = htmlspecialchars($row_data["report_number"] . ' nolu raporu silmek istediğinize emin misiniz?', ENT_QUOTES);
        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord(\'' . $confirmMsg . '\', \'' . $rid . '\', \'reports/reports\', \'reports\')">
            <i class="fa fa-trash"></i>
        </button>';
    }

    $actions .= ' <div class="dropdown d-inline">
        <button class="btn btn-outline-secondary btn-sm action-btn" type="button" id="dropdownMenu_' . $rid . '" data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false" title="Diğer İşlemler">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu_' . $rid . '">';

    if ($canViewOffer) {
        $actions .= ' <a href="' . htmlspecialchars($viewpagelink) . '" target="_blank" class="dropdown-item">
            <i class="fa fa-file-text-o text-primary mr-2"></i> Raporu Göster
        </a>
        <a href="' . htmlspecialchars($viewpagelink . '&sign=no') . '" target="_blank" class="dropdown-item">
            <i class="fa fa-file-o text-info mr-2"></i> İmzasız Raporu Göster
        </a>';
    }

    $actions .= ' <a href="' . htmlspecialchars($send_mail_link) . '" target="_blank" class="dropdown-item">
        <i class="fa fa-paper-plane-o text-success mr-2"></i> Mail Gönder
    </a>
    <div class="dropdown-divider"></div>
    <a class="btn-report-detail btn dropdown-item" data-id="' . $rid . '" type="button">
        <i class="fa fa-info-circle text-secondary mr-2"></i> Detay Bilgisi
    </a>';

    $actions .= '</div></div></div>';

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
