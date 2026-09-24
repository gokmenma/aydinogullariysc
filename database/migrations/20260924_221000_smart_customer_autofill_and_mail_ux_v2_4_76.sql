-- Sürüm Notu Migration: E-Posta & Editör Kullanıcı Deneyimi İyileştirmeleri
-- Sürüm: v2.4.76
-- Tarih: 2026-09-24 22:10:00

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.76',
    'E-Posta Yönetimi, Editör Hizalaması ve Mükerrer E-Posta Uyarısı',
    'improvement',
    '- Alıcı Firma & E-Posta seçim kutularındaki (Select2 Multiple) placeholder metin taşması ve sığmama sorunu flex altyapısıyla giderildi.
- Zengin metin editörlerindeki (WYSIHTML5 / Summernote) placeholder ve yazı alanı butonlarla aynı hizaya gelecek şekilde (padding: 10px 14px) dengelendi.
- E-posta alıcı seçim ve arama listelerinde boş, hatalı veya nokta gibi geçersiz e-postaların listeye gelmesi engellendi (RFC e-posta doğrulaması uygulandı).
- Firma ekleme ve düzenleme ekranlarında aynı/şablon e-posta (örn: omerseckin@aydinogullari.com) girildiğinde kullanıcıyı bilgilendiren dinamik uyarı kutusu entegre edildi (kayda engel olmayacak şekilde yapılandırıldı).',
    'Antigravity AI',
    '2026-09-24 22:10:00'
ON DUPLICATE KEY UPDATE 
    `title` = VALUES(`title`),
    `description` = VALUES(`description`);
