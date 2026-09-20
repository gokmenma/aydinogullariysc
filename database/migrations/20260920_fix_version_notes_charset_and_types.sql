-- Sürüm notları ve keşifler tablolarının karakter seti ve sütun tipi düzeltme migration'ı
-- Export (Dışa Aktarma / mysqldump) sırasındaki 1064 sözdizimi hatasını düzeltir.
-- Tarih: 2026-09-20

ALTER TABLE `version_notes` 
    ENGINE = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    MODIFY `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY `updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `kesifler` 
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
