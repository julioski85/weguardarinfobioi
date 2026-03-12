ALTER TABLE daily_reports
    ADD COLUMN recommendation_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER totalplay_count,
    ADD COLUMN radio_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER recommendation_count;
