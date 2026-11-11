CREATE OR REPLACE SQL SECURITY INVOKER VIEW `sqlvalidity_date` AS
select `r`.`id` AS `report_id`,`c`.`company` AS `firma_adi`,`r`.`report_number` AS `report_number`,`r`.`validity_date` AS `validity_date`,
case when `r`.`validity_date` like '%-%' then substring_index(substring_index(`r`.`validity_date`,'-',2),'-',-1)
     when `r`.`validity_date` like '%.%' then substring_index(substring_index(`r`.`validity_date`,'.',2),'.',-1)
     when `r`.`validity_date` like '%/%' then substring_index(substring_index(`r`.`validity_date`,'/',2),'/',-1)
     else NULL end AS `ay`,
case when `r`.`validity_date` like '%-%' then substring_index(`r`.`validity_date`,'-',-1)
     when `r`.`validity_date` like '%.%' then substring_index(`r`.`validity_date`,'.',-1)
     when `r`.`validity_date` like '%/%' then substring_index(`r`.`validity_date`,'/',-1)
     else NULL end AS `yil`
from (`reports` `r` left join `customers` `c` on(`c`.`id` = `r`.`customer_id`));

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `sqlsonkullanmatarihi` AS
select `ryc`.`id` AS `content_id`,`r`.`id` AS `report_id`,`c`.`company` AS `firma_adi`,`r`.`report_number` AS `report_number`,`ryc`.`cihaz_no` AS `cihaz_no`,`ryc`.`bulundugu_bolge` AS `bulundugu_bolge`,`ryc`.`cihaz_sonkullanma_tarihi` AS `cihaz_sonkullanma_tarihi`,
case when `ryc`.`cihaz_sonkullanma_tarihi` like '%/%' then substring_index(`ryc`.`cihaz_sonkullanma_tarihi`,'/',1)
     when `ryc`.`cihaz_sonkullanma_tarihi` like '%.%' then substring_index(`ryc`.`cihaz_sonkullanma_tarihi`,'.',1)
     else NULL end AS `ay`,
case when `ryc`.`cihaz_sonkullanma_tarihi` like '%/%' then substring_index(`ryc`.`cihaz_sonkullanma_tarihi`,'/',-1)
     when `ryc`.`cihaz_sonkullanma_tarihi` like '%.%' then substring_index(`ryc`.`cihaz_sonkullanma_tarihi`,'.',-1)
     else NULL end AS `yil`
from ((`report_ysc_content` `ryc` left join `reports` `r` on(`r`.`id` = `ryc`.`report_id`)) left join `customers` `c` on(`c`.`id` = `r`.`customer_id`));
