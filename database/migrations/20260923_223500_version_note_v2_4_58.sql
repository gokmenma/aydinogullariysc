-- Migration: 20260923_223500_version_note_v2_4_58.sql
-- Sürüm Notu: Tanımlamalar Menüsü Yetki ve Görünürlük Düzeltmesi

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.58',
    'Tanımlamalar Menüsü Yetki ve Görünürlük Düzeltmesi',
    'bugfix',
    '- Sidebar Tanımlamalar menüsü ve alt elemanlarındaki katı yetki kontrolü ($userPerm == 1) dinamik yetki ve Super Admin (Role 13) kapsayacak şekilde güncellendi.\n- Tanımlamalar altındaki Servis Konusu, Servis Durumu, Servis Bölgesi, Tahsilat Türü, Teklif Şablonları ve Birim Tanımlama sayfalarına Admin ve modül yetki kontrolleri uygulandı.\n- Tanımlama sayfalarındaki düzenleme/silme butonları ve oturum kontrolleri standartlaştırıldı.',
    'Antigravity AI',
    '2026-09-23 22:35:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.58'
);
