-- Migration: 20260924_082000_version_note_v2_4_61.sql
-- Sürüm Notu: Aktif Sayfalama (Pagination) Elemanı Renginin Aktif Tema ile Uyumlulaştırılması

INSERT INTO `version_notes` (`version_tag`, `title`, `category`, `description`, `author`, `created_at`)
SELECT 
    'v2.4.61',
    'Aktif Sayfalama Renginin Tema ile Uyumlulaştırılması',
    'improvement',
    '- Tablolardaki ve genel DataTables sayfalama (pagination) bileşenlerindeki aktif sayfa numarasının sabit mavi (#0284c7) rengi, seçili aktif tema rengiyle dinamik olarak uyumlu hale getirildi.\n- 17 farklı hazır tema presetine (KODE, Ersan Gold, Zümrüt, Kraliyet Moru, Rose, Gün Batımı vb.) ve dark mode ayarlarına tam entegre çalışan CSS tema değişkenleri (--theme-primary, --theme-primary-shadow) tanımlandı.\n- Sistem Aktiviteleri (Loglar) ve tüm DataTables sayfalamalarında aktif sayfa elemanının arka planı, kenarlık ve gölge tonları aktif temayla senkronize edildi.',
    'Antigravity AI',
    '2026-09-24 08:20:00'
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.61'
);
