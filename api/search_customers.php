<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/../configs/functions.php';

use App\Model\CustomerModel;

// Yetki & Oturum Kontrolü
$userId = (int)($_SESSION['lid'] ?? 0);
if ($userId <= 0 || !isset($_SESSION['login'])) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Oturum süresi dolmuş veya yetkisiz erişim.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? 'search';
$customerModel = new CustomerModel();

// Belirli bir ID'ye göre tek müşteri getirme (Edit veya Modal sonrası otomatik seçim için)
if ($action === 'get_by_id') {
    $id = (int)($_GET['id'] ?? 0);
    $customer = null;
    if ($id > 0) {
        $customer = $customerModel->find($id);
    }

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');

    if ($customer && empty($customer->deleted_at)) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id'          => (int)$customer->id,
                'text'        => $customer->company,
                'company'     => $customer->company,
                'yetkili'     => $customer->yetkili ?? '',
                'email'       => $customer->email ?? '',
                'gsm'         => $customer->gsm ?? '',
                'city'        => $customer->city ?? '',
                'ilce'        => $customer->ilce ?? '',
                'odemevadesi' => $customer->OdemeVade ?? ''
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Firma bulunamadı.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Varsayılan: Dinamik AJAX Arama
$term = trim((string)($_GET['q'] ?? ''));
$limit = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 50) : 30;

$sql = "SELECT 
            id,
            company,
            yetkili,
            email,
            gsm,
            city,
            ilce,
            OdemeVade
        FROM customers
        WHERE deleted_at IS NULL";

$params = [];
if ($term !== '') {
    $sql .= " AND (company LIKE :q1 OR yetkili LIKE :q2 OR email LIKE :q3 OR gsm LIKE :q4 OR city LIKE :q5 OR ilce LIKE :q6)";
    $like = '%' . $term . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
    $params[':q5'] = $like;
    $params[':q6'] = $like;
}

$sql .= " ORDER BY id DESC LIMIT " . (int)$limit;

$stmt = $ac->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($rows as $r) {
    $results[] = [
        'id'          => (int)$r['id'],
        'text'        => $r['company'],
        'company'     => $r['company'],
        'yetkili'     => $r['yetkili'] ?? '',
        'email'       => $r['email'] ?? '',
        'gsm'         => $r['gsm'] ?? '',
        'city'        => $r['city'] ?? '',
        'ilce'        => $r['ilce'] ?? '',
        'odemevadesi' => $r['OdemeVade'] ?? ''
    ];
}

if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'results' => $results,
    'count'   => count($results)
], JSON_UNESCAPED_UNICODE);
exit;
