-- Sürüm Notu: Sistem Aktiviteleri (Logs) Tablosu ServerSide DataTables AJAX ve Global Filtreleme Entegrasyonu (v2.4.16)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.16',
    'Sistem Aktiviteleri Tablosu ServerSide AJAX ve Global Filtreleme Dönüşümü',
    '- Sistem Aktiviteleri (pages/1/logs/index.php) tablosu 40.000+ log kaydının tamamında anlık filtrelenebilmesi için DataTables serverSide AJAX mimarisine geçirildi.\n- App/api/get-logs.php uç noktası oluşturularak kullanıcı, işlem türü, modül, tarih aralığı ve arama filtreleri sunucu taraflı hızlandırıldı.\n- TableFilter popover filtrelerinde tüm veri tabanındaki toplam kayıt sayıları (columnCounts) bağlanarak, ilk sayfada görünmeyen kullanıcılar (örn: Cüneyt GÜLSÜN) dahil tüm personelin aktivitelerine anında filtrelenebilmesi sağlandı.\n- Arama kutusu ikon hizalamaları ve 0 adetli boş seçeneklerin gizlenmesi optimize edildi.',
    'improvement',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.16'
);
