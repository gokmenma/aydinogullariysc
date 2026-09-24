<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Helper\MaintenanceMode;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$status = MaintenanceMode::getStatus($ac);
$status['has_access'] = MaintenanceMode::hasAccessPermission($ac);

echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
