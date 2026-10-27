INSERT INTO userauths (roleID, authID)
SELECT r.id, a.id
FROM userroles r
INNER JOIN authority a ON a.authName = 'userview'
WHERE r.id IN (11, 12)
  AND NOT EXISTS (
    SELECT 1 FROM userauths ua WHERE ua.roleID = r.id AND ua.authID = a.id
  );
