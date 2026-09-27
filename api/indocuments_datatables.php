<?php
// API endpoint for DataTables server-side processing for incoming documents (Gelen Evraklar)
if (function_exists('ob_start')) {
    ob_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

use App\Helper\Security;
use App\Helper\DataTableFilter;

$userId = (int)($_SESSION['lid'] ?? ($_SESSION['id'] ?? (function_exists('sesset') ? sesset("id") : 0)));
$isLoggedIn = ($userId > 0) || !empty($_SESSION['login']);

$params_source = !empty($_POST['draw']) ? $_POST : $_GET;
$draw = intval($params_source['draw'] ?? 0);

if (!$isLoggedIn) {
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Oturum süresi doldu. Lütfen sayfayı yenileyin.'
    ]);
    exit;
}

try {
    $start = intval($params_source['start'] ?? 0);
    $length = intval($params_source['length'] ?? 25);
    if ($length <= 0) {
        $length = 25;
    }
    $search_value = trim($params_source['search']['value'] ?? '');
    $order_column = intval($params_source['order'][0]['column'] ?? 0);
    $order_dir = strtolower($params_source['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $requested_columns = $params_source['columns'] ?? [];

    // Column names for ordering
    $columns = [
        0 => 'e.id',
        1 => 'COALESCE(c.company, e.firma)',
        2 => 'e.evrakturu',
        3 => 'e.kategori',
        4 => 'CAST(e.adet AS UNSIGNED)',
        5 => 'u_teslimeden.username',
        6 => 'u_teslimalan.username',
        7 => 'e.teslimtarihi',
        8 => 'e.estatu',
        9 => 'e.aciklama',
        10 => 'e.id'
    ];

    $order_by = $columns[$order_column] ?? 'e.id';

    // Base FROM and JOINs
    $base_from = "
        FROM evraktakip e
        LEFT JOIN customers c ON c.id = e.firma
        LEFT JOIN users u_teslimalan ON e.teslimalan = u_teslimalan.id
        LEFT JOIN users u_teslimeden ON e.teslimeden = u_teslimeden.id
    ";

    // Count total records for 'Gelen'
    $count_query = "SELECT COUNT(*) as total FROM evraktakip WHERE evrakturu = 'Gelen'";
    $count_stmt = $ac->query($count_query);
    $total_records = $count_stmt ? (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;

    $where_conditions = ["e.evrakturu = 'Gelen'"];
    $params = [];

    // Global search
    if ($search_value !== '') {
        $where_conditions[] = "(
            e.id LIKE :gsearch_0 OR
            c.company LIKE :gsearch_1 OR
            e.firma LIKE :gsearch_2 OR
            e.kategori LIKE :gsearch_3 OR
            e.adet LIKE :gsearch_4 OR
            u_teslimeden.username LIKE :gsearch_5 OR
            u_teslimalan.username LIKE :gsearch_6 OR
            e.teslimtarihi LIKE :gsearch_7 OR
            e.estatu LIKE :gsearch_8 OR
            e.aciklama LIKE :gsearch_9
        )";
        $params[':gsearch_0'] = "%{$search_value}%";
        $params[':gsearch_1'] = "%{$search_value}%";
        $params[':gsearch_2'] = "%{$search_value}%";
        $params[':gsearch_3'] = "%{$search_value}%";
        $params[':gsearch_4'] = "%{$search_value}%";
        $params[':gsearch_5'] = "%{$search_value}%";
        $params[':gsearch_6'] = "%{$search_value}%";
        $params[':gsearch_7'] = "%{$search_value}%";
        $params[':gsearch_8'] = "%{$search_value}%";
        $params[':gsearch_9'] = "%{$search_value}%";
    }

    // Column level filters (DataTableFilter)
    $column_configs = [
        0 => ['expr' => 'e.id', 'type' => 'number'],
        1 => ['expr' => 'COALESCE(c.company, e.firma)', 'type' => 'text'],
        2 => ['expr' => 'e.evrakturu', 'type' => 'text'],
        3 => ['expr' => 'e.kategori', 'type' => 'text'],
        4 => ['expr' => 'e.adet', 'type' => 'text'],
        5 => ['expr' => 'u_teslimeden.username', 'type' => 'text'],
        6 => ['expr' => 'u_teslimalan.username', 'type' => 'text'],
        7 => ['expr' => 'e.teslimtarihi', 'type' => 'text'],
        8 => ['expr' => 'e.estatu', 'type' => 'text'],
        9 => ['expr' => 'e.aciklama', 'type' => 'text'],
    ];

    if (!empty($requested_columns) && is_array($requested_columns)) {
        foreach ($requested_columns as $idx => $col) {
            $rawSearch = $col['search']['value'] ?? '';
            $idx = intval($idx);
            if ($rawSearch === '') {
                continue;
            }

            $filter = DataTableFilter::parse($rawSearch);
            if (!$filter) {
                continue;
            }

            if (isset($column_configs[$idx])) {
                $config = $column_configs[$idx];
                $cond = DataTableFilter::buildCondition(
                    $config['expr'],
                    $filter,
                    $params,
                    "col_{$idx}_",
                    $config['type']
                );
                if ($cond !== '') {
                    $where_conditions[] = $cond;
                }
            }
        }
    }

    $where_clause = " WHERE " . implode(" AND ", $where_conditions);

    // Filtered total count
    $filtered_count_query = "SELECT COUNT(*) as filtered " . $base_from . $where_clause;
    $filtered_stmt = $ac->prepare($filtered_count_query);
    foreach ($params as $k => $v) {
        $filtered_stmt->bindValue($k, $v);
    }
    $filtered_stmt->execute();
    $filtered_records = (int)($filtered_stmt->fetch(PDO::FETCH_ASSOC)['filtered'] ?? 0);

    // Main data query
    $data_query = "
        SELECT 
            e.id,
            e.firma,
            e.evrakturu,
            e.kategori,
            e.adet,
            e.teslimalan,
            e.teslimalmatarihi,
            e.teslimeden,
            e.teslimtarihi,
            e.estatu,
            e.aciklama,
            c.company AS customer_company,
            c.id AS customer_id,
            u_teslimalan.username AS teslim_alan_username,
            u_teslimeden.username AS teslim_eden_username
        " . $base_from . $where_clause . "
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

    $records = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    $display_index = $start + 1;

    foreach ($records as $row) {
        $docId = (int)$row['id'];
        $rawCompany = !empty($row['customer_company']) ? $row['customer_company'] : ($row['firma'] ?: '-');
        $companyName = htmlspecialchars($rawCompany, ENT_QUOTES, 'UTF-8');
        $customerId = (int)($row['customer_id'] ?? 0);

        $evrakTuru = htmlspecialchars($row['evrakturu'] ?: 'Gelen', ENT_QUOTES, 'UTF-8');
        $kategori = htmlspecialchars($row['kategori'] ?: '-', ENT_QUOTES, 'UTF-8');
        $adet = htmlspecialchars($row['adet'] ?: '1', ENT_QUOTES, 'UTF-8');
        $teslimEden = htmlspecialchars($row['teslim_eden_username'] ?: '-', ENT_QUOTES, 'UTF-8');
        $teslimAlan = htmlspecialchars($row['teslim_alan_username'] ?: '-', ENT_QUOTES, 'UTF-8');
        $teslimTarihi = htmlspecialchars($row['teslimtarihi'] ?: '-', ENT_QUOTES, 'UTF-8');
        $estatu = $row['estatu'] ?: 'Bekliyor';
        $aciklamaRaw = $row['aciklama'] ?: '';
        $aciklamaSafe = htmlspecialchars($aciklamaRaw, ENT_QUOTES, 'UTF-8');
        $aciklamaShort = htmlspecialchars(shorted($aciklamaRaw, 30), ENT_QUOTES, 'UTF-8');

        // 0: Row Index / Sıra No Badge
        $col0 = '<span class="row-index-badge">' . $display_index . '</span>';

        // 1: Firma Adı
        if ($customerId > 0) {
            $encCustomerId = Security::encrypt((string)$customerId);
            $col1 = '<a href="firma-duzenle?id=' . htmlspecialchars($encCustomerId, ENT_QUOTES, 'UTF-8') . '" class="font-weight-600 text-dark" title="' . $companyName . '">' . $companyName . '</a>';
        } else {
            $col1 = '<span class="font-weight-600 text-dark">' . $companyName . '</span>';
        }

        // 2: Evrak Türü Badge
        $col2 = '<span class="badge-doc-type">' . $evrakTuru . '</span>';

        // 3: Kategori Badge
        $col3 = '<span class="badge-category">' . $kategori . '</span>';

        // 4: Adet
        $col4 = '<span class="font-weight-600">' . $adet . '</span>';

        // 5: Teslim Eden
        $col5 = '<span class="font-12 text-muted"><i class="fa fa-user-o mr-1"></i>' . $teslimEden . '</span>';

        // 6: Teslim Alan
        $col6 = '<span class="font-12 text-muted"><i class="fa fa-user-check mr-1"></i>' . $teslimAlan . '</span>';

        // 7: Teslim Tarihi
        $col7 = '<span class="text-nowrap font-12 text-muted"><i class="fa fa-calendar-o mr-1"></i>' . $teslimTarihi . '</span>';

        // 8: Evrak Durumu
        if ($estatu === 'Bekliyor') {
            $col8 = '<span class="badge-stat-soft-warning"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>';
        } elseif ($estatu === 'Çalışıyor') {
            $col8 = '<span class="badge-stat-soft-primary"><i class="fa fa-spinner fa-spin mr-1"></i>Çalışıyor</span>';
        } elseif ($estatu === 'Tamamlandı') {
            $col8 = '<span class="badge-stat-soft-success"><i class="fa fa-check mr-1"></i>Tamamlandı</span>';
        } else {
            $col8 = '<span class="badge badge-secondary">' . htmlspecialchars($estatu, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        // 9: Açıklama
        $col9 = '<span class="font-12 text-muted" title="' . $aciklamaSafe . '">' . $aciklamaShort . '</span>';

        // 10: İşlem Butonları
        $escapedCompanyName = addslashes($rawCompany);
        $col10 = '<div class="action-btn-group">
            <a class="btn btn-sm btn-outline-info action-btn" data-tooltip="Düzenle" href="index.php?p=indocument-edit&id=' . $docId . '">
                <i class="fa fa-pencil"></i>
            </a>
            <a href="#" class="btn btn-sm btn-outline-danger action-btn" data-tooltip="Sil" onClick="deleteRecord(\'' . $escapedCompanyName . ' firmasına ait evrak kaydını silmek istediğinize emin misiniz?\', \'' . $docId . '\', \'view-indocument\', \'evraktakip\'); return false;">
                <i class="fa fa-trash"></i>
            </a>
        </div>';

        $data[] = [
            0 => $col0,
            1 => $col1,
            2 => $col2,
            3 => $col3,
            4 => $col4,
            5 => $col5,
            6 => $col6,
            7 => $col7,
            8 => $col8,
            9 => $col9,
            10 => $col10,
            'DT_RowAttr' => [
                'data-doc-id' => (string)$docId,
                'data-company' => $rawCompany
            ]
        ];

        $display_index++;
    }

    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        ob_clean();
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $filtered_records,
        'data' => $data
    ]);
} catch (\Throwable $e) {
    error_log('Indocuments DataTables Error: ' . $e->getMessage());
    if (function_exists('ob_get_level') && ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Veriler yüklenirken bir hata oluştu: ' . $e->getMessage()
    ]);
}
