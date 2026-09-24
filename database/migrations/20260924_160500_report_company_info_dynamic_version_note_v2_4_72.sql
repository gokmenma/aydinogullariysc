-- Sürüm Notu: Rapor ve Tekliflerde Dinamik Firma Bilgileri ve Başlık Hizalaması

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT
    'v2.4.72',
    'Rapor ve Tekliflerde Dinamik ve Dengeli Firma Başlıkları',
    'improvement',
    '- Tüp test deney raporu ve ilgili doküman başlıklarındaki sabit firma bilgileri ayarlar (settings) tablosuna bağlandı.\n- Teklif, rapor ve satın alma formlarında uzun firma unvanlarının üst bilgide dengeli ve orantılı iki satıra bölünmesini sağlayan format_company_header_title fonksiyonu eklendi.',
    'Antigravity AI',
    '2026-09-24 16:05:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.72'
);
