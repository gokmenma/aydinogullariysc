-- Sürüm Notu: v2.4.53
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Müşteri Yönetimi Özet Kartları Hizalama Düzeltmesi',
    'v2.4.53',
    'improvement',
    '- Müşteri düzenleme sayfasında (customers/manage) yer alan 4 özet bilgi kartının (Toplam Servis Sayısı, Toplam Teklif Sayısı, Son Oluşturulan Teklif, Son Oluşturulan Servis) üst başlık kartı ve alt form kartından daha geniş durması sorunu giderildi.\n- Bootstrap negatif kenar paylarından kaynaklanan genişlik taşması yerine CSS Grid (customer-stats-grid) yapısına geçildi.\n- Özet kartların sol ve sağ sınırları, üstteki başlık kartı ve alttaki Firma Bilgileri kartı ile piksel düzeyinde kusursuz aynı hizaya getirildi.\n- Kartların mobil, tablet ve masaüstü duyarlı (responsive) grid davranışları ve eşit yükseklik dengeleri optimize edildi.',
    'Antigravity AI',
    '2026-09-23 21:25:00',
    '2026-09-23 21:25:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.53'
);
