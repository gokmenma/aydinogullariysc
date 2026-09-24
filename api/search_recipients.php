<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use App\Model\CustomerModel;

// Yetki Kontrolü
$userId = (int)($_SESSION['lid'] ?? 0);
$canSend = permtrue("mailandsmssend") || (isset($_SESSION['perm']) && $_SESSION['perm'] == 1) || in_array($userId, [1, 12]);

if (!$canSend) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? 'search';
$customerModel = new CustomerModel();

if ($action === 'all_emails') {
    // Tüm aktif müşteri e-postalarını çek
    $customers = $customerModel->getActiveCustomersWithEmail();
    $results = [];
    foreach ($customers as $c) {
        $email = trim($c['email']);
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $results[] = [
                'id'       => $email,
                'text'     => $c['company'] . ' (' . $email . ')',
                'company'  => $c['company'],
                'email'    => $email,
                'yetkili'  => $c['yetkili'] ?? '',
                'city'     => $c['city'] ?? ''
            ];
        }
    }

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'success',
        'count'   => count($results),
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Varsayılan: Dinamik Arama
$term = trim((string)($_GET['q'] ?? ''));
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 30;

$customers = $customerModel->searchActiveCustomersWithEmail($term, $limit);
$results = [];

foreach ($customers as $c) {
    $email = trim($c['email'] ?? '');
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $results[] = [
            'id'       => $email,
            'text'     => $c['company'] . ' (' . $email . ')',
            'company'  => $c['company'],
            'email'    => $email,
            'yetkili'  => $c['yetkili'] ?? '',
            'city'     => $c['city'] ?? ''
        ];
    }
}

if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'results' => $results
], JSON_UNESCAPED_UNICODE);
exit;
