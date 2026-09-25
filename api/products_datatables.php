<?php
// API endpoint for DataTables server-side processing for products
header('Content-Type: application/json');

// Include bootstrap and functions to ensure consistent setup
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

use App\Helper\Security;

// Liste sayfasıyla aynı ürün modülü yetkilerini kabul et; yetkisiz istekte JSON dön.
$canViewProducts = permtrue("product_dashboard")
    || permtrue("productcategory")
    || permtrue("productadd")
    || permtrue("productedit")
    || permtrue("productdelete");

if (!$canViewProducts) {
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

use App\Helper\DataTableFilter;

// Column configs for DataTableFilter
$column_configs = [
    1 => ['expr' => 'p.StokKodu', 'type' => 'text'],
    2 => ['expr' => 'p.Adi', 'type' => 'text'],
    3 => ['expr' => 'u.title', 'type' => 'text'],
    4 => ['expr' => 'p.AlisFiyati', 'type' => 'number'],
    5 => ['expr' => 'p.SatisFiyati', 'type' => 'number'],
    6 => ['expr' => 'p.Aciklama', 'type' => 'text'],
    7 => ['expr' => 'p.OlusturmaTarihi', 'type' => 'datetime'],
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
    $row[] = '<span class="row-index-badge">' . ($siraNo++) . '</span>';

    // Column 1: Stok Kodu
    $stokKodu = trim($row_data['StokKodu'] ?? '');
    if (!empty($stokKodu)) {
        $row[] = '<span class="badge-sku"><i class="fa fa-barcode mr-1 opacity-75"></i>' . htmlspecialchars($stokKodu) . '</span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 2: Ürün/Hizmet Adı
    $fullName = htmlspecialchars($row_data['Adi'] ?? '');
    $shortName = htmlspecialchars(shorted($row_data['Adi'] ?? '', 45));
    $row[] = '<div class="product-title-cell" data-tooltip="' . $fullName . '"><span class="weight-600 text-dark font-14">' . $shortName . '</span></div>';

    // Column 3: Birimi
    $birim = trim($row_data['birim'] ?? '');
    if (!empty($birim)) {
        $row[] = '<span class="badge-unit">' . htmlspecialchars($birim) . '</span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 4: Alış Fiyatı
    $alisFiyati = trim($row_data['AlisFiyati'] ?? '');
    $alisParaBirimi = trim($row_data['AlisParaBirimi'] ?? 'TRY');
    if ($alisFiyati !== '' && is_numeric($alisFiyati)) {
        $row[] = '<span class="text-muted font-weight-500 font-13">' . number_format((float)$alisFiyati, 2, ',', '.') . ' <span class="badge-currency">' . htmlspecialchars($alisParaBirimi) . '</span></span>';
    } elseif ($alisFiyati !== '') {
        $row[] = '<span class="text-muted font-weight-500 font-13">' . htmlspecialchars($alisFiyati) . ' <span class="badge-currency">' . htmlspecialchars($alisParaBirimi) . '</span></span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 5: Satış Fiyatı
    $satisFiyati = trim($row_data['SatisFiyati'] ?? '');
    $satisParaBirimi = trim($row_data['SatisParaBirimi'] ?? 'TRY');
    if ($satisFiyati !== '' && is_numeric($satisFiyati)) {
        $row[] = '<span class="product-price-cell text-success font-weight-bold font-14">' . number_format((float)$satisFiyati, 2, ',', '.') . ' <span class="badge-currency badge-currency-success">' . htmlspecialchars($satisParaBirimi) . '</span></span>';
    } elseif ($satisFiyati !== '') {
        $row[] = '<span class="product-price-cell text-success font-weight-bold font-14">' . htmlspecialchars($satisFiyati) . ' <span class="badge-currency badge-currency-success">' . htmlspecialchars($satisParaBirimi) . '</span></span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 6: Açıklama
    $aciklama = trim($row_data['Aciklama'] ?? '');
    if (!empty($aciklama)) {
        $row[] = '<span class="text-muted font-12" data-tooltip="' . htmlspecialchars($aciklama) . '">' . htmlspecialchars(shorted($aciklama, 35)) . '</span>';
    } else {
        $row[] = '<span class="text-muted font-12">-</span>';
    }

    // Column 7: Kayıt Tarihi
    $regDate = !empty($row_data['OlusturmaTarihi']) ? str_replace('-', '.', $row_data['OlusturmaTarihi']) : '-';
    $row[] = '<span class="text-muted font-12 text-nowrap"><i class="fa fa-calendar-o mr-1 text-secondary opacity-75"></i>' . htmlspecialchars($regDate) . '</span>';

    // Column 8: İşlem
    $actions = '<div class="action-btn-group text-center text-nowrap">';
    if ($canEdit) {
        $actions .= '<a class="btn btn-sm btn-outline-primary action-btn" data-tooltip="Düzenle" href="index.php?p=products/manage&id=' . $enc_id . '">
            <i class="fa fa-pencil"></i>
        </a>';
    }
    
    if ($canDel) {
        $actions .= '<a href="javascript:void(0);" class="btn btn-sm btn-outline-danger action-btn product-delete" data-tooltip="Sil" data-id="' . $enc_id . '" data-name="' . htmlspecialchars($row_data['Adi'] ?? '', ENT_QUOTES) . '">
            <i class="fa fa-trash-o"></i>
        </a>';
    }

    $actions .= '<div class="dropdown d-inline">
        <button class="btn btn-sm btn-outline-secondary action-btn" type="button" id="dropdownMenu_' . $pid . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail shadow-sm" aria-labelledby="dropdownMenu_' . $pid . '">
            <a href="index.php?p=products/manage&id=' . $enc_id . '" class="dropdown-item">
                <i class="fa fa-info-circle mr-2 text-primary"></i> Detay & Düzenle
            </a>
            <div class="dropdown-divider"></div>
            <a href="index.php?p=purchase-demand-detail&id=" target="_blank" class="dropdown-item">
                <i class="fa fa-history mr-2 text-info"></i> Stok Hareketleri
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
