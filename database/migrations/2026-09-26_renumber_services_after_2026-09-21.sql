-- 21.09.2026 tarihinden sonraki servisleri kayıt sırasına göre yeniden numaralandırır.
-- Tekrar çalıştırılabilir; pregdate ve id sırası değişmediği sürece aynı sonucu üretir.
START TRANSACTION;

SET @cutoff_date = '2026-09-22 00:00:00';
SET @renumber_batch = REPLACE(UUID(), '-', '');

SET @previous_service_number = (
    SELECT CAST(SUBSTRING(`service_number`, 4) AS UNSIGNED)
    FROM `projects`
    WHERE `pregdate` < @cutoff_date
      AND `service_number` REGEXP '^SRV[0-9]+$'
    ORDER BY `pregdate` DESC, `id` DESC
    LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS `tmp_service_renumber`;

CREATE TEMPORARY TABLE `tmp_service_renumber` (
    `sequence_no` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `new_service_number` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`sequence_no`),
    UNIQUE KEY `uq_project_id` (`project_id`)
);

INSERT INTO `tmp_service_renumber` (`project_id`)
SELECT `id`
FROM `projects`
WHERE `pregdate` >= @cutoff_date
ORDER BY `pregdate` ASC, `id` ASC;

UPDATE `tmp_service_renumber`
SET `new_service_number` = CONCAT(
    'SRV',
    LPAD(COALESCE(@previous_service_number, 0) + `sequence_no`, 5, '0')
);

UPDATE `projects` AS p
INNER JOIN `tmp_service_renumber` AS r ON r.`project_id` = p.`id`
SET p.`service_number` = CONCAT('__REN_', @renumber_batch, '_', p.`id`);

UPDATE `projects` AS p
INNER JOIN `tmp_service_renumber` AS r ON r.`project_id` = p.`id`
SET p.`service_number` = r.`new_service_number`;

UPDATE `define_numbers`
SET `service` = COALESCE(@previous_service_number, 0)
    + (SELECT COUNT(*) FROM `tmp_service_renumber`)
    + 1;

DROP TEMPORARY TABLE IF EXISTS `tmp_service_renumber`;

COMMIT;
