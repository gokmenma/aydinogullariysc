<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Helper\Security;
use App\Service\MailboxService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$accountId = (int) ($_POST['account_id'] ?? ($_GET['account_id'] ?? 0));
if ($accountId < 1) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz hesap ID.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $service = new MailboxService($ac);
    $count = $service->syncAccount($accountId, 100);
    $accounts = $service->accounts();
    $activeAcc = null;
    foreach ($accounts as $a) {
        if ((int) $a['id'] === $accountId) {
            $activeAcc = $a;
            break;
        }
    }
    echo json_encode([
        'status' => 'success',
        'count' => $count,
        'message' => $count > 0 ? "$count yeni mail alındı." : 'Posta kutusu güncel.',
        'last_sync_at' => (string) ($activeAcc['last_sync_at'] ?? date('Y-m-d H:i:s')),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log(sprintf(
        '[mailbox_sync] account_id=%d type=%s message=%s',
        $accountId,
        get_class($e),
        $e->getMessage()
    ));

    $message = $e->getMessage();
    $errorCode = 'mailbox_sync_failed';
    if ($e instanceof PDOException && in_array((string) $e->getCode(), ['42S02', '42S22'], true)) {
        $message = 'Mail modülü veritabanı kurulumu sunucuda eksik. İlgili migration dosyaları uygulanmalıdır.';
        $errorCode = 'mailbox_database_missing';
    } elseif (stripos($message, 'PHP IMAP') !== false) {
        $errorCode = 'imap_extension_missing';
    } elseif (stripos($message, 'güvenli bağlantı') !== false || stripos($message, 'TLS') !== false) {
        $errorCode = 'imap_tls_failed';
    } elseif (stripos($message, 'IMAP') !== false || stripos($message, 'AUTHENTICATIONFAILED') !== false) {
        $errorCode = 'imap_connection_failed';
    }

    http_response_code(500);
    echo json_encode(['status' => 'error', 'error_code' => $errorCode, 'message' => $message], JSON_UNESCAPED_UNICODE);
}
