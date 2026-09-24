-- Migration: 20260924_081700_version_note_v2_4_60.sql
-- Sürüm Notu: Keşif Listesinde Tablo İçi Tooltip Taşma ve Kesilme Sorununun Çözümü

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.60',
    'Keşif Listesi Tablo İçi Tooltip Kesilme Düzeltmesi',
    'bugfix',
    '- Keşif listesi tablosunda (kesif/list) CSS pseudo-element tooltip lerinin table-responsive ve kart sınırları nedeniyle üstten kesilmesi/kırpılması sorunu giderildi.\n- Tooltip yapısı Popper/Bootstrap altyapısına (container: body ve boundary: window) taşınarak tüm overflow sınırlarından bağımsız ve eksiksiz görüntülenmesi sağlandı.\n- Otomatik yönlenme (auto placement) ve satır sonu kaydırma (pre-wrap, word-break) ile uzun keşif notları, adresler ve şirket bilgileri için şık koyu tema desteği entegre edildi.',
    'Antigravity AI',
    '2026-09-24 08:17:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.60'
);
