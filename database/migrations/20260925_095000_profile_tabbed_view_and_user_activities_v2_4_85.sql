-- Sürüm Notu: Profil Sayfası Sekmeli Yapı & Sistem Aktiviteleri
-- Sürüm: v2.4.85
-- Tarih: 2026-09-25 09:50:00

INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.85',
    'Profil Sayfası Sekmeli Yapı ve Sistem Aktiviteleri',
    '- Profil sayfası modern sekmeli (tabbed) yapıya kavuşturuldu.\n- Güvenlik / Şifre sekmesi altına şifre değiştirme formu, canlı şifre gücü göstergesi ve güvenlik / oturum bilgileri eklendi.\n- Sistem Aktiviteleri sekmesi eklenerek kullanıcının sistem üzerinde gerçekleştirdiği tüm işlemler standart DataTable (sıralama, arama, sayfa boyutu, hızlı filtreler) yapısıyla entegre edildi.\n- 4\'lü özet KPI kartları (Toplam Hareket, Bugünkü İşlemler, Başarılı Girişler, Veri Değişiklikleri) ve daraltma standardı uygulandı.\n- ActivityLogModel sınıfına kullanıcıya özel aktivite sorgulama ve istatistik metotları eklendi.',
    'feature',
    'Antigravity AI',
    '2026-09-25 09:50:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.85'
);
