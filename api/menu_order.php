<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Model\MenuOrderModel;

// Oturum kontrolü
$userId = (int)($_SESSION['lid'] ?? ($_SESSION['id'] ?? (function_exists('sesset') ? sesset("id") : 0)));
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Oturum süreniz dolmuş. Lütfen sayfayı yenileyip tekrar deneyiniz.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$requestData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $requestData = $decoded;
    }
}
if (empty($requestData)) {
    $requestData = $_POST;
}

$action = $requestData['action'] ?? ($_GET['action'] ?? 'get');
$menuModel = new MenuOrderModel();

switch ($action) {
    case 'save':
        $mainOrder = $requestData['main_order'] ?? [];
        $subOrder = $requestData['sub_order'] ?? [];

        if (!is_array($mainOrder)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Geçersiz ana menü sıralama verisi.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Temizleme (Sanitization)
        $cleanMainOrder = array_values(array_filter(array_map('trim', $mainOrder), function($item) {
            return !empty($item) && is_string($item) && preg_match('/^[a-zA-Z0-9_\-\/\.\?\=\&]+$/', $item);
        }));

        $cleanSubOrder = [];
        if (is_array($subOrder)) {
            foreach ($subOrder as $parentKey => $items) {
                if (is_string($parentKey) && is_array($items)) {
                    $cleanSubOrder[$parentKey] = array_values(array_filter(array_map('trim', $items), function($subItem) {
                        return !empty($subItem) && is_string($subItem) && preg_match('/^[a-zA-Z0-9_\-\/\.\?\=\&]+$/', $subItem);
                    }));
                }
            }
        }

        $orderPayload = [
            'main_order' => $cleanMainOrder,
            'sub_order' => $cleanSubOrder
        ];

        $result = $menuModel->saveOrder($userId, $orderPayload);

        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Menü sıralaması başarıyla kaydedildi.',
                'data' => $orderPayload
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Menü sıralaması kaydedilirken bir hata oluştu.'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'reset':
        $result = $menuModel->resetOrder($userId);
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Menü sıralaması varsayılana sıfırlandı.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Menü sırası sıfırlanırken bir hata oluştu.'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'get':
    default:
        $savedOrder = $menuModel->getOrderByUserId($userId);
        echo json_encode([
            'status' => 'success',
            'data' => $savedOrder
        ], JSON_UNESCAPED_UNICODE);
        break;
}
