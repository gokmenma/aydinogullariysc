<?php
/**
 * Migration: Add mail_password to mail_accounts
 * Date: 2026-09-20
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';

global $ac;

try {
    $columns = $ac->query("SHOW COLUMNS FROM mail_accounts LIKE 'mail_password'")->fetchAll();
    if (empty($columns)) {
        $ac->exec("ALTER TABLE mail_accounts ADD COLUMN mail_password VARCHAR(255) NULL AFTER mail_user");
        echo "OK: mail_password kolonu basariyla eklendi.\n";
    } else {
        echo "INFO: mail_password kolonu zaten mevcut.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
