-- Schema for lowlight_demo_db

CREATE DATABASE IF NOT EXISTS lowlight_demo_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lowlight_demo_db;

CREATE TABLE IF NOT EXISTS `images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `original_name` VARCHAR(255) NOT NULL,              -- 上傳時的原始檔名
    `stored_name` VARCHAR(64) NOT NULL,                 -- uploads 與 outputs 共用的系統檔名
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- 紀錄建立時間
    `brightness_before_pct` DECIMAL(6,3) DEFAULT NULL, -- 增亮前亮度（%）
    `brightness_after_pct` DECIMAL(6,3) DEFAULT NULL,  -- 增亮後亮度（%）
    `contrast_before_pct` DECIMAL(6,3) DEFAULT NULL,   -- 增亮前對比（%）
    `contrast_after_pct` DECIMAL(6,3) DEFAULT NULL,    -- 增亮後對比（%）
    `image_width_px` SMALLINT UNSIGNED DEFAULT NULL,   -- 圖片寬度（px）
    `image_height_px` SMALLINT UNSIGNED DEFAULT NULL,  -- 圖片高度（px）
    `original_size_kb` DECIMAL(10,2) DEFAULT NULL,     -- 原始圖片大小（KB）
    `enhanced_size_kb` DECIMAL(10,2) DEFAULT NULL,     -- 增亮圖片大小（KB）
    `processing_ms` INT UNSIGNED DEFAULT NULL,         -- 增亮處理時間（毫秒）

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_images_stored_name` (`stored_name`),
    KEY `idx_images_created_at` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;