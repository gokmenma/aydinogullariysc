<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$service = new \App\Service\MailboxService($ac);
foreach ($service->accounts() as $account) {
    try {
        $count = $service->syncAccount((int) $account['id']);
        echo $account['mail_address'] . ': ' . $count . " yeni mail\n";
    } catch (Throwable $e) {
        fwrite(STDERR, $account['mail_address'] . ': ' . $e->getMessage() . "\n");
    }
}
