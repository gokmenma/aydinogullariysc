-- Migration: 20260924_081000_version_note_v2_4_59.sql
-- Sürüm Notu: Teklif Listesinde Teklif Numarası Düzenleme Bağlantısı

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.59',
    'Teklif Listesinde Teklif Numarası Düzenleme Bağlantısı',
    'improvement',
    '- Teklif listesi (offers/list) tablosunda yer alan Teklif Numarası (TK...) alanına teklif düzenleme ekranına (offers/offer-manage) doğrudan yönlendiren tıklanabilir bağlantı eklendi.\n- Yetki kontrolleri (offeredit ve template_offer_edit) entegre edilerek yetkili kullanıcılar için hızlı erişim sağlandı.\n- Teklif satır listesi (offers/items-list) API çıktısında da teklif numarası düzenleme bağlantısı ile senkronize edildi.',
    'Antigravity AI',
    '2026-09-24 08:10:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.59'
);
