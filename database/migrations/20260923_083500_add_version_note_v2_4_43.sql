-- Sürüm Notu: v2.4.43 - Teklif ve Servis Yönetimi Başlık Butonları Okunabilirlik ve Yerleşim Optimizasyonu
INSERT INTO version_notes (version_tag, title, category, description, author, created_at)
SELECT 
    'v2.4.43',
    'Teklif ve Servis Yönetimi Başlık Butonları Okunabilirlik ve Yerleşim Optimizasyonu',
    'improvement',
    '- offers/offer-manage sayfasında ve service/manage sayfasında yerel CSS bloğu içindeki eski ve çakışan .offer-header-card / .service-header-card ve .btn-header kuralları kaldırıldı.
- Beyaz arkaplan üzerinde beyaz metin ve ikonların görünmemesine yol açan stil ezmesi (color: #fff !important) giderildi.
- Başlık kartı butonları (TL\'ye Çevir, Servis Oluştur, Excel, Göster, Listeye Dön) merkezi premium tema standartlarına (premium-theme.css) bağlanarak metin ve ikon kontrastı net ve okunabilir hale getirildi.
- Teklif yönetim ekranında (offer-manage) ilk işlem butonları (TL\'ye Çevir, Servis Oluştur, Excel, Göster) başlık bölümünün sol tarafına taşındı; Listeye Dön ve Kaydet butonları sağ tarafta konumlandırıldı.',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.43'
);
