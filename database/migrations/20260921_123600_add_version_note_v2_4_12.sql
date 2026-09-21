-- Sürüm Notu: DataTable Başlık Filtrelerinde Yalnızca Veri İçeren Seçeneklerin Gösterilmesi
-- Tarih: 2026-09-21 12:36:00
-- Sürüm: v2.4.12

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'DataTable Başlık Filtrelerinde Yalnızca Veri İçeren Seçeneklerin Listelenmesi',
    'v2.4.12',
    'improvement',
    '- Sütun filtre açılır menüsünde (popover) kaydı 0 olan gereksiz seçeneklerin gösterilmesi engellendi.\n- Sadece tabloda gerçek verisi bulunan seçeneklerin (sayısı > 0 olanlar) listelenmesi sağlandı.\n- İstemci taraflı sayfalama yapılan tablolarda sadece görünen ilk sayfanın değil tüm tablodaki toplam kayıtların taranması ve doğru sayı adetleriyle getirilmesi sağlandı.\n- Seçenek sayacı ve arama kutusu yalnızca aktif kayıt adetlerini gösterecek şekilde optimize edildi.',
    'Antigravity AI',
    '2026-09-21 12:36:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.12' AND `title` = 'DataTable Başlık Filtrelerinde Yalnızca Veri İçeren Seçeneklerin Listelenmesi'
);
