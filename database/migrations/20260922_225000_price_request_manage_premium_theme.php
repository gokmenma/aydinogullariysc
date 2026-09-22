<?php

use App\Model\VersionNoteModel;

return [
    'name' => '20260922_225000_price_request_manage_premium_theme',
    'description' => 'Fiyat Talebi Yönetimi (purchases/price-request-manage) Premium Tema Modernizasyonu',
    'up' => function ($db) {
        $noteModel = new VersionNoteModel();
        
        $exists = $db->query("SELECT id FROM version_notes WHERE version_tag = 'v2.4.40' AND title = 'Fiyat Talebi Yönetimi Premium Tema Modernizasyonu'")->fetch();
        
        if (!$exists) {
            $noteModel->save([
                'title' => 'Fiyat Talebi Yönetimi Premium Tema Modernizasyonu',
                'version_tag' => 'v2.4.40',
                'category' => 'improvement',
                'description' => "- Fiyat Talebi Ekleme ve Düzenleme (`purchases/price-request-manage.php`) sayfası kurumsal `premium-theme` standartlarına taşındı.\n- Sayfa başlığı modern gradient hero banner (`pricereq-header-card`), dinamik talep numarası rozeti, PDF/Yazdır, Listeye Dön ve Kaydet aksiyon butonlarıyla yenilendi.\n- Form kartı `form-card` stiliyle iki kolonlu ergonomik yerleşime kavuşturuldu; firma seçimi, hızlı firma ekleme butonu, termin tarihi seçicisi ve döviz kurları modernize edildi.\n- Canlı hesaplanan Ara Toplam ve Genel Toplam için modern KPI özet kartları entegre edildi.\n- Talep Edilen Ürünler tablosu `premium-table` mimarisine geçirilerek sürükle-bırak (Sortable) sıralama, dinamik satır ekleme/silme, ürün arama modalı ve dosya eki (Resim/Excel/PDF) önizleme ve güvenli silme mekanizmaları ile donatıldı.\n- Alt ürünler tablosundaki yatay kaydırma çubuğu (horizontal scrollbar) kaldırıldı; sütun genişlikleri, input boyutları ve tablo yerleşimi tam sayfa genişliğine sığacak şekilde optimize edildi.\n- Koyu tema (Dark Mode) desteği ve SweetAlert2 geri bildirimleri tam uyumlu hale getirildi.",
                'author' => 'Antigravity AI',
                'created_at' => '2026-09-22 22:50:00'
            ]);
        }
        return true;
    }
];
