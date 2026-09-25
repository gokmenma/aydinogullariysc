-- Version Note for v2.4.89
INSERT INTO version_notes (title, version_tag, category, description, author, created_at)
SELECT 
    'YAS, OYS ve AAS Rapor Sayfaları Premium Tema ve Select2 Dönüşümü',
    'v2.4.89',
    'feature',
    '- Yangın Algılama Sistemi Kontrol Raporu (reports/yas/report-new-yas), Otomatik Yangın Söndürme Raporu (reports/oys/report-new-oys) ve Acil Aydınlatma Kontrol Raporu (reports/aas/report-new-aas) sayfaları Premium Tema tasarım standartlarına kavuşturuldu.\n- Sayfalardaki tüm seçim alanları (Firma, Mühendis/Personel, İş Emri, Sistem Sınıfı, Önceki Kontroller vb.) Select2 bileşenine dönüştürüldü.\n- Zengin metin editörü (WYSIWYG) alanlarındaki çift metin kutusu görünümü düzeltilerek arka plandaki ham textarea gizlendi ve temiz editör arayüzü sağlandı.\n- Sekmeli navigasyon (custom-report-pills), modern form kartları (form-card), kompakt ve şık ekipman/dosya tabloları (premium-table) ve dinamik sayaç rozetleri entegre edildi.\n- SweetAlert2 bildirimleri ve tam kapsamlı Dark Mode uyumluluğu sağlandı.\n- OYS ve AAS kayıt numaralandırma ve veritabanı akışları standartlaştırıldı.',
    'Antigravity AI',
    '2026-09-25 14:15:00'
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    updated_at = NOW();
