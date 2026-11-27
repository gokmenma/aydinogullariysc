CREATE OR REPLACE SQL SECURITY INVOKER VIEW `sqlvalidity_date` AS
select `x`.`report_id` AS `report_id`,`x`.`firma_adi` AS `firma_adi`,`x`.`report_number` AS `report_number`,`x`.`validity_date` AS `validity_date`,
case when `x`.`validity_date` like '%-%' then substring_index(substring_index(`x`.`validity_date`,'-',2),'-',-1)
     when `x`.`validity_date` like '%.%' then substring_index(substring_index(`x`.`validity_date`,'.',2),'.',-1)
     when `x`.`validity_date` like '%/%' then substring_index(substring_index(`x`.`validity_date`,'/',2),'/',-1)
     else NULL end AS `ay`,
case when `x`.`validity_date` like '%-%' then substring_index(`x`.`validity_date`,'-',-1)
     when `x`.`validity_date` like '%.%' then substring_index(`x`.`validity_date`,'.',-1)
     when `x`.`validity_date` like '%/%' then substring_index(`x`.`validity_date`,'/',-1)
     else NULL end AS `yil`
from (
  select `r`.`id` AS `report_id`,`c`.`company` AS `firma_adi`,`r`.`report_number` AS `report_number`,
  case when trim(coalesce(`r`.`validity_date`,'')) <> '' then trim(`r`.`validity_date`)
       when `r`.`report_type` in ('3','4') then date_format(date_add(str_to_date(replace(replace(trim(`r`.`control_date`),'.','-'),'/','-'),'%d-%m-%Y'), interval 1 year),'%d.%m.%Y')
       else `r`.`validity_date` end AS `validity_date`
  from (`reports` `r` left join `customers` `c` on(`c`.`id` = `r`.`customer_id`))
) `x`;
