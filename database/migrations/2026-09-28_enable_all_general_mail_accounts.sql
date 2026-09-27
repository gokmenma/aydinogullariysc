-- Genel mail hesaplarinin (account_type = 1) posta kutusu moduluyle esitlenmesini aktif etme

UPDATE `mail_accounts`
SET `imap_host` = IF(COALESCE(`imap_host`, '') = '', 'mail.guzel.net.tr', `imap_host`),
    `imap_port` = IF(COALESCE(`imap_port`, 0) = 0, 993, `imap_port`),
    `imap_encryption` = IF(COALESCE(`imap_encryption`, '') = '', 'ssl', `imap_encryption`),
    `smtp_host` = IF(COALESCE(`smtp_host`, '') = '', 'mail.guzel.net.tr', `smtp_host`),
    `smtp_port` = IF(COALESCE(`smtp_port`, 0) = 0, 465, `smtp_port`),
    `smtp_encryption` = IF(COALESCE(`smtp_encryption`, '') = '', 'ssl', `smtp_encryption`),
    `sync_enabled` = 1
WHERE `account_type` = 1;
