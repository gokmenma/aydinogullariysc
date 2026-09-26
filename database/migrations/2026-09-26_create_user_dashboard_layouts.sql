-- Migration: 2026-09-26_create_user_dashboard_layouts.sql
-- Description: Kullanıcı bazlı özelleştirilebilir ana sayfa kart (widget) yerleşim tablosu

CREATE TABLE IF NOT EXISTS `user_dashboard_layouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `layout_data` longtext NOT NULL COMMENT 'JSON formatında widget konfigürasyonu, koordinatları ve görünürlük durumları',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_dashboard_layout` (`user_id`),
  KEY `idx_user_dashboard_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
