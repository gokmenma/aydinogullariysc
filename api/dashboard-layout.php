<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Model\DashboardLayoutModel;

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
$dashboardModel = new DashboardLayoutModel();

if (!in_array($action, ['get', 'save', 'reset', 'toggle_widget'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz pano işlemi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($action !== 'get' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Bu işlem yalnızca POST isteğiyle yapılabilir.'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'save':
        $layoutData = $requestData['layout'] ?? [];
        if (!is_array($layoutData)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Geçersiz yerleşim verisi formatı.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (count($layoutData) === 0 || count($layoutData) > 50) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'message' => 'Pano yerleşimi boş olamaz veya izin verilen kart sayısını aşamaz.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $result = $dashboardModel->saveLayout($userId, $layoutData);
        if ($result) {
            if (function_exists('audit_log')) {
                audit_log(
                    'update',
                    'dashboard',
                    'Kullanıcı ana sayfa kart yerleşimini güncelledi',
                    'user_dashboard_layouts',
                    (string)$userId,
                    ['widget_count' => count($layoutData)]
                );
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Pano yerleşimi başarıyla kaydedildi.',
                'data' => $dashboardModel->getEffectiveLayout($userId)
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Pano yerleşimi kaydedilirken bir hata oluştu.'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'reset':
        $result = $dashboardModel->resetLayout($userId);
        if ($result) {
            if (function_exists('audit_log')) {
                audit_log(
                    'delete',
                    'dashboard',
                    'Kullanıcı ana sayfa kart yerleşimini varsayılana sıfırladı',
                    'user_dashboard_layouts',
                    (string)$userId,
                    []
                );
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Pano yerleşimi varsayılana sıfırlandı.',
                'data' => $dashboardModel->getDefaultLayout($userId)
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Pano yerleşimi sıfırlanırken bir hata oluştu.'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'toggle_widget':
        $widgetId = trim($requestData['widget_id'] ?? '');
        $visible = isset($requestData['visible']) ? (bool)$requestData['visible'] : true;

        if (empty($widgetId)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Widget ID belirtilmedi.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $currentLayout = $dashboardModel->getEffectiveLayout($userId);
        $updated = false;

        foreach ($currentLayout as &$item) {
            if ($item['id'] === $widgetId) {
                $item['visible'] = $visible;
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $catalog = $dashboardModel->getWidgetCatalog($userId);
            if (isset($catalog[$widgetId])) {
                $currentLayout[] = [
                    'id' => $widgetId,
                    'x' => 0,
                    'y' => 999,
                    'w' => $catalog[$widgetId]['default_w'],
                    'h' => $catalog[$widgetId]['default_h'],
                    'minW' => $catalog[$widgetId]['min_w'],
                    'minH' => $catalog[$widgetId]['min_h'],
                    'visible' => $visible,
                ];
                $updated = true;
            }
        }

        if ($updated) {
            $dashboardModel->saveLayout($userId, $currentLayout);
            echo json_encode([
                'status' => 'success',
                'message' => 'Kart görünürlüğü güncellendi.',
                'data' => $dashboardModel->getEffectiveLayout($userId)
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Geçersiz widget ID.'
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'get':
    default:
        echo json_encode([
            'status' => 'success',
            'layout' => $dashboardModel->getEffectiveLayout($userId),
            'catalog' => $dashboardModel->getWidgetCatalog($userId)
        ], JSON_UNESCAPED_UNICODE);
        break;
}
