-- Sürüm Notu: v2.4.13 - Arka Plan Asenkron Tam Sistem Yedekleme İyileştirmesi
INSERT INTO version_notes (version_tag, title, category, description, author, created_at)
SELECT 
    'v2.4.13',
    'Arka Plan Asenkron Tam Sistem Yedekleme İyileştirmesi',
    'bugfix',
    '- Arka plan asenkron yedekleme (full backup) başlatıldığında log kaydının anında (in_progress) oluşturulması sağlandı.\n- Non-blocking HTTP Webhook ve CLI bağımsız daemonized tetikleme mekanizması güçlendirildi.\n- Tablo canlı takip (polling) ve otomatik durum güncelleme akışı optimize edildi.',
    'Antigravity AI',
    '2026-09-21 12:43:00'
WHERE NOT EXISTS (
    SELECT 1 FROM version_notes WHERE version_tag = 'v2.4.13' AND title = 'Arka Plan Asenkron Tam Sistem Yedekleme İyileştirmesi'
);
