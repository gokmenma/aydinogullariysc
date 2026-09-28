<?php
// API endpoint for DataTables server-side processing for purchases
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\DataTableFilter;

// Session & Auth Check
if (empty($_SESSION['login'])) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    http_response_code(403);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? ($_GET['draw'] ?? 0)),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Forbidden'
    ]);
    exit;
}

if (!isset($_GET['draw']) && !isset($_POST['draw'])) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$params_source = !empty($_POST['draw']) ? $_POST : $_GET;

$draw = intval($params_source['draw'] ?? 0);
$start = intval($params_source['start'] ?? 0);
$length = intval($params_source['length'] ?? 25);
if ($length === -1) {
    $length = 1000000;
} else if ($length <= 0) {
    $length = 25;
}
$search_value = trim($params_source['search']['value'] ?? '');
$order_column = intval($params_source['order'][0]['column'] ?? 3);
$order_dir = strtolower($params_source['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$requested_columns = $params_source['columns'] ?? [];
$only_pending = !empty($params_source['only_pending']) && $params_source['only_pending'] == '1';

// Sütun sıralama haritası
$columns = [
    0 => 'p.id',
    1 => 'p.siparisNo',
    2 => 'c.company',
    3 => 'p.create_time',
    4 => 'p.deadline',
    5 => 'CAST(REPLACE(REPLACE(p.altToplam, \'.\', \'\'), \',\', \'.\') AS DECIMAL(15,2))',
    6 => 'p.state',
    7 => 'p.payment_period',
    8 => 'p.invoice_number',
    9 => 'p.invoice_date',
    10 => 'u_create.username',
    11 => 'p.type',
    12 => 'p.id'
];

$order_by = $columns[$order_column] ?? 'p.create_time';

// Temel JOIN sorgusu
$base_from = "
    FROM purchases p
    LEFT JOIN customers c ON p.companyID = c.id
    LEFT JOIN users u_create ON p.creator = u_create.id
    LEFT JOIN users u_update ON p.updater = u_update.id
";

// Toplam kayıt sayısı
$count_query = "SELECT COUNT(*) as total FROM purchases p";
$count_stmt = $ac->query($count_query);
$total_records = $count_stmt ? (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;

$where_conditions = [];
$params = [];

if ($only_pending) {
    $where_conditions[] = "p.state = 0";
}

// Global Arama
if ($search_value !== '') {
    $where_conditions[] = "(
        p.siparisNo LIKE :gsearch_0 OR
        c.company LIKE :gsearch_1 OR
        p.invoice_number LIKE :gsearch_2 OR
        p.payment_period LIKE :gsearch_3 OR
        u_create.username LIKE :gsearch_4 OR
        p.altToplam LIKE :gsearch_5 OR
        DATE_FORMAT(p.create_time, '%d.%m.%Y') LIKE :gsearch_6
    )";
    $params[':gsearch_0'] = "%{$search_value}%";
    $params[':gsearch_1'] = "%{$search_value}%";
    $params[':gsearch_2'] = "%{$search_value}%";
    $params[':gsearch_3'] = "%{$search_value}%";
    $params[':gsearch_4'] = "%{$search_value}%";
    $params[':gsearch_5'] = "%{$search_value}%";
    $params[':gsearch_6'] = "%{$search_value}%";
}

// Sütun Bazlı Filtreler
$column_configs = [
    1 => ['expr' => 'p.siparisNo', 'type' => 'text'],
    2 => ['expr' => 'c.company', 'type' => 'text'],
    3 => ['expr' => 'p.create_time', 'type' => 'datetime'],
    4 => ['expr' => 'p.deadline', 'type' => 'date'],
    5 => ['expr' => 'CAST(REPLACE(REPLACE(p.altToplam, \'.\', \'\'), \',\', \'.\') AS DECIMAL(15,2))', 'type' => 'number'],
    6 => ['expr' => 'p.state', 'type' => 'number'],
    7 => ['expr' => 'p.payment_period', 'type' => 'text'],
    8 => ['expr' => 'p.invoice_number', 'type' => 'text'],
    9 => ['expr' => 'p.invoice_date', 'type' => 'date'],
    10 => ['expr' => 'u_create.username', 'type' => 'text'],
    11 => ['expr' => 'p.type', 'type' => 'number'],
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

$where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

// Filtrelenmiş kayıt sayısı
$filtered_count_query = "SELECT COUNT(*) as total " . $base_from . $where_clause;
$filtered_stmt = $ac->prepare($filtered_count_query);
$filtered_stmt->execute($params);
$records_filtered = $filtered_stmt ? (int)$filtered_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;

// Veri Sorgusu
$data_query = "
    SELECT 
        p.*,
        c.company AS customer_name,
        u_create.username AS creator_username,
        u_create.Unvan AS creator_title,
        u_update.username AS updater_username,
        u_update.Unvan AS updater_title
    " . $base_from . $where_clause . "
    ORDER BY {$order_by} {$order_dir}, p.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $ac->prepare($data_query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $length, PDO::PARAM_INT);
$stmt->bindValue(':offset', $start, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$canDelete = permtrue("purchasedelete");
$data = [];
$sira = $start + 1;

foreach ($results as $purc) {
    $pid = (int)$purc['id'];
    $companyName = !empty($purc['customer_name']) ? $purc['customer_name'] : '-';
    $siparisNo = htmlspecialchars($purc['siparisNo'] ?? '', ENT_QUOTES, 'UTF-8');
    $rawCreateTime = $purc['create_time'] ?? '';
    $createTimeFormatted = !empty($rawCreateTime) ? date('d.m.Y', strtotime($rawCreateTime)) : '-';
    $rawDeadline = $purc['deadline'] ?? '';
    $deadlineFormatted = !empty($rawDeadline) ? date('d.m.Y', strtotime($rawDeadline)) : '-';
    $altToplam = htmlspecialchars($purc['altToplam'] ?? '0,00', ENT_QUOTES, 'UTF-8');
    $state = (int)($purc['state'] ?? 0);
    $type = (int)($purc['type'] ?? 0);
    $paymentPeriod = htmlspecialchars($purc['payment_period'] ?? '', ENT_QUOTES, 'UTF-8');
    $invoiceNumber = htmlspecialchars($purc['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $rawInvoiceDate = $purc['invoice_date'] ?? '';
    $invoiceDateFormatted = !empty($rawInvoiceDate) ? date('d.m.Y', strtotime($rawInvoiceDate)) : '-';
    
    $creator = !empty($purc['creator_username']) ? $purc['creator_username'] : 'Sistem';
    $updater = !empty($purc['updater_username']) ? $purc['updater_username'] : '';
    $updatedDate = $purc['updated_at'] ?? '';

    // Tip Rozeti & Linkler
    if ($type == 1) {
        $typeLabel = 'TALEP';
        $typeBadge = '<span class="badge-type-demand">TALEP</span>';
        $editLink = 'index.php?p=purchase-demand-edit&id=' . $pid;
        $detailLink = 'index.php?p=purchase-demand-detail&id=' . $pid;
    } else if ($type == 2) {
        $typeLabel = 'FİYAT TALEBİ';
        $typeBadge = '<span class="badge-type-price">FİYAT</span>';
        $editLink = 'index.php?p=purchases/manage&id=' . $pid;
        $detailLink = 'index.php?p=purchase-detail&id=' . $pid;
    } else {
        $typeLabel = 'SİPARİŞ';
        $typeBadge = '<span class="badge-type-order">SİPARİŞ</span>';
        $editLink = 'index.php?p=purchases/manage&id=' . $pid;
        $detailLink = 'index.php?p=purchase-detail&id=' . $pid;
    }

    // Durum Rozeti
    if ($state == 0) {
        $statusBadge = '<span class="badge badge-soft-warning font-weight-bold">Bekliyor</span>';
    } elseif ($state == 1) {
        $statusBadge = '<span class="badge badge-soft-info font-weight-bold">Onaylandı</span>';
    } elseif ($state == 2) {
        $statusBadge = '<span class="badge badge-soft-success font-weight-bold">Tamamlandı</span>';
    } elseif ($state == 3) {
        $statusBadge = '<span class="badge badge-soft-danger font-weight-bold">Reddedildi</span>';
    } else {
        $statusBadge = Helper::getStateBadge($state);
    }

    $creatorTooltip = "Oluşturulma: " . $rawCreateTime;
    if (!empty($updater)) {
        $creatorTooltip .= "\nGüncelleyen: " . $updater . " (" . $updatedDate . ")";
    }

    // İşlem Butonları ve Açılır Menü
    $actions = '<div class="action-btn-group">';
    $actions .= '<a href="' . $detailLink . '" target="_blank" class="btn btn-sm btn-outline-primary action-btn" data-toggle="tooltip" data-placement="top" title="Detay / Form Görüntüle"><i class="fa fa-eye"></i></a>';
    $actions .= '<a href="' . $editLink . '" class="btn btn-sm btn-outline-info action-btn" data-toggle="tooltip" data-placement="top" title="Düzenle"><i class="fa fa-pencil"></i></a>';

    if ($canDelete) {
        if ($state == 2) {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger action-btn disabled opacity-50" data-toggle="tooltip" data-placement="top" title="Tamamlanmış Kayıt Silinemez" disabled><i class="fa fa-trash"></i></button>';
        } else {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger action-btn" data-toggle="tooltip" data-placement="top" title="Sil" onclick="deleteRecord(\'' . $siparisNo . ' nolu kaydı silmek istediğinize emin misiniz?\', ' . $pid . ', \'purchases\', null, \'/satin-almalar\')"><i class="fa fa-trash"></i></button>';
        }
    }

    $actions .= '<div class="dropdown d-inline">
        <button class="btn btn-sm btn-outline-secondary action-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Diğer İşlemler">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right shadow border-0" style="border-radius: 8px; z-index: 1050;">';

    if ($type == 1 && $state == 0) {
        $actions .= '<a href="index.php?p=purchases/manage&talep_id=' . $pid . '&demand=true" class="dropdown-item py-2 font-13"><i class="fa fa-shopping-cart text-primary mr-2"></i> Sipariş Oluştur</a>';
    }

    $actions .= '<a href="index.php?p=purchase-demand-detail&id=' . $pid . '" target="_blank" class="dropdown-item py-2 font-13"><i class="fa fa-file-text-o text-info mr-2"></i> Talep Formunu Göster</a>';
    $actions .= '<a href="index.php?p=purchase-detail&id=' . $pid . '" target="_blank" class="dropdown-item py-2 font-13"><i class="fa fa-file-text text-success mr-2"></i> Sipariş Formunu Göster</a>';

    if ($type == 1) {
        $toggleStateText = ($state == 0) ? 'Tamamlandı Olarak İşaretle' : 'Bekliyor Olarak İşaretle';
        $actions .= '<a href="#" class="dropdown-item py-2 font-13 done-demand" data-id="' . $pid . '"><i class="fa fa-check-square-o text-warning mr-2"></i> ' . $toggleStateText . '</a>';
    }

    $actions .= '<a href="index.php?p=report-send-as-mail&type=purchase&id=' . $pid . '" class="dropdown-item py-2 font-13"><i class="fa fa-envelope text-secondary mr-2"></i> Mail Gönder</a>';
    $actions .= '</div></div></div>';

    $row = [
        'DT_RowAttr' => [
            'data-id' => $pid,
            'data-siparis-no' => $siparisNo,
            'data-type' => $type,
            'data-type-label' => $typeLabel,
            'data-state' => $state,
            'data-company' => $companyName,
            'data-edit-link' => $editLink
        ],
        0 => '<span class="row-index-badge">' . $sira++ . '</span>',
        1 => '<span class="font-weight-bold text-dark">' . $siparisNo . '</span>',
        2 => '<span class="font-weight-600 text-dark" data-toggle="tooltip" data-placement="top" title="' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(shorted($companyName, 20), ENT_QUOTES, 'UTF-8') . '</span>',
        3 => '<span class="text-muted text-center" data-toggle="tooltip" data-placement="top" title="' . htmlspecialchars($rawCreateTime, ENT_QUOTES, 'UTF-8') . '">' . $createTimeFormatted . '</span>',
        4 => '<span class="text-center">' . $deadlineFormatted . '</span>',
        5 => '<span class="text-right font-weight-bold text-dark">' . $altToplam . ' ₺</span>',
        6 => $statusBadge,
        7 => $paymentPeriod ?: '-',
        8 => $invoiceNumber ?: '-',
        9 => $invoiceDateFormatted,
        10 => '<span data-toggle="tooltip" data-placement="top" title="' . htmlspecialchars($creatorTooltip, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-user-circle text-muted mr-1"></i>' . htmlspecialchars(shorted($creator, 12), ENT_QUOTES, 'UTF-8') . '</span>',
        11 => $typeBadge,
        12 => $actions
    ];

    $data[] = $row;
}

if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $records_filtered,
    'data' => $data
]);
exit;
