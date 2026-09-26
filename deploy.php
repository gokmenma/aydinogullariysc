<?php
$secret = (string) getenv('DEPLOY_WEBHOOK_SECRET');
if ($secret === '') {
    http_response_code(503);
    exit('Webhook yapılandırılmamış.');
}

$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    exit("Unauthorized");
}

$output = [];

exec('cd /home/aydinogu/repositories/aydinogullariysc && /usr/local/cpanel/3rdparty/bin/git pull 2>&1', $output);

file_put_contents("deploy.log", implode("\n", $output)."\n", FILE_APPEND);

echo "Deploy OK";
