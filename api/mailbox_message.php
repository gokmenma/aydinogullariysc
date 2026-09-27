<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Service\MailboxService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$accountId = (int) ($_GET['account'] ?? 0);
$messageId = (int) ($_GET['message'] ?? 0);
if ($accountId < 1 || $messageId < 1) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz mail bilgisi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$showRemoteImages = ($_GET['show_remote_images'] ?? '') === '1';
$message = (new MailboxService($ac))->message($messageId, $accountId, $showRemoteImages);
if (!$message) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Mail bulunamadı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = trim((string) ($message['body_text'] ?: strip_tags((string) $message['body_html'])));
echo json_encode([
    'status' => 'success',
    'message' => [
        'id' => (int) $message['id'],
        'subject' => (string) $message['subject'],
        'from_address' => (string) $message['from_address'],
        'from_name' => (string) $message['from_name'],
        'to_addresses' => (string) $message['to_addresses'],
        'received_at' => (string) $message['received_at'],
        'body' => $body,
        'safe_html' => (string) ($message['safe_html'] ?? ''),
        'remote_image_count' => (int) ($message['remote_image_count'] ?? 0),
        'attachments' => array_map(static fn(array $attachment): array => [
            'id' => (int) $attachment['id'],
            'file_name' => (string) $attachment['file_name'],
            'mime_type' => (string) $attachment['mime_type'],
            'file_size' => (int) $attachment['file_size'],
            'download_url' => 'api/mailbox_attachment.php?id=' . (int) $attachment['id'],
        ], $message['attachments'] ?? []),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
