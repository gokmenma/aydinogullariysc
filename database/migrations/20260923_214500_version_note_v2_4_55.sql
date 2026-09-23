-- Sürüm Notu: v2.4.55
INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`, `updated_at`)
SELECT 
    'Ana Sayfa Hızlı Aksiyon Butonları Kare Tasarım ve Sağa Hizalama',
    'v2.4.55',
    'improvement',
    '- Ana sayfadaki karşılama bannerında bulunan hızlı işlem butonları (Yeni Teklif, Yeni Servis, Yeni Firma, Görev Ekle) sağa yaslı kare/kutu görünümüne dönüştürüldü.\n- Butonlar için modern ikon yerleşimi, yumuşak arkaplan ve etkileşimli hover/active mikro animasyonları entegre edildi.\n- Dark mode ve mobil/tablet duyarlı (responsive) ekran uyumlulukları tamamlandı.',
    'Antigravity AI',
    '2026-09-23 21:45:00',
    '2026-09-23 21:45:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.55'
);
