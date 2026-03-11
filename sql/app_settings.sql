CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
