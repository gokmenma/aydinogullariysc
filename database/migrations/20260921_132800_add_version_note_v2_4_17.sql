-- Sürüm Notu: Server-Side DataTables İstemci Tarafı ext.search Çakışması ve Kullanıcı Filtresi Düzeltmesi (v2.4.17)
INSERT INTO `version_notes` (`version_tag`, `title`, `description`, `category`, `author`, `created_at`)
SELECT 
    'v2.4.17',
    'Server-Side DataTables ext.search Çakışması ve Kullanıcı Filtresi Düzeltmesi',
    '- DataTables serverSide: true modunda çalışan tablolarda istemci taraflı $.fn.dataTable.ext.search filtresinin devre dışı bırakılması sağlanarak sunucudan dönen filtrelenmiş verilerin tarayıcıda elenmesi engellendi.\n- App/api/get-logs.php kullanıcı filtresi ve columnCounts sorguları sistem logları (author/user_id null/0) ve tüm kullanıcı adlarıyla tam uyumlu hale getirildi.\n- Cüneyt GÜLSÜN ve diğer tüm geçmiş kullanıcı aktivitelerinin filtre seçiminde anında listelenmesi sağlandı.',
    'bugfix',
    'Antigravity AI',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `version_notes` WHERE `version_tag` = 'v2.4.17'
);
