<?php
// API endpoint for Excel Export of incoming documents (Gelen Evraklar)
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';
global $ac;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Helper\DataTableFilter;

if (empty($_SESSION['login'])) {
    http_response_code(403);
    exit('Bu işlem için yetkiniz bulunmuyor.');
}

$search_value = trim($_GET['search']['value'] ?? '');
$requested_columns = $_GET['columns'] ?? [];
$order_column = intval($_GET['order'][0]['column'] ?? 0);
$order_dir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

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

$base_from = "
    FROM evraktakip e
    LEFT JOIN customers c ON c.id = e.firma
    LEFT JOIN users u_teslimalan ON e.teslimalan = u_teslimalan.id
    LEFT JOIN users u_teslimeden ON e.teslimeden = u_teslimeden.id
";

$where_conditions = ["e.evrakturu = 'Gelen'"];
$params = [];

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

$data_query = "
    SELECT 
        e.id,
        COALESCE(c.company, e.firma) AS company_name,
        e.evrakturu,
        e.kategori,
        e.adet,
        u_teslimeden.username AS teslim_eden,
        u_teslimalan.username AS teslim_alan,
        e.teslimtarihi,
        e.estatu,
        e.aciklama
    " . $base_from . $where_clause . "
    ORDER BY {$order_by} {$order_dir}
";

$statement = $ac->prepare($data_query);
foreach ($params as $key => $value) {
    $statement->bindValue($key, $value);
}
$statement->execute();
$documents = $statement->fetchAll(PDO::FETCH_ASSOC);

if (function_exists('audit_log')) {
    audit_log(
        'export',
        'evraktakip',
        'Gelen evrak listesi Excel olarak dışa aktarıldı',
        'indocument_list',
        null,
        ['record_count' => count($documents), 'search' => $search_value]
    );
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Gelen Evraklar');

$headers = [
    'Evrak No', 'Firma Adı', 'Evrak Türü', 'Kategori', 'Adet',
    'Teslim Eden', 'Teslim Alan', 'Teslim Tarihi', 'Durum', 'Açıklama'
];
$fields = [
    'id', 'company_name', 'evrakturu', 'kategori', 'adet',
    'teslim_eden', 'teslim_alan', 'teslimtarihi', 'estatu', 'aciklama'
];

foreach ($headers as $index => $header) {
    $column = Coordinate::stringFromColumnIndex($index + 1);
    $sheet->setCellValue($column . '1', $header);
    $sheet->getStyle($column . '1')->getFont()->setBold(true);
}

$rowNumber = 2;
foreach ($documents as $doc) {
    foreach ($fields as $index => $field) {
        $column = Coordinate::stringFromColumnIndex($index + 1);
        $val = $doc[$field] ?? '';
        $sheet->setCellValue($column . $rowNumber, $val);
    }
    $rowNumber++;
}

foreach (range(1, count($headers)) as $index) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
}
$sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1');
$sheet->freezePane('A2');

$filename = 'gelen_evraklar_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
