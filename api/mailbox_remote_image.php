<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Service\MailboxService;
use App\Service\MailRemoteImageService;

if (!MailboxService::isSuperAdmin()) {
    http_response_code(403);
    exit;
}
try {
    $image = MailRemoteImageService::fetch((string) ($_GET['u'] ?? ''), (string) ($_GET['s'] ?? ''));
    header('Content-Type: ' . $image['mime_type']);
    header('Content-Length: ' . strlen($image['content']));
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src \'none\'; sandbox');
    header('Cache-Control: private, max-age=3600');
    header('Referrer-Policy: no-referrer');
    echo $image['content'];
} catch (Throwable $e) {
    http_response_code(404);
}
