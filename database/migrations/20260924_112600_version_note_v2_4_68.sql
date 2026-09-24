-- Migration: 20260924_112600_version_note_v2_4_68.sql
-- Sürüm Notu: Rapor Yönetimi Tablo Sayfalama ve İşlem Butonları İyileştirmesi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.68',
    'Rapor Tablosu Sayfalama ve İşlem Butonları İyileştirmesi',
    'bugfix',
    '- Rapor listesinde (reports/reports) sayfa geçişlerinde responsive mod ve eski stateSave çakışması nedeniyle işlem butonlarının gizlenmesi/kaybolması sorunu giderildi.\n- Tablo kapsayıcısı standart table-responsive yapısına geçirildi ve drawCallback tooltip entegrasyonu sağlandı.\n- Sütun genişlikleri ve hizalamaları optimize edildi.',
    'Antigravity AI',
    '2026-09-24 11:26:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.68'
);
