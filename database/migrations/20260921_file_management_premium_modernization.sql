-- Dosya Yönetimi ve Dosya Kategorileri Premium Tema Modernizasyonu
-- Migration Date: 2026-09-21

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`) 
VALUES (
    'Dosya Yönetimi ve Kategorileri Premium Tema Modernizasyonu',
    'v2.5.3',
    'feature',
    '- Dosya Yönetimi (`all-files.php`) sayfası kurumsal premium-theme tasarımına geçirildi; KPI kartları (toplam dosya, depolama boyutu, kategori sayısı, son 30 gün), kategori filtre hapları ve renkli dosya türü ikonları (PDF, Word, Excel, Görsel, Arşiv) eklendi.\n- Yeni dosya yükleme işlemi sürükle-bırak (drag & drop) ve dosya seçici destekli modern bir modal penceresine taşındı.\n- Eski `new-file.php` sayfası iptal edilerek all-files modal açılışına yönlendirildi ve sol menüden kaldırıldı.\n- Dosya Kategorileri (`file-categories.php`) sayfasındaki hatalı yetki kontrolü (`sercategory`) giderilerek dosya yetkileriyle uyumlu hale getirildi ve premium temaya uyarlandı.\n- Kategoriler için modal ile ekleme/düzenleme desteği ve ilişkili dosya kontrolüyle güvenli silme yapısı entegre edildi.\n- Dosya ve kategori silme işlemleri ajax.php ve SweetAlert2 (`deleteRecord`) ile dinamik hale getirildi, fiziksel dosya temizliği sağlandı.',
    'Antigravity AI',
    '2026-09-21 08:20:00'
);
