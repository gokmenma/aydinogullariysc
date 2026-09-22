<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/configs/functions.php';

use App\Model\GlobalSearchModel;

// Session ve Giriş Kontrolü
if (!isset($_SESSION['login']) || (int)set('system_statu') !== 1) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Oturum süreniz dolmuş veya yetkiniz bulunmamaktadır.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : 'all';
$limit    = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 20) : 8;

$allowedCategories = ['all', 'offers', 'products', 'customers', 'services', 'kesifler', 'reports'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

try {
    $searchModel = new GlobalSearchModel();
    $data = $searchModel->search($query, $category, $limit);

    if (mb_strlen($query, 'UTF-8') >= 2) {
        audit_log(
            'search',
            'global_search',
            'Küresel arama yapıldı: ' . $query,
            'global',
            $query,
            [
                'query'       => $query,
                'category'    => $category,
                'total_found' => $data['counts']['all'] ?? 0
            ]
        );
    }

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'success',
        'query'   => $query,
        'counts'  => $data['counts'],
        'results' => $data['results']
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (\Exception $e) {
    error_log('Global Search API Error: ' . $e->getMessage());

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Arama işlemi sırasında bir hata oluştu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
