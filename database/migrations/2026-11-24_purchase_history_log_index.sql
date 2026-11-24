ALTER TABLE `logs`
    ADD INDEX IF NOT EXISTS `idx_logs_module_entity` (`module`(40), `entity_type`(40), `entity_id`(40));
