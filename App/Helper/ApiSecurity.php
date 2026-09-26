<?php

namespace App\Helper;

use PDO;

final class ApiSecurity
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /** @var array<string, array<int, string>> */
    private const PERMISSIONS = [
        'App/api/customer.php' => ['customer_dashboard', 'customeradd', 'customeredit', 'customerdelete'],
        'App/api/customers-list.php' => ['customer_dashboard', 'customeradd', 'customeredit'],
        'App/api/define.php' => [],
        'App/api/document.php' => ['indocview', 'indocadd', 'outdocview', 'outdocadd', 'docedit'],
        'App/api/export-offers.php' => ['data_export_offers'],
        'App/api/geocode.php' => [],
        'App/api/get-logs.php' => ['panelsettings'],
        'App/api/get-offer-filter-options.php' => ['offerview'],
        'App/api/get-offer-item-filters.php' => ['teklif_kalemleri_listesi', 'offerview'],
        'App/api/get-offer-items.php' => ['teklif_kalemleri_listesi', 'offerview'],
        'App/api/get-offers.php' => ['offerview'],
        'App/api/note-delete.php' => ['notedelete'],
        'App/api/offer.php' => ['offerview', 'offeradd', 'offeredit', 'offerdelete', 'offercopy'],
        'App/api/products.php' => ['product_dashboard', 'productadd', 'productedit', 'productdelete'],
        'App/api/purchase.php' => ['purchase_dashboard', 'purchaseadd', 'purchaseedit', 'purchasedelete'],
        'App/api/service-save.php' => ['serviceAdd', 'serviceEdit'],
        'App/api/units.php' => [],
        'App/api/version-notes.php' => [],
        'api/customers_datatables.php' => ['customer_dashboard', 'customeradd', 'customeredit', 'customerdelete'],
        'api/customers_export.php' => ['customerexport'],
        'api/dashboard-layout.php' => [],
        'api/global_search.php' => [],
        'api/mail_templates.php' => ['mailandsmssend'],
        'api/menu_order.php' => [],
        'api/permission_save.php' => ['authdefine', 'authEdit'],
        'api/products_datatables.php' => ['product_dashboard', 'productcategory', 'productadd', 'productedit', 'productdelete'],
        'api/reports_datatables.php' => ['reportview'],
        'api/search_customers.php' => [],
        'api/search_products.php' => [],
        'api/search_recipients.php' => ['mailandsmssend'],
        'api/services_datatables.php' => ['serviceView'],
        'api/services_export.php' => ['serviceView', 'data_export_service'],
        'api/test_smtp.php' => ['panelsettings'],
        'pages/1/ajax.php' => [],
        'pages/1/kesif/api.php' => ['kesifView', 'kesifCreate', 'kesifEdit', 'kesifDelete'],
        'pages/1/products/api.php' => ['productadd', 'productedit', 'productdelete'],
        'uploadoffer.php' => ['fileadd'],
        'upfileof.php' => ['fileadd'],
        'upfileofp.php' => ['fileadd'],
        'pages/1/upfileof.php' => ['fileadd'],
    ];

    /** @var array<string, array<string, array<int, string>>> */
    private const ACTION_PERMISSIONS = [
        'App/api/customer.php' => [
            'check_email' => ['customeradd', 'customeredit'],
            'create' => ['customeradd'],
        ],
        'App/api/document.php' => ['receiveDocument' => ['docedit']],
        'App/api/note-delete.php' => ['*' => ['notedelete']],
        'App/api/offer.php' => [
            'copyOffer' => ['offercopy'], 'saveOffer' => ['offeradd', 'offeredit'],
            'deleteOffer' => ['offerdelete'], 'convertToTry' => ['offeredit'],
        ],
        'App/api/purchase.php' => [
            'doneDemand' => ['purchaseedit'], 'deleteItemFile' => ['purchaseedit'],
            'savePurchases' => ['purchaseadd', 'purchaseedit'],
        ],
        'App/api/service-save.php' => ['*' => ['serviceAdd', 'serviceEdit']],
        'App/api/version-notes.php' => [
            'save' => ['panelsettings', 'authdefine'],
            'delete' => ['panelsettings', 'authdefine'],
        ],
        'api/permission_save.php' => ['*' => ['authdefine', 'authEdit']],
        'api/services_datatables.php' => [
            'toggle_accounting_receipt' => ['muhasebe_teslim_alma_yetkisi'],
            'get_accounting_receipt_logs' => ['serviceView'],
        ],
        'api/test_smtp.php' => ['*' => ['panelsettings']],
        'pages/1/kesif/api.php' => [
            'create' => ['kesifCreate'], 'update' => ['kesifEdit'],
            'delete' => ['kesifDelete'], 'delete_image' => ['kesifEdit'],
        ],
        'pages/1/products/api.php' => [
            'save-product' => ['productadd', 'productedit'], 'delete-product' => ['productdelete'],
        ],
    ];

    public static function enforce(PDO $db, string $root): void
    {
        $endpoint = self::endpoint($root);
        if ($endpoint === null || $endpoint === 'api/maintenance-status.php') {
            return;
        }

        if (empty($_SESSION['login']) || (int) ($_SESSION['lid'] ?? 0) < 1) {
            self::deny($db, 401, 'auth_missing', 'Oturum açmanız gerekiyor.', $endpoint);
        }

        $userState = $db->prepare('SELECT statu, permission FROM users WHERE id = ? LIMIT 1');
        $userState->execute([(int) $_SESSION['lid']]);
        $currentUser = $userState->fetch(PDO::FETCH_ASSOC);
        if (!$currentUser || (int) $currentUser['statu'] !== 1
            || (int) $currentUser['permission'] !== (int) ($_SESSION['perm'] ?? 0)) {
            $_SESSION = [];
            self::deny($db, 401, 'session_revoked', 'Oturumunuz geçersiz veya hesabınız pasif.', $endpoint);
        }

        $permissions = self::PERMISSIONS[$endpoint] ?? null;
        if ($permissions === null) {
            self::deny($db, 403, 'unregistered_endpoint', 'API erişim politikası tanımlı değil.', $endpoint);
        }
        $action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');
        if (isset(self::ACTION_PERMISSIONS[$endpoint])) {
            $actionRules = self::ACTION_PERMISSIONS[$endpoint];
            $permissions = $actionRules[$action] ?? ($actionRules['*'] ?? $permissions);
        }
        if ($endpoint === 'pages/1/ajax.php' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            $page = (string) ($_POST['page'] ?? '');
            $pagePermissions = [
                'customers' => ['customerdelete'], 'reports/reports' => ['reportdel'],
                'offers' => ['offerdelete'], 'all-files' => ['filedelete'],
                'permission-settings' => ['authDel'], 'all-users' => ['userdelete'],
                'users' => ['userdelete'], 'services' => ['serviceDel'],
                'all-projects' => ['serviceDel'], 'purchases' => ['purchasedelete'],
                'products' => ['productdelete'], 'tasks' => ['tododelete'],
            ];
            if (isset($pagePermissions[$page])) {
                $permissions = $pagePermissions[$page];
            }
        }
        if ($permissions !== [] && !self::hasAnyPermission($permissions)) {
            self::deny($db, 403, 'permission_denied', 'Bu işlem için yetkiniz bulunmuyor.', $endpoint);
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, self::SAFE_METHODS, true)) {
            self::validateCsrf($db, $endpoint);
        }
    }

    private static function endpoint(string $root): ?string
    {
        $script = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $rootPath = realpath($root);
        if ($script === false || $rootPath === false || strpos($script, $rootPath . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($script, strlen($rootPath) + 1));
        if (strpos($relative, 'api/') === 0 || strpos($relative, 'App/api/') === 0 || isset(self::PERMISSIONS[$relative])) {
            return $relative;
        }
        return null;
    }

    private static function hasAnyPermission(array $permissions): bool
    {
        $userId = (int) ($_SESSION['lid'] ?? ($_SESSION['id'] ?? 0));
        $userPerm = (int) ($_SESSION['perm'] ?? ($_SESSION['permission'] ?? 0));
        if (in_array($userId, [1, 12], true) || in_array($userPerm, [1, 13], true)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (function_exists('permtrue') && permtrue($permission)) {
                return true;
            }
        }
        return false;
    }

    private static function validateCsrf(PDO $db, string $endpoint): void
    {
        $provided = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
        if ($provided === '') {
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
            if (strpos($contentType, 'application/json') !== false) {
                $decoded = json_decode((string) file_get_contents('php://input'), true);
                $provided = is_array($decoded) ? (string) ($decoded['csrf_token'] ?? '') : '';
            }
        }
        $expected = (string) ($_SESSION['csrf_token'] ?? '');
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            self::deny($db, 403, 'csrf_failed', 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.', $endpoint);
        }
    }

    private static function deny(PDO $db, int $status, string $event, string $message, string $endpoint): void
    {
        self::log($db, $event, $endpoint);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function log(PDO $db, string $event, string $endpoint, array $context = []): void
    {
        try {
            $stmt = $db->prepare('INSERT INTO security_events (event_type, user_id, endpoint, ip_address, user_agent, context_json, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $event,
                (int) ($_SESSION['lid'] ?? 0) ?: null,
                substr($endpoint, 0, 255),
                substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $e) {
            error_log('Security event log failed: ' . $e->getMessage());
        }
    }
}
