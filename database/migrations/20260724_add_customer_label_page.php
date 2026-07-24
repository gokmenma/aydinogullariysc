<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

try {
    $sql = file_get_contents(__DIR__ . '/20260724_add_customer_label_page.sql');
    $ac->exec($sql);
    echo "Migration 20260724_add_customer_label_page.sql basariyla calistirildi.\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
