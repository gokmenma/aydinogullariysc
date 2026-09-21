-- Migration: Add Version Note v2.4.6
-- Description: Servis No sütun araması hassaslaştırıldı (sadece servis numarası ile eşleşme)

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Servis No Sütun Filtreleme Eşleşme Düzeltmesi',
    'v2.4.6',
    'bugfix',
    '- Servis listesi DataTables filtrelemesinde Servis No alanının veritabanı ID değeriyle yanlış eşleşmesi engellendi; sadece görünen servis numarası (p.service_number) üzerinden arama yapılması sağlandı.
- Arama kriterinde 3355 gibi değerler arandığında farklı servis numarasına sahip ID eşleşmelerinin listeye gelmesi sorunu giderildi.',
    'Antigravity AI',
    '2026-09-21 11:28:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Servis No Sütun Filtreleme Eşleşme Düzeltmesi'
);
