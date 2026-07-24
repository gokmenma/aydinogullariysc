<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

if (!permtrue("serviceView") || !permtrue("data_export_service")) {
    http_response_code(403);
    exit;
}

// Excel export logla
audit_log("export", "services", "Servis listesi Excel olarak dışa aktarıldı", "service_list", null, [
    'search' => $_GET['search']['value'] ?? '',
    'cid' => $_GET['cid'] ?? null,
    'sid' => $_GET['sid'] ?? null
]);

use App\Helper\Helper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$cid = isset($_GET['cid']) ? $_GET['cid'] : null;
$sid = isset($_GET['sid']) ? $_GET['sid'] : null;

$search_value = $_GET['search']['value'] ?? '';
$order_column = intval($_GET['order'][0]['column'] ?? 0);
$order_dir = $_GET['order'][0]['dir'] ?? 'desc';
$requested_columns = $_GET['columns'] ?? [];

$columns = ['p.id', 'p.service_number', 'c.company', 'r.title', 's.title', 'p.pregdate', 'p.pstart_date', 'p.contract_statu', 'p.pstatu', 'u.username', 'uu.username', 'ar.action_at'];
$order_by = $columns[$order_column] ?? 'p.id';

$filter_columns = [
    1 => 'p.service_number',
    2 => 'c.company',
    3 => 'r.title',
    4 => 's.title',
    5 => 'p.pregdate',
    6 => 'p.pstart_date',
    8 => 'st.title',
    9 => 'u.username',
    10 => 'uu.username',
];

try {
    $ac->exec("CREATE TABLE IF NOT EXISTS service_accounting_receipt_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id INT UNSIGNED NOT NULL,
        action VARCHAR(20) NOT NULL,
        action_by INT UNSIGNED NOT NULL,
        action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_service_id (service_id),
        KEY idx_action_at (action_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Export akışı tablo yoksa da devam etsin.
}

$base_query = "
    FROM projects p
    LEFT JOIN customers c ON c.id = p.pcid
    LEFT JOIN units r ON r.id = p.region
    LEFT JOIN units s ON s.id = p.servicestype
    LEFT JOIN users u ON u.id = p.pcreativer
    LEFT JOIN users uu ON uu.id = p.updater
    LEFT JOIN units st ON st.id = p.pstatu
    LEFT JOIN (
        SELECT l.service_id, l.action, l.action_by, l.action_at
        FROM service_accounting_receipt_logs l
        INNER JOIN (
            SELECT service_id, MAX(id) as max_id
            FROM service_accounting_receipt_logs
            GROUP BY service_id
        ) lm ON lm.max_id = l.id
    ) ar ON ar.service_id = p.id
";

$where_conditions = [];
$params = [];

if ($cid) {
    $where_conditions[] = "p.pcid = :cid";
    $params[':cid'] = $cid;
}
if ($sid) {
    $where_conditions[] = "p.id = :sid";
    $params[':sid'] = $sid;
}

if ($search_value !== '') {
    $where_conditions[] = "(p.service_number LIKE :search OR c.company LIKE :search OR r.title LIKE :search OR s.title LIKE :search OR u.username LIKE :search OR uu.username LIKE :search OR (CASE WHEN ar.action = 'received' THEN 'Teslim Alındı' ELSE 'Teslim Bekliyor' END) LIKE :search OR p.pregdate LIKE :search)";
    $params[':search'] = "%{$search_value}%";
}
if (!empty($requested_columns) && is_array($requested_columns)) {
    foreach ($requested_columns as $idx => $col) {
        $value = $col['search']['value'] ?? '';
        $idx = intval($idx);
        if ($value === '') {
            continue;
        }

        if ($idx === 7 && defined('SOZLESMEDURUMU')) {
            $matchIds = [];
            foreach (SOZLESMEDURUMU as $k => $v) {
                if ($k === '') {
                    continue;
                }
                if (stripos($v, $value) !== false) {
                    $matchIds[] = $k;
                }
            }

            if (count($matchIds)) {
                $inKeys = [];
                foreach ($matchIds as $i => $mid) {
                    $pkey = ":cs_{$i}";
                    $inKeys[] = $pkey;
                    $params[$pkey] = $mid;
                }
                $where_conditions[] = "p.contract_statu IN (" . implode(',', $inKeys) . ")";
            } else {
                $where_conditions[] = "p.contract_statu = -1";
            }
        } else if ($idx === 11) {
            $paramKey = ":col_{$idx}";
            $where_conditions[] = "(CASE WHEN ar.action = 'received' THEN 'Teslim Alındı' ELSE 'Teslim Bekliyor' END) LIKE " . $paramKey;
            $params[$paramKey] = "%{$value}%";
        } else if (isset($filter_columns[$idx])) {
            $paramKey = ":col_{$idx}";
            $where_conditions[] = $filter_columns[$idx] . " LIKE " . $paramKey;
            $params[$paramKey] = "%{$value}%";
        }
    }
}

$where_clause = count($where_conditions) ? (" WHERE " . implode(" AND ", $where_conditions)) : "";

$data_query = "
    SELECT 
        p.id,
        p.service_number,
        c.company as company_name,
        r.title as region_name,
        s.title as service_title,
        p.pregdate,
        p.pstart_date,
        p.contract_statu,
        st.title as status_title,
        u.username as creator_username,
        uu.username as updater_username,
        ar.action as accounting_action
" . $base_query . $where_clause . "
    ORDER BY {$order_by} {$order_dir}
";

$stmt = $ac->prepare($data_query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ['Sıra No','Servis No','Firma Adı','Bölge','Servis Konusu','İş Emri Oluşturma Tarihi','Servis Planlama Tarihi','Sözleşme Durum','Durum','İş Emrini Oluşturan','Son İşlem Yapan','Muhasebe Teslim Durumu'];
foreach ($headers as $idx => $title) {
    $col = Coordinate::stringFromColumnIndex($idx + 1);
    $sheet->setCellValue($col . '1', $title);
}

$rowIndex = 2;
$i = 1;
foreach ($rows as $r) {
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . $rowIndex, $i);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(2) . $rowIndex, $r['service_number'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(3) . $rowIndex, $r['company_name'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(4) . $rowIndex, $r['region_name'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(5) . $rowIndex, $r['service_title'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(6) . $rowIndex, $r['pregdate'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(7) . $rowIndex, $r['pstart_date'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(8) . $rowIndex, (defined('SOZLESMEDURUMU') ? (SOZLESMEDURUMU[$r['contract_statu']] ?? '') : ''));
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(9) . $rowIndex, $r['status_title'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(10) . $rowIndex, $r['creator_username'] ?? '');
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(11) . $rowIndex, $r['updater_username'] ?: ($r['creator_username'] ?? ''));
    $sheet->setCellValue(Coordinate::stringFromColumnIndex(12) . $rowIndex, (($r['accounting_action'] ?? '') === 'received') ? 'Teslim Alındı' : 'Teslim Bekliyor');
    $rowIndex++;
    $i++;
}

$filename = 'services_export_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename=' . $filename);
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
