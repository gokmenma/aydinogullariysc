<?php
/**
 * Migration: Add remote_file_id column to backup_logs
 */

return function (PDO $db) {
    $check = $db->query("SHOW COLUMNS FROM `backup_logs` LIKE 'remote_file_id'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE `backup_logs` ADD COLUMN `remote_file_id` VARCHAR(100) NULL AFTER `file_path`");
    }
};
