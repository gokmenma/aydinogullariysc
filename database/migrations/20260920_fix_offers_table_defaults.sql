-- Teklifler (offers) tablosundaki varsayılan değeri olmayan alanların düzeltilmesi
-- Tarih: 2026-09-20

ALTER TABLE `offers`
    MODIFY COLUMN `mycompany` VARCHAR(150) NULL DEFAULT '',
    MODIFY COLUMN `authors` VARCHAR(50) NULL DEFAULT '',
    MODIFY COLUMN `tax` INT(7) NULL DEFAULT 0,
    MODIFY COLUMN `notes` TEXT NULL DEFAULT NULL,
    MODIFY COLUMN `dollar` FLOAT NULL DEFAULT 0,
    MODIFY COLUMN `euro` FLOAT NULL DEFAULT 0,
    MODIFY COLUMN `subdescription` VARCHAR(255) NULL DEFAULT '',
    MODIFY COLUMN `currency` VARCHAR(30) NULL DEFAULT 'TRY',
    MODIFY COLUMN `statu` INT(11) NULL DEFAULT 1,
    MODIFY COLUMN `creativer` INT(11) NULL DEFAULT NULL;
