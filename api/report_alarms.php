<?php
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use App\Model\ReportAlarmModel;

function reportAlarmResponse(int $statusCode, array $payload): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$userId = (int) sesset('id');
if ($userId < 1 || !permtrue('reportview') || !permtrue('report_alarm')) {
    reportAlarmResponse(403, ['status' => 'error', 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    unset($_SESSION['report_alarm_banner_cache']);
}
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');

if ($method === 'POST') {
    $receivedToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($receivedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $receivedToken)) {
        reportAlarmResponse(419, ['status' => 'error', 'message' => 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyiniz.']);
    }
}

try {
    $model = new ReportAlarmModel($ac);

    if ($method === 'GET' && $action === 'list') {
        $alarms = $model->listForUser($userId);
        foreach ($alarms as &$alarm) {
            $alarm['counts'] = $model->counts($alarm);
        }
        unset($alarm);
        reportAlarmResponse(200, ['status' => 'success', 'alarms' => $alarms]);
    }

    if ($method === 'GET' && $action === 'preview') {
        $counts = $model->counts([
            'days_before' => (int) ($_GET['days_before'] ?? 30),
            'include_overdue' => !empty($_GET['include_overdue']),
            'latest_only' => !empty($_GET['latest_only']),
            'customer_ids' => (array) ($_GET['customer_ids'] ?? []),
            'type_ids' => (array) ($_GET['type_ids'] ?? []),
        ]);
        reportAlarmResponse(200, ['status' => 'success', 'counts' => $counts]);
    }

    if ($method === 'GET' && $action === 'options') {
        reportAlarmResponse(200, ['status' => 'success'] + $model->options());
    }

    if ($method === 'POST' && $action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            reportAlarmResponse(422, ['status' => 'error', 'message' => 'Alarm adı zorunludur.']);
        }
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'id' => $id,
            'name' => $name,
            'days_before' => (int) ($_POST['days_before'] ?? 30),
            'include_overdue' => !empty($_POST['include_overdue']),
            'latest_only' => !empty($_POST['latest_only']),
            'customer_ids' => (array) ($_POST['customer_ids'] ?? []),
            'type_ids' => (array) ($_POST['type_ids'] ?? []),
        ];
        $savedId = $model->save($userId, $data);
        audit_log($id > 0 ? 'update' : 'create', 'report_alarms', 'Rapor hatırlatma alarmı kaydedildi: ' . $name, 'report_alarm', $savedId, [
            'days_before' => $data['days_before'],
            'customer_count' => count($data['customer_ids']),
            'type_count' => count($data['type_ids']),
        ]);
        reportAlarmResponse(200, ['status' => 'success', 'id' => $savedId, 'message' => 'Alarm kaydedildi.']);
    }

    if ($method === 'POST' && $action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $active = !empty($_POST['active']);
        if (!$model->setActive($id, $userId, $active)) {
            reportAlarmResponse(404, ['status' => 'error', 'message' => 'Alarm bulunamadı.']);
        }
        audit_log('status_change', 'report_alarms', 'Rapor hatırlatma alarmı ' . ($active ? 'açıldı' : 'kapatıldı'), 'report_alarm', $id);
        reportAlarmResponse(200, ['status' => 'success']);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$model->softDelete($id, $userId)) {
            reportAlarmResponse(404, ['status' => 'error', 'message' => 'Alarm bulunamadı.']);
        }
        audit_log('delete', 'report_alarms', 'Rapor hatırlatma alarmı silindi', 'report_alarm', $id);
        reportAlarmResponse(200, ['status' => 'success']);
    }

    reportAlarmResponse(400, ['status' => 'error', 'message' => 'Geçersiz istek.']);
} catch (\Throwable $e) {
    error_log('report_alarms API hatası: ' . $e->getMessage());
    reportAlarmResponse(500, ['status' => 'error', 'message' => 'İşlem sırasında bir hata oluştu.']);
}
