<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use App\Helper\Security;
use App\Model\PermissionModel;

// 1. Yetki Kontrolü
if (!permtrue("authdefine") && !permtrue("authEdit")) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Yetki düzenleme izniniz bulunmuyor.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$permModel = new PermissionModel();

$action = $_POST['action'] ?? 'update';
$rawId = $_POST['role_id'] ?? ($_GET['id'] ?? 0);
$roleId = is_numeric($rawId) ? (int)$rawId : (int)Security::decrypt($rawId);

$authName = trim($_POST['authName'] ?? '');
$authDescription = trim($_POST['authDescription'] ?? '');

$authIds = [];
if (!empty($_POST["auth"]) && is_array($_POST["auth"])) {
    $authIds = $_POST["auth"];
} elseif (!empty($_POST["checkedDataIds"]) && is_array($_POST["checkedDataIds"])) {
    $authIds = $_POST["checkedDataIds"];
}

if ($action === 'create' || $roleId <= 0) {
    $result = $permModel->createRole($authName, $authDescription, $authIds);
} else {
    $result = $permModel->updateRole($roleId, $authName, $authDescription, $authIds);
}

// Clear any buffers (warnings, notice logs etc.) before returning JSON
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'     => $result['success'] ? 'success' : 'error',
    'message'    => $result['message'],
    'auth_count' => $result['auth_count'] ?? 0,
    'role_id'    => $result['role_id'] ?? $roleId
], JSON_UNESCAPED_UNICODE);
exit;
