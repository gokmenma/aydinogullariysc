<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require 'vendor/autoload.php';

try {
    $logger = \App\Logging\LoggerFactory::file();
    echo "LoggerFactory::file() - OK<br>";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Class exists: " . (class_exists('App\Logging\LoggerFactory') ? 'YES' : 'NO') . "<br>";
}
?>
