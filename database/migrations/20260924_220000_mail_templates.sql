-- Mail gonderim sablonlari
-- Tarih: 2026-09-24 22:00:00

CREATE TABLE IF NOT EXISTS `mail_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `sender_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `recipients_json` LONGTEXT NOT NULL,
    `body_html` LONGTEXT NOT NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mail_templates_user_name` (`created_by`, `name`),
    KEY `idx_mail_templates_created_by_updated` (`created_by`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sürüm notu: v2.4.76
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT
    'Mail Gönderim Şablonları',
    'v2.4.76',
    'feature',
    '- Mail gönderme sayfasındaki Şablon Kaydet ve Şablondan Yükle işlemleri aktif hale getirildi.\n- Şablonlarda gönderen hesabı, konu, firma ve alıcı bilgileri ile HTML mail içeriğinin birlikte saklanması ve geri yüklenmesi sağlandı.\n- Şablonlar kullanıcı bazında ayrıştırıldı; yetki, CSRF, gönderici hesabı ve alıcı e-posta doğrulamaları sunucu tarafına eklendi.\n- Aynı isimli kişisel şablonun güvenli biçimde güncellenmesi ve kayıtlı şablonların seçim penceresinde listelenmesi sağlandı.',
    'Antigravity AI',
    '2026-09-24 21:50:51'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.76'
);
