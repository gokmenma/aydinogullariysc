<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Service\MailboxService;

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    exit('Yetkisiz erişim.');
}
$attachmentId = (int) ($_GET['id'] ?? 0);
$stmt = $ac->prepare('SELECT a.file_name, a.mime_type, a.file_size, a.content, a.is_inline FROM mailbox_attachments a INNER JOIN mailbox_messages m ON m.id = a.message_id WHERE a.id = ? LIMIT 1');
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$attachment) {
    http_response_code(404);
    exit('Ek dosya bulunamadı.');
}
$fileName = preg_replace('/[\r\n"\\\/]+/u', '_', (string) $attachment['file_name']) ?: 'ek';
header('Content-Type: ' . ((string) $attachment['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . (int) $attachment['file_size']);
$inlineRequested = !empty($_GET['inline']);
$safeInlineTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
$allowInline = $inlineRequested && (int) $attachment['is_inline'] === 1 && in_array(strtolower((string) $attachment['mime_type']), $safeInlineTypes, true);
if ($inlineRequested && !$allowInline) {
    http_response_code(415);
    exit('Bu dosya tarayıcı içinde güvenli biçimde gösterilemez.');
}
$disposition = $allowInline ? 'inline' : 'attachment';
if (!$allowInline) header('Content-Type: application/octet-stream');
header("Content-Disposition: {$disposition}; filename*=UTF-8''" . rawurlencode($fileName));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; sandbox');
header('Cache-Control: private, no-store');
header('Cross-Origin-Resource-Policy: same-origin');
echo $attachment['content'];
if ((int) $attachment['file_size'] > 0 && strlen((string) $attachment['content']) === 0) {
    $chunks = $ac->prepare('SELECT content FROM mailbox_attachment_chunks WHERE attachment_id = ? ORDER BY chunk_index');
    $chunks->execute([$attachmentId]);
    while (($chunk = $chunks->fetchColumn()) !== false) echo $chunk;
}
