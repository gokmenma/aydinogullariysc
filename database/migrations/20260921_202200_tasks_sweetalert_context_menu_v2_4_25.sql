-- Yapılacaklar Sayfası SweetAlert2 Silme Onayı ve Sağ Tık Menüsü Sürüm Notu Migration
INSERT INTO version_notes (title, version_tag, category, description, author, created_at, updated_at)
SELECT 
    'Yapılacaklar Sayfası SweetAlert2 Silme Onayı ve Sağ Tık Menüsü',
    'v2.4.25',
    'improvement',
    '- Yapılacaklar (tasks) sayfasındaki görev silme işlemlerinde tarayıcının yerel confirm popupı yerine modern, şık ve animasyonlu SweetAlert2 onay modalı entegre edildi.\n- Görev listesi tablosundaki satırlara sağ tıklandığında açılan dinamik Context Menu (Sağ Tık Menüsü) geliştirildi.\n- Sağ tık menüsü üzerinden görevi inceleme, durumu anlık güncelleme (Yapıldı, Ertele, Yapılmadı), görevi düzenleme ve yetkiye bağlı güvenli silme aksiyonları eklendi.\n- Sağ tık menüsü için açık ve koyu tema (dark mode) tam uyumluluğu, ekran sınırlandırmaları ve ESC/dış tıklama ile kapanma etkileşimleri sağlandı.',
    'Antigravity AI',
    '2026-09-21 20:22:00',
    '2026-09-21 20:22:00'
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.25'
);
