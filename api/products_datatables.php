<?php
// API endpoint for DataTables server-side processing for products
header('Content-Type: application/json');

// Include bootstrap and functions to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use App\Helper\Security;

// Permission check: return JSON instead of redirect
if (!permtrue("productedit") && !permtrue("productadd") && !permtrue("productdelete")) {
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
$order_dir = $_GET['order'][0]['dir'] ?? 'asc';
$requested_columns = $_GET['columns'] ?? [];

// Column names for ordering (DataTables indexes)
// 0: p.ID, 1: p.StokKodu, 2: p.Adi, 3: u.title, 4: p.AlisFiyati, 5: p.SatisFiyati, 6: p.Aciklama, 7: p.OlusturmaTarihi
$columns = ['p.ID', 'p.StokKodu', 'p.Adi', 'u.title', 'p.AlisFiyati', 'p.SatisFiyati', 'p.Aciklama', 'p.OlusturmaTarihi'];
$order_by = $columns[$order_column] ?? 'p.ID';

// Base query with JOINs
$base_query = "
    FROM products p
    LEFT JOIN units u ON u.id = p.Birimi
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
        p.ID LIKE :search OR
        p.StokKodu LIKE :search OR
        p.Adi LIKE :search OR
        u.title LIKE :search OR
        p.AlisFiyati LIKE :search OR
        p.SatisFiyati LIKE :search OR
        p.Aciklama LIKE :search OR
        p.OlusturmaTarihi LIKE :search
    )";
    $params[':search'] = "%{$search_value}%";
}

// Column-specific search
$filter_columns = [
    1 => 'p.StokKodu',
    2 => 'p.Adi',
    3 => 'u.title',
    4 => 'p.AlisFiyati',
    5 => 'p.SatisFiyati',
    6 => 'p.Aciklama',
    7 => 'p.OlusturmaTarihi',
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
                $altVal = str_replace('.', '-', $value);
                $where_conditions[] = "(p.OlusturmaTarihi LIKE {$paramKey} OR p.OlusturmaTarihi LIKE :col_7_alt)";
                $params[$paramKey] = "%{$value}%";
                $params[':col_7_alt'] = "%{$altVal}%";
            } else {
                $where_conditions[] = $filter_columns[$idx] . " LIKE " . $paramKey;
                $params[$paramKey] = "%{$value}%";
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

// Main data query
$data_query = "
    SELECT 
        p.ID,
        p.StokKodu,
        p.Adi,
        p.AlisFiyati,
        p.AlisParaBirimi,
        p.SatisFiyati,
        p.SatisParaBirimi,
        p.Aciklama,
        p.OlusturmaTarihi,
        u.title as birim
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

$products = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response
$response = [
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => []
];

$canEdit = permtrue("productedit");
$canDel = permtrue("productdelete");

$siraNo = $start + 1;
foreach ($products as $row_data) {
    $row = [];
    $pid = $row_data['ID'];
    $enc_id = Security::encrypt($pid);

    // Column 0: Sıra
    $row[] = $siraNo++;

    // Column 1: Stok Kodu
    $row[] = htmlspecialchars($row_data['StokKodu'] ?? '');

    // Column 2: Ürün/Hizmet Adı
    $fullName = htmlspecialchars($row_data['Adi'] ?? '');
    $shortName = htmlspecialchars(shorted($row_data['Adi'] ?? '', 40));
    $row[] = '<span class="text-nowrap" data-tooltip="' . $fullName . '">' . $shortName . '</span>';

    // Column 3: Birimi
    $row[] = htmlspecialchars($row_data['birim'] ?? '');

    // Column 4: Alış Fiyatı
    $row[] = htmlspecialchars($row_data['AlisFiyati'] ?? '') . ' ' . htmlspecialchars($row_data['AlisParaBirimi'] ?? '');

    // Column 5: Satış Fiyatı
    $row[] = htmlspecialchars($row_data['SatisFiyati'] ?? '') . ' ' . htmlspecialchars($row_data['SatisParaBirimi'] ?? '');

    // Column 6: Açıklama
    $row[] = htmlspecialchars($row_data['Aciklama'] ?? '');

    // Column 7: Kayıt Tarihi
    $regDate = !empty($row_data['OlusturmaTarihi']) ? str_replace('-', '.', $row_data['OlusturmaTarihi']) : '-';
    $row[] = htmlspecialchars($regDate);

    // Column 8: İşlem
    $actions = '<div class="text-center text-nowrap pl-3 pr-3" style="display:inline-flex; flex-wrap:nowrap; gap:4px">';
    if ($canEdit) {
        $actions .= '<a class="btn btn-sm btn-outline-info" data-tooltip="Düzenle" href="index.php?p=products/manage&id=' . $enc_id . '">
            <i class="fa fa-edit"></i>
        </a>';
    }
    
    $actions .= '<a href="#" class="btn btn-sm btn-danger product-delete" data-tooltip="Sil!" data-id="' . $enc_id . '" data-name="' . htmlspecialchars($row_data['Adi'] ?? '', ENT_QUOTES) . '">
        <i class="fa fa-trash"></i>
    </a>';

    $actions .= '<div class="dropdown d-inline">
        <button class="btn btn-secondary btn-sm" type="button" id="dropdownMenu_' . $pid . '" data-toggle="dropdown">
            <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail" aria-labelledby="dropdownMenu_' . $pid . '">
            <a href="index.php?p=purchase-demand-detail&id=" target="_blank" class="dropdown-item" type="button">
                <i class="fa fa-list-ol mr-2" aria-hidden="true"></i>
                Stok Hareketleri
            </a>
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
