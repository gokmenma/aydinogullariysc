-- Migration: Add Version Note v2.4.10
-- Description: Raporlar Dolum Listesi ve Kontrol Listesi SQL Görünümlerinin (VIEW) Geri Yüklenmesi ve Hata Çözümü

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'Raporlar Dolum ve Kontrol Listesi SQL Görünümleri Onarımı',
    'v2.4.10',
    'bugfix',
    '- Veritabanı aktarımı sonrası eksik kalan sqlsonkullanmatarihi ve sqlvalidity_date SQL görünümleri (VIEW) oluşturuldu.
- Farklı tarih formatlarındaki (GG/AA/YYYY, GG-AA-YYYY, GG.AA.YYYY, AA/YYYY vb.) kayıtların ay ve yıl bazlı filtrelemeye doğru dahil edilmesi sağlandı.
- Raporlar altındaki Dolum Listesi ve Kontrol Listesi sayfalarının sorunsuz açılması ve hızlı çalışması sağlandı.',
    'Antigravity AI',
    '2026-09-21 12:12:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `title` = 'Raporlar Dolum ve Kontrol Listesi SQL Görünümleri Onarımı'
);
