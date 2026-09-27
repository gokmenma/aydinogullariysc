-- Mail eklerini yerel ve yetkili indirme icin saklar (tekrar calistirilabilir)
CREATE TABLE IF NOT EXISTS `mailbox_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` BIGINT UNSIGNED NOT NULL,
    `part_number` VARCHAR(32) NOT NULL,
    `file_name` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(150) NOT NULL DEFAULT 'application/octet-stream',
    `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `content` LONGBLOB NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mailbox_attachment_part` (`message_id`, `part_number`),
    KEY `idx_mailbox_attachment_message` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `mailbox_messages`
    ADD COLUMN IF NOT EXISTS `attachments_synced_at` DATETIME NULL AFTER `has_attachments`;

ALTER TABLE `mailbox_attachments`
    ADD COLUMN IF NOT EXISTS `content_id` VARCHAR(500) NULL AFTER `mime_type`,
    ADD COLUMN IF NOT EXISTS `is_inline` TINYINT(1) NOT NULL DEFAULT 0 AFTER `content_id`;

CREATE TABLE IF NOT EXISTS `mailbox_attachment_chunks` (
    `attachment_id` BIGINT UNSIGNED NOT NULL,
    `chunk_index` INT UNSIGNED NOT NULL,
    `content` MEDIUMBLOB NOT NULL,
    PRIMARY KEY (`attachment_id`, `chunk_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eski MIME taramasında gömülü görseller nedeniyle oluşan yanlış ataç işaretlerini düzelt.
UPDATE `mailbox_messages` m
SET m.`has_attachments` = CASE
    WHEN EXISTS (SELECT 1 FROM `mailbox_attachments` a WHERE a.`message_id` = m.`id`) THEN 1
    ELSE 0
END
WHERE m.`attachments_synced_at` IS NOT NULL;

-- CID kullanan HTML mailleri inline gorseller icin bir kez yeniden tara.
UPDATE `mailbox_messages`
SET `attachments_synced_at` = NULL
WHERE `body_html` LIKE '%cid:%';
