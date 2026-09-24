-- Migration: 20260924_103200_version_note_v2_4_67.sql
-- Sürüm Notu: Rapor Listesine Kayıt Yapan ve Kayıt Tarihi Sütunlarının Eklenmesi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.67',
    'Rapor Listesine Kayıt Yapan ve Kayıt Tarihi Sütunları Eklendi',
    'feature',
    '- Rapor Yönetimi tablosuna (reports/reports) "Kayıt Tarihi" ve "Kayıt Yapan" sütunları eklendi.\n- Server-side DataTables sorgusuna (api/reports_datatables.php) users tablosu JOIN edilerek kayıt oluşturan personelin adı ve oluşturulma tarihi formatlanmış olarak entegre edildi.\n- Arama ve sütun bazlı filtrelemelere kayıt tarihi ve personel adı dahil edildi.',
    'Antigravity AI',
    '2026-09-24 10:32:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.67'
);
