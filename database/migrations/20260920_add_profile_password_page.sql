INSERT INTO pages (p_title, p_link, pid)
SELECT 'Profil Güvenliği', 'profile', 0
WHERE NOT EXISTS (
    SELECT 1 FROM pages WHERE p_link = 'profile'
);
