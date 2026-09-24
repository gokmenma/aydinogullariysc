-- Bakım modu ayarı ve bakım sırasında erişim yetkisi

INSERT INTO `settings` (`var`, `val`)
SELECT 'maintenance_mode', '0'
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `var` = 'maintenance_mode'
);

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'maintenance_access', 'Bakım Modunda Sisteme Erişim', 12, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `authority` WHERE `authName` = 'maintenance_access'
);

-- İlk kurulumda mevcut Super Admin rolünün kilitlenmesini önle.
-- Yetki daha sonra normal rol/yetki ekranından başka rollere de verilebilir.
INSERT INTO `userauths` (`roleID`, `authID`)
SELECT ur.id, a.id
FROM `userroles` ur
INNER JOIN `authority` a ON a.authName = 'maintenance_access'
WHERE LOWER(REPLACE(ur.roleName, ' ', '')) = 'superadmin'
  AND NOT EXISTS (
      SELECT 1
      FROM `userauths` ua
      WHERE ua.roleID = ur.id AND ua.authID = a.id
  );
