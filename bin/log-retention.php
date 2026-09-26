<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/configs/config.php';
require $root . '/vendor/autoload.php';

use App\Service\LogRetentionService;

$options = getopt('', ['execute', 'batch-size:']);
$execute = array_key_exists('execute', $options);
$batchSize = isset($options['batch-size']) ? (int) $options['batch-size'] : 1000;

try {
    $service = new LogRetentionService($ac, $batchSize);
    $result = $service->run($execute);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Log saklama işlemi başarısız: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
