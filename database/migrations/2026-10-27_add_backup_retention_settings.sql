INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_retention_remote_daily_days', '14' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_retention_remote_daily_days');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_retention_weekly_weeks', '8' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_retention_weekly_weeks');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_retention_monthly_months', '12' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_retention_monthly_months');

INSERT INTO `settings` (`var`, `val`)
SELECT 'backup_retention_min_keep', '5' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `var` = 'backup_retention_min_keep');
