-- Sürüm Notu: v2.4.42 - Logo Alanı ve Üst Menü (Topbar) Yükseklik ve Border Hizalama Optimizasyonu
INSERT INTO version_notes (version_tag, title, category, description, author, created_at)
SELECT 
    'v2.4.42',
    'Logo Alanı ve Üst Menü (Topbar) Yükseklik ve Border Hizalama Optimizasyonu',
    'improvement',
    '- Logo konteyneri (.brand-logo) ile üst bar (.header) arasındaki yükseklik ve alt kenarlık (border-bottom) piksel uyumu kusursuzlaştırıldı.
- .header ve .brand-logo bileşenlerine sabit 70px (min/max/box-sizing) değerleri atanarak piksel taşmaları ve asimetrik boşluklar giderildi.
- Logo görselinin (img) dikey dolgusu optimize edildi (max-height: 44px, padding: 0 18px), dikey hizada topbar bileşenleriyle dengeli ve optik olarak ortalanmış bir görünüm sağlandı.
- Koyu sidebar ile açık header birleşimindeki alt çizgi kesişimi ve tema hazır ayarları (presets) için renk uyumluluğu netleştirildi.',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.42'
);
