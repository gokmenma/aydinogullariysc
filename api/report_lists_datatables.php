<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Model\ReportListDatatableModel;

$draw = (int) ($_GET['draw'] ?? 0);

function reportListJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('permtrue') || !permtrue('reportview')) {
    reportListJson(403, ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Yetkiniz bulunmuyor.']);
}

$type = (string) ($_GET['list'] ?? '');
if (!ReportListDatatableModel::isValidType($type)) {
    reportListJson(400, ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Geçersiz liste.']);
}

$month = (string) ($_GET['month'] ?? '');
$year = (string) ($_GET['year'] ?? '');
$dateFrom = (string) ($_GET['date_from'] ?? '');
$dateTo = (string) ($_GET['date_to'] ?? '');
$period = [
    'month' => preg_match('/^(0?[1-9]|1[0-2])$/', $month) ? $month : '',
    'year' => preg_match('/^\d{4}$/', $year) ? $year : '',
    'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '',
    'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '',
];

$columnSearch = [];
foreach ((array) ($_GET['columns'] ?? []) as $index => $column) {
    $value = $column['search']['value'] ?? '';
    if (is_string($value) && $value !== '') {
        $columnSearch[(int) $index] = $value;
    }
}

$request = [
    'start' => $_GET['start'] ?? 0,
    'length' => $_GET['length'] ?? 25,
    'search' => $_GET['search']['value'] ?? '',
    'order_column' => $_GET['order'][0]['column'] ?? null,
    'order_dir' => $_GET['order'][0]['dir'] ?? 'asc',
    'column_search' => $columnSearch,
];

function reportListEsc($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function reportListNumberCell(array $row): string
{
    $repId = (int) ($row['report_id'] ?? 0);
    $repNum = reportListEsc($row['report_number'] ?? '-');
    if ($repId > 0) {
        return '<a href="index.php?p=reports/ysc/report-view-ysc&id=' . $repId . '" target="_blank" class="text-primary font-weight-bold" title="Raporu Görüntüle">' . $repNum . '</a>';
    }
    return $repNum;
}

function reportListActionCell(array $row): string
{
    $repId = (int) ($row['report_id'] ?? 0);
    if ($repId > 0) {
        return '<a href="index.php?p=reports/ysc/report-view-ysc&id=' . $repId . '" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; padding: 3px 8px;" title="Rapor Detayını Görüntüle"><i class="fa fa-eye"></i></a>';
    }
    return '-';
}

function reportListValidity(array $row): string
{
    $sort = (string) ($row['validity_sort'] ?? '');
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sort, $m)) {
        return $m[3] . '.' . $m[2] . '.' . $m[1];
    }
    $raw = trim((string) ($row['validity_date'] ?? ''));
    return $raw !== '' ? $raw : '-';
}

try {
    $model = new ReportListDatatableModel();
    $result = $model->getPage($type, $period, $request, $draw <= 1);

    $data = [];
    $number = (int) $request['start'];
    foreach ($result['rows'] as $row) {
        $number++;
        $cells = [
            0 => $number,
            1 => reportListEsc($row['firma_adi'] ?? '-'),
            2 => reportListNumberCell($row),
        ];
        if ($type === 'control') {
            $cells[3] = reportListEsc(reportListValidity($row));
            $cells[4] = reportListEsc($row['ay'] ?? '-');
            $cells[5] = reportListEsc($row['yil'] ?? '-');
            $cells[6] = reportListActionCell($row);
        } else {
            $cells[3] = reportListEsc($row['cihaz_no'] ?? '-');
            $cells[4] = reportListEsc($row['bulundugu_bolge'] ?? '-');
            $cells[5] = reportListEsc($row['ay'] ?? '-');
            $cells[6] = reportListEsc($row['yil'] ?? '-');
            $cells[7] = reportListActionCell($row);
        }
        $data[] = $cells;
    }

    reportListJson(200, [
        'draw' => $draw,
        'recordsTotal' => $result['total'],
        'recordsFiltered' => $result['filtered'],
        'data' => $data,
        'columnCounts' => $result['counts'],
    ]);
} catch (\Throwable $e) {
    error_log('report_lists_datatables hata: ' . $e->getMessage());
    reportListJson(500, ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Liste yüklenemedi.']);
}
