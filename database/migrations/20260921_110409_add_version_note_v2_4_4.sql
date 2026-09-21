-- Sürüm Notu: DataTable Başlık Filtrelerinde Tüm Veri Havuzunu Kapsayan Dinamik Seçenek Desteği
-- Tarih: 2026-09-21 11:04:09
-- Sürüm: v2.4.4

INSERT INTO `version_notes` (`title`, `version_tag`, `category`, `description`, `author`, `created_at`)
SELECT 
    'DataTable Başlık Filtrelerinde Tüm Veri Havuzunu Kapsayan Dinamik Seçenek Desteği',
    'v2.4.4',
    'improvement',
    '- Sayfalanmış veya sunucu taraflı (serverSide) tablolarda filtre seçeneklerinin sadece görüntülenen sayfayla sınırlı kalması sorunu giderildi.\n- Sistem genelindeki bilinen domain seçenekleri (KNOWN_COLUMN_OPTIONS: Durum, Sözleşme, Muhasebe, Modül, vb.) ve sayfa formlarındaki filtre kaynakları otomatik olarak seçenek havuzuna dahil edildi.\n- DataTables AJAX yanıtları (xhr.dt) ve sayfa geçişlerindeki tüm satırlar otomatik taranarak (harvestRowsFromData) seçenek havuzuna eklendi.\n- Mevcut sayfada eşleşmeyen ancak sistem genelinde bulunan seçenekler için zarif sıfır durumu rozeti (-) eklendi.',
    'Antigravity AI',
    '2026-09-21 11:04:09'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.4' AND `title` = 'DataTable Başlık Filtrelerinde Tüm Veri Havuzunu Kapsayan Dinamik Seçenek Desteği'
);
