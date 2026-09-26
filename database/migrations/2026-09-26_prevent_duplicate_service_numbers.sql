-- Dış veri aktarımlarından sonra servis sayacını mevcut en yüksek numarayla eşitle.
UPDATE `define_numbers` AS dn
CROSS JOIN (
    SELECT COALESCE(MAX(CAST(SUBSTRING(`service_number`, 4) AS UNSIGNED)), 0) + 1 AS next_service
    FROM `projects`
    WHERE `service_number` REGEXP '^SRV[0-9]+$'
) AS current_numbers
SET dn.`service` = GREATEST(dn.`service`, current_numbers.next_service);

-- Mevcut mükerrer kayıtlar korunur; bundan sonraki mükerrer ekleme ve numara değişiklikleri engellenir.
DROP TRIGGER IF EXISTS `projects_service_number_before_insert`;
DROP TRIGGER IF EXISTS `projects_service_number_before_update`;

DELIMITER $$

CREATE TRIGGER `projects_service_number_before_insert`
BEFORE INSERT ON `projects`
FOR EACH ROW
BEGIN
    IF NEW.`service_number` IS NOT NULL
       AND TRIM(NEW.`service_number`) <> ''
       AND EXISTS (
           SELECT 1
           FROM `projects`
           WHERE `service_number` = NEW.`service_number`
       ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'duplicate service number';
    END IF;
END$$

CREATE TRIGGER `projects_service_number_before_update`
BEFORE UPDATE ON `projects`
FOR EACH ROW
BEGIN
    IF NOT (NEW.`service_number` <=> OLD.`service_number`)
       AND NEW.`service_number` IS NOT NULL
       AND TRIM(NEW.`service_number`) <> ''
       AND EXISTS (
           SELECT 1
           FROM `projects`
           WHERE `service_number` = NEW.`service_number`
             AND `id` <> OLD.`id`
       ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'duplicate service number';
    END IF;
END$$

DELIMITER ;
