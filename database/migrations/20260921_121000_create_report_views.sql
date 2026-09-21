-- Migration: Create Report Views (sqlsonkullanmatarihi and sqlvalidity_date)
-- Date: 2026-09-21 12:10:00
-- Description: Raporlar modülündeki Dolum Listesi ve Kontrol Listesi sayfalarının ihtiyaç duyduğu SQL görünümlerinin (VIEW) oluşturulması.

-- 1. Cihaz Dolum Listesi Görünümü (sqlsonkullanmatarihi)
CREATE OR REPLACE VIEW `sqlsonkullanmatarihi` AS
SELECT 
    ryc.report_id,
    r.report_number,
    c.company AS firma_adi,
    ryc.bulundugu_bolge,
    ryc.cihaz_no,
    CASE 
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%/%/%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '/', 2), '/', -1), 2, '0')
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%-%-%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '-', 2), '-', -1), 2, '0')
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%.%.%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '.', 2), '.', -1), 2, '0')
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%/%' THEN LPAD(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '/', 1), 2, '0')
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%.%' AND ryc.cihaz_sonkullanma_tarihi NOT LIKE '.%' THEN LPAD(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '.', 1), 2, '0')
        WHEN ryc.cihaz_sonkullanma_tarihi LIKE '%-%' THEN LPAD(SUBSTRING_INDEX(ryc.cihaz_sonkullanma_tarihi, '-', 1), 2, '0')
        WHEN LENGTH(TRIM(ryc.cihaz_sonkullanma_tarihi)) = 6 AND TRIM(ryc.cihaz_sonkullanma_tarihi) REGEXP '^[0-1][0-9]20[0-9]{2}$' THEN LEFT(TRIM(ryc.cihaz_sonkullanma_tarihi), 2)
        ELSE NULL
    END AS ay,
    CASE 
        WHEN (ryc.cihaz_sonkullanma_tarihi LIKE '%/%' OR ryc.cihaz_sonkullanma_tarihi LIKE '%.%' OR ryc.cihaz_sonkullanma_tarihi LIKE '%-%') AND RIGHT(TRIM(ryc.cihaz_sonkullanma_tarihi), 4) REGEXP '^[1-2][0-9]{3}$' THEN RIGHT(TRIM(ryc.cihaz_sonkullanma_tarihi), 4)
        WHEN LENGTH(TRIM(ryc.cihaz_sonkullanma_tarihi)) = 6 AND TRIM(ryc.cihaz_sonkullanma_tarihi) REGEXP '^[0-1][0-9]20[0-9]{2}$' THEN RIGHT(TRIM(ryc.cihaz_sonkullanma_tarihi), 4)
        ELSE NULL
    END AS yil
FROM report_ysc_content ryc
LEFT JOIN reports r ON r.id = ryc.report_id
LEFT JOIN customers c ON c.id = r.customer_id;

-- 2. Rapor Kontrol ve Geçerlilik Listesi Görünümü (sqlvalidity_date)
CREATE OR REPLACE VIEW `sqlvalidity_date` AS
SELECT 
    r.id AS report_id,
    r.report_number,
    c.company AS firma_adi,
    r.validity_date,
    CASE 
        WHEN r.validity_date LIKE '%/%/%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(r.validity_date, '/', 2), '/', -1), 2, '0')
        WHEN r.validity_date LIKE '%-%-%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(r.validity_date, '-', 2), '-', -1), 2, '0')
        WHEN r.validity_date LIKE '%.%.%' THEN LPAD(SUBSTRING_INDEX(SUBSTRING_INDEX(r.validity_date, '.', 2), '.', -1), 2, '0')
        WHEN r.validity_date LIKE '%/%' THEN LPAD(SUBSTRING_INDEX(r.validity_date, '/', 1), 2, '0')
        WHEN r.validity_date LIKE '%.%' AND r.validity_date NOT LIKE '.%' THEN LPAD(SUBSTRING_INDEX(r.validity_date, '.', 1), 2, '0')
        WHEN r.validity_date LIKE '%-%' THEN LPAD(SUBSTRING_INDEX(r.validity_date, '-', 1), 2, '0')
        WHEN LENGTH(TRIM(r.validity_date)) = 6 AND TRIM(r.validity_date) REGEXP '^[0-1][0-9]20[0-9]{2}$' THEN LEFT(TRIM(r.validity_date), 2)
        ELSE NULL
    END AS ay,
    CASE 
        WHEN (r.validity_date LIKE '%/%' OR r.validity_date LIKE '%.%' OR r.validity_date LIKE '%-%') AND RIGHT(TRIM(r.validity_date), 4) REGEXP '^[1-2][0-9]{3}$' THEN RIGHT(TRIM(r.validity_date), 4)
        WHEN LENGTH(TRIM(r.validity_date)) = 6 AND TRIM(r.validity_date) REGEXP '^[0-1][0-9]20[0-9]{2}$' THEN RIGHT(TRIM(r.validity_date), 4)
        ELSE NULL
    END AS yil
FROM reports r
LEFT JOIN customers c ON c.id = r.customer_id;
