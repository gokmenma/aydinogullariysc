<?php
// Kullanıcı Menü Sıralaması Tablosu Migration
require_once __DIR__ . '/../../configs/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `user_menu_orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `menu_order` LONGTEXT NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_user_menu_orders_user` (`user_id`),
        INDEX `idx_user_menu_orders_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $ac->exec($sql);
    echo "Migration 20260919_user_menu_orders basariyla uygulandi.\n";
} catch (PDOException $e) {
    echo "Migration Hatasi: " . $e->getMessage() . "\n";
}
