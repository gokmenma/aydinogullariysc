CREATE TABLE IF NOT EXISTS `report_alarms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `days_before` smallint(6) NOT NULL DEFAULT 30,
  `include_overdue` tinyint(1) NOT NULL DEFAULT 1,
  `latest_only` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_alarms_user` (`user_id`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `report_alarm_customers` (
  `alarm_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  PRIMARY KEY (`alarm_id`, `customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `report_alarm_types` (
  `alarm_id` int(11) NOT NULL,
  `report_type_id` int(11) NOT NULL,
  PRIMARY KEY (`alarm_id`, `report_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `authority` (`authName`, `authTitle`, `authGroup`, `authValue`, `isActive`)
SELECT 'report_alarm', 'Rapor Hatırlatma Alarmı', 16, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `authority` WHERE `authName` = 'report_alarm');

INSERT INTO `userauths` (`roleID`, `authID`)
SELECT DISTINCT ua.roleID, alarm.id
FROM `userauths` ua
INNER JOIN `authority` edit_auth ON edit_auth.id = ua.authID AND edit_auth.authName = 'reportedit'
INNER JOIN `authority` alarm ON alarm.authName = 'report_alarm'
WHERE NOT EXISTS (
    SELECT 1 FROM `userauths` x WHERE x.roleID = ua.roleID AND x.authID = alarm.id
);
