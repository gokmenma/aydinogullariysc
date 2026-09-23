-- Version note for v2.4.49: Erken Scrollbar Render ve Native Scroll Flash Onleme
INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.49',
    'Erken Scrollbar Render ve Native Scroll Flash Önleme',
    'improvement',
    '- Sayfa yüklenirken ortaya çıkan native kaba scrollbar yanıp sönmesi (FOUC / flash) engellendi.\n- <head> içerisine kritik erken render scrollbar tanımları (scrollbar-width, scrollbar-color ve ::-webkit-scrollbar) inline olarak eklendi.\n- Sidebar ve customscroll elemanlarında JavaScript devreye girmeden önce native scrollbar görünümü önlendi.\n- Açık ve Karanlık Mod (Dark Mode) renkleriyle tam uyumlu modern, ince scrollbar sabitlendi.',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.49'
);
