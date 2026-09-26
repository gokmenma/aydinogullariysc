-- Güvenlik sertleştirmesi: modern parola alanı, giriş sınırlama ve güvenlik olayları.
SET @password_column_type := (
    SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password'
    LIMIT 1
);
SET @alter_password_sql := IF(
    @password_column_type IS NOT NULL AND @password_column_type <> 'varchar(255)',
    'ALTER TABLE users MODIFY password VARCHAR(255) NULL',
    'SELECT 1'
);
PREPARE alter_password_stmt FROM @alter_password_sql;
EXECUTE alter_password_stmt;
DEALLOCATE PREPARE alter_password_stmt;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    identity_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    was_successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempt_lookup (identity_hash, ip_address, attempted_at),
    KEY idx_login_attempt_cleanup (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(64) NOT NULL,
    user_id INT NULL,
    endpoint VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    context_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_security_event_type_date (event_type, created_at),
    KEY idx_security_event_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
