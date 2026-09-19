<?php
require_once dirname(__DIR__) . '/bootstrap.php';

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
$filterColumns = [
    1 => 'c.company',
    2 => 'cg.title',
    3 => 'c.represant',
    5 => 'c.email',
    6 => 'c.gsm',
    7 => 'c.regdate',
];

$conditions = ['c.deleted_at IS NULL'];
$params = [];

if ($searchValue !== '') {
    $conditions[] = '(
        c.id LIKE :search OR c.company LIKE :search OR cg.title LIKE :search OR
        c.represant LIKE :search OR c.email LIKE :search OR c.gsm LIKE :search OR
        DATE_FORMAT(c.regdate, \'%d.%m.%Y\') LIKE :search OR c.regdate LIKE :search
    )';
    $params[':search'] = '%' . $searchValue . '%';
}

if (is_array($requestedColumns)) {
    foreach ($requestedColumns as $index => $column) {
        $value = trim($column['search']['value'] ?? '');
        $index = (int) $index;
        if ($value !== '' && isset($filterColumns[$index])) {
            $key = ':col_' . $index;
            if ($index === 7) {
                $conditions[] = '(DATE_FORMAT(c.regdate, \'%d.%m.%Y\') LIKE ' . $key . ' OR c.regdate LIKE ' . $key . ')';
            } else {
                $conditions[] = $filterColumns[$index] . ' LIKE ' . $key;
            }
            $params[$key] = '%' . $value . '%';
        }
    }
}

$sql = '
    SELECT c.id, c.company, cg.title AS group_title, c.represant,
           (SELECT COUNT(*) FROM offers o WHERE o.cid = c.id) AS offer_count,
           (SELECT COUNT(*) FROM projects p WHERE p.pcid = c.id) AS service_count,
           c.email, c.gsm, c.regdate, c.yetkili, c.city, c.ilce, c.region, c.address
    FROM customers c
    LEFT JOIN cgroups cg ON cg.id = c.grp
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
    'Servis Sayısı', 'E-Posta', 'GSM', 'Kayıt Tarihi', 'Yetkili', 'İl', 'İlçe', 'Bölge', 'Adres'
];
$fields = [
    'id', 'company', 'group_title', 'represant', 'offer_count',
    'service_count', 'email', 'gsm', 'regdate', 'yetkili', 'city', 'ilce', 'region', 'address'
];

foreach ($headers as $index => $header) {
    $column = Coordinate::stringFromColumnIndex($index + 1);
    $sheet->setCellValue($column . '1', $header);
    $sheet->getStyle($column . '1')->getFont()->setBold(true);
}

$rowNumber = 2;
foreach ($customers as $customer) {
    foreach ($fields as $index => $field) {
        $column = Coordinate::stringFromColumnIndex($index + 1);
        $val = $customer[$field] ?? '';
        if ($field === 'regdate' && !empty($val) && $val !== '0000-00-00 00:00:00') {
            $val = date('d.m.Y', strtotime($val));
        }
        $sheet->setCellValue($column . $rowNumber, $val);
    }
    $rowNumber++;
}

foreach (range(1, count($headers)) as $index) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
}
$sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1');
$sheet->freezePane('A2');

$filename = 'firmalar_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
