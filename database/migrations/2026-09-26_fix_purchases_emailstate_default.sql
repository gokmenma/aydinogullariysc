-- Satın alma ve satın alma kalemleri tablolarında varsayılan değer tanımları
-- strict mode (SQL 1364) hatalarını önlemek için emailState ve stokKodu kolonlarına varsayılan değer atanması ve siparisNo boyutunun genişletilmesi

ALTER TABLE `purchases` 
    MODIFY COLUMN `emailState` VARCHAR(1) NOT NULL DEFAULT '0',
    MODIFY COLUMN `siparisNo` VARCHAR(50) NULL DEFAULT '',
    MODIFY COLUMN `payment_date` VARCHAR(50) NULL DEFAULT '';

ALTER TABLE `purchase_items` 
    MODIFY COLUMN `stokKodu` VARCHAR(50) NULL DEFAULT '';
