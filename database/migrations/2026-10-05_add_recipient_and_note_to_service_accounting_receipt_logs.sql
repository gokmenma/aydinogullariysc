-- 2026-10-05: Add recipient_id and note columns to service_accounting_receipt_logs
ALTER TABLE `service_accounting_receipt_logs`
ADD COLUMN IF NOT EXISTS `recipient_id` INT(10) UNSIGNED NULL AFTER `action_by`,
ADD COLUMN IF NOT EXISTS `note` TEXT NULL AFTER `recipient_id`,
ADD INDEX IF NOT EXISTS `idx_recipient_id` (`recipient_id`);
