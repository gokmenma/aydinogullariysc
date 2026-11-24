ALTER TABLE `purchases`
    ADD COLUMN IF NOT EXISTS `project_id` INT(11) NULL DEFAULT NULL AFTER `talep_id`;

CREATE INDEX IF NOT EXISTS `idx_purchases_project_id` ON `purchases` (`project_id`);
