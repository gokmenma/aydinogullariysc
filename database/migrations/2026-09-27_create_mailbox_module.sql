-- Super Admin posta kutusu modulu (tekrar calistirilabilir)

ALTER TABLE `mail_accounts`
    ADD COLUMN IF NOT EXISTS `imap_host` VARCHAR(255) NULL AFTER `mail_password`,
    ADD COLUMN IF NOT EXISTS `imap_port` SMALLINT UNSIGNED NOT NULL DEFAULT 993 AFTER `imap_host`,
    ADD COLUMN IF NOT EXISTS `imap_encryption` VARCHAR(20) NOT NULL DEFAULT 'ssl' AFTER `imap_port`,
    ADD COLUMN IF NOT EXISTS `smtp_host` VARCHAR(255) NULL AFTER `imap_encryption`,
    ADD COLUMN IF NOT EXISTS `smtp_port` SMALLINT UNSIGNED NULL AFTER `smtp_host`,
    ADD COLUMN IF NOT EXISTS `smtp_encryption` VARCHAR(20) NULL AFTER `smtp_port`,
    ADD COLUMN IF NOT EXISTS `sync_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `smtp_encryption`,
    ADD COLUMN IF NOT EXISTS `last_sync_at` DATETIME NULL AFTER `sync_enabled`,
    ADD COLUMN IF NOT EXISTS `last_sync_error` VARCHAR(1000) NULL AFTER `last_sync_at`;

UPDATE `mail_accounts`
SET `imap_host` = 'mail.guzel.net.tr', `imap_port` = 993, `imap_encryption` = 'ssl',
    `smtp_host` = 'mail.guzel.net.tr', `smtp_port` = 465, `smtp_encryption` = 'ssl',
    `sync_enabled` = 1
WHERE LOWER(TRIM(`mail_address`)) = 'info@aydinogullariyangin.com'
  AND (`imap_host` IS NULL OR `imap_host` = '');

CREATE TABLE IF NOT EXISTS `mailbox_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` INT(10) NOT NULL,
    `folder` VARCHAR(32) NOT NULL DEFAULT 'inbox',
    `imap_uid` BIGINT UNSIGNED NULL,
    `uid_validity` BIGINT UNSIGNED NULL,
    `message_id` VARCHAR(512) NULL,
    `in_reply_to` VARCHAR(512) NULL,
    `from_address` VARCHAR(255) NOT NULL DEFAULT '',
    `from_name` VARCHAR(255) NULL,
    `to_addresses` TEXT NULL,
    `cc_addresses` TEXT NULL,
    `subject` VARCHAR(1000) NOT NULL DEFAULT '',
    `body_html` MEDIUMTEXT NULL,
    `body_text` MEDIUMTEXT NULL,
    `has_attachments` TINYINT(1) NOT NULL DEFAULT 0,
    `received_at` DATETIME NULL,
    `is_read_local` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mailbox_account_folder_uid` (`account_id`, `folder`, `uid_validity`, `imap_uid`),
    KEY `idx_mailbox_account_folder_date` (`account_id`, `folder`, `received_at`),
    KEY `idx_mailbox_message_id` (`message_id`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pages` (`p_title`, `p_link`, `pid`)
SELECT 'Gelen / Giden Mail', 'mailbox', 15
WHERE NOT EXISTS (SELECT 1 FROM `pages` WHERE `p_link` = 'mailbox');
