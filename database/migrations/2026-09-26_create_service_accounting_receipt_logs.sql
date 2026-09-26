CREATE TABLE IF NOT EXISTS `service_accounting_receipt_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(20) NOT NULL,
    `action_by` INT UNSIGNED NOT NULL,
    `action_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_service_id` (`service_id`),
    KEY `idx_action_at` (`action_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
