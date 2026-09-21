-- Migration: Add Version Note v2.4.5
-- Description: DataTables sunucu taraflı (server-side) sütun filtreleme desteği eklendi (JSON & metin uyumluluğu, DataTableFilter yardımcı sınıfı)

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'DataTables Sunucu Taraflı Sütun Filtreleme Düzeltmesi',
    'v2.4.5',
    'bugfix',
    '- TableFilter popover filtrelerinin ürettiği JSON arama parametrelerinin sunucu tarafında (Server-side DataTables API) çözümlenememesi sorunu giderildi.
- App\\Helper\\DataTableFilter merkezi yardımcı sınıfı oluşturuldu; metin, sayı, tarih ve çoklu seçim filtreleri için güvenli PDO sorgu koşulları ve parametre bağlama yapısı sağlandı.
- Servisler, müşteriler, ürünler ve raporlar DataTables API uç noktalarında (services_datatables.php, customers_datatables.php, products_datatables.php, reports_datatables.php) filtreleme motoru güncellendi.
- Servis No filtrelemesinde hem formatlanmış numara (SRV03354) hem de sayısal ID bazlı arama uyumluluğu sağlandı.',
    'Antigravity AI',
    '2026-09-21 11:26:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'DataTables Sunucu Taraflı Sütun Filtreleme Düzeltmesi'
);
