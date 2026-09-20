-- Sürüm notları tablosu geliştirme migration'ı
-- Tarih: 2026-09-20

-- Description alanının tipini TEXT olarak güncelle
ALTER TABLE `version_notes` MODIFY `description` TEXT NULL;

-- Yeni sütunları ekle (varsa hata vermemesi için güvenli kontrol veya doğrudan ekleme)
ALTER TABLE `version_notes` 
    ADD COLUMN IF NOT EXISTS `version_tag` VARCHAR(50) NULL DEFAULT NULL AFTER `title`,
    ADD COLUMN IF NOT EXISTS `category` VARCHAR(50) NULL DEFAULT 'feature' AFTER `version_tag`,
    ADD COLUMN IF NOT EXISTS `author` VARCHAR(100) NULL DEFAULT 'Admin' AFTER `description`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
