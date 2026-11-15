<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Helper\DataTableFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!permtrue('customerexport')) {
    http_response_code(403);
    exit('Bu işlem için yetkiniz bulunmuyor.');
}

$searchValue = trim($_GET['search']['value'] ?? '');
$requestedColumns = $_GET['columns'] ?? [];
$orderColumn = intval($_GET['order'][0]['column'] ?? 0);
$orderDirection = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$orderColumns = ['c.id', 'c.company', 'cg.title', 'c.represant', 'c.id', 'c.email', 'c.gsm', 'c.regdate'];
$orderBy = $orderColumns[$orderColumn] ?? 'c.id';
$columnConfigs = [
    1 => ['expr' => 'c.company', 'type' => 'text'],
    2 => ['expr' => 'cg.title', 'type' => 'text'],
    3 => ['expr' => 'c.represant', 'type' => 'text'],
    5 => ['expr' => 'c.email', 'type' => 'text'],
    6 => ['expr' => 'c.gsm', 'type' => 'text'],
    7 => ['expr' => 'c.regdate', 'type' => 'date'],
];

$conditions = ['c.deleted_at IS NULL'];
$params = [];

if ($searchValue !== '') {
    $conditions[] = '(
        c.id LIKE :search_0 OR c.company LIKE :search_1 OR cg.title LIKE :search_2 OR
        c.represant LIKE :search_3 OR c.email LIKE :search_4 OR c.gsm LIKE :search_5 OR
        DATE_FORMAT(c.regdate, \'%d.%m.%Y\') LIKE :search_6 OR c.regdate LIKE :search_7
    )';
    $params[':search_0'] = '%' . $searchValue . '%';
    $params[':search_1'] = '%' . $searchValue . '%';
    $params[':search_2'] = '%' . $searchValue . '%';
    $params[':search_3'] = '%' . $searchValue . '%';
    $params[':search_4'] = '%' . $searchValue . '%';
    $params[':search_5'] = '%' . $searchValue . '%';
    $params[':search_6'] = '%' . $searchValue . '%';
    $params[':search_7'] = '%' . $searchValue . '%';
}

if (is_array($requestedColumns)) {
    foreach ($requestedColumns as $index => $column) {
        $rawSearch = $column['search']['value'] ?? '';
        $index = (int) $index;
        $filter = DataTableFilter::parse($rawSearch);
        if (!$filter) {
            continue;
        }
        if (isset($columnConfigs[$index])) {
            $cfg = $columnConfigs[$index];
            $prefix = "col_{$index}_";
            $cond = DataTableFilter::buildCondition($cfg['expr'], $filter, $params, $prefix, $cfg['type'] ?? 'text');
            if (!empty($cond)) {
                $conditions[] = $cond;
            }
        }
    }
}

$sql = '
    SELECT c.id, c.company, cg.title AS group_title, c.represant,
           COALESCE(o_cnt.cnt, 0) AS offer_count,
           COALESCE(p_cnt.cnt, 0) AS service_count,
           COALESCE(r_cnt.cnt, 0) AS report_count,
           c.email, c.gsm, c.regdate, c.yetkili, c.city, c.ilce, c.region, c.address
    FROM customers c
    LEFT JOIN cgroups cg ON cg.id = c.grp
    LEFT JOIN (SELECT cid, COUNT(*) AS cnt FROM offers GROUP BY cid) o_cnt ON o_cnt.cid = c.id
    LEFT JOIN (SELECT pcid, COUNT(*) AS cnt FROM projects WHERE deleted_at IS NULL GROUP BY pcid) p_cnt ON p_cnt.pcid = c.id
    LEFT JOIN (SELECT customer_id, COUNT(*) AS cnt FROM reports GROUP BY customer_id) r_cnt ON r_cnt.customer_id = c.id
    WHERE ' . implode(' AND ', $conditions) . "
    ORDER BY {$orderBy} {$orderDirection}";

$statement = $ac->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue($key, $value);
}
$statement->execute();
$customers = $statement->fetchAll(PDO::FETCH_ASSOC);
audit_log(
    'export',
    'customers',
    'Firma listesi Excel olarak dışa aktarıldı',
    'customer_list',
    null,
    ['record_count' => count($customers), 'search' => $searchValue]
);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Firmalar');

$headers = [
    'Firma No', 'Firma Adı', 'Grup', 'Satış Temsilcisi', 'Teklif Sayısı',
    'Servis Sayısı', 'Rapor Sayısı', 'E-Posta', 'GSM', 'Kayıt Tarihi', 'Yetkili', 'İl', 'İlçe', 'Bölge', 'Adres'
];
$fields = [
    'id', 'company', 'group_title', 'represant', 'offer_count',
    'service_count', 'report_count', 'email', 'gsm', 'regdate', 'yetkili', 'city', 'ilce', 'region', 'address'
];

$lastColumn = Coordinate::stringFromColumnIndex(count($headers));
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);

$rowsData = [];
foreach ($customers as $customer) {
    $row = [];
    foreach ($fields as $field) {
        $val = $customer[$field] ?? '';
        if ($field === 'regdate' && !empty($val) && $val !== '0000-00-00 00:00:00') {
            $val = date('d.m.Y', strtotime($val));
        }
        $row[] = $val;
    }
    $rowsData[] = $row;
}
if (!empty($rowsData)) {
    $sheet->fromArray($rowsData, null, 'A2', true);
}

foreach (range(1, count($headers)) as $index) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
}
$sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1');
$sheet->freezePane('A2');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'firmalar_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
