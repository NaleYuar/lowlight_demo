-- Schema for lowlight_demo_db

CREATE DATABASE IF NOT EXISTS lowlight_demo_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lowlight_demo_db;

CREATE TABLE `images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orig_name`   VARCHAR(255) NOT NULL,  -- 上傳時的原始檔名
  `stored_name` VARCHAR(255) NOT NULL,  -- 影像增亮後的檔名
  `created_at`  DATETIME NOT NULL,      -- 上傳與影像增亮完成的時間 
  `psnr` DOUBLE DEFAULT NULL,           -- 指標：PSNR
  `ssim` DOUBLE DEFAULT NULL,           -- 指標：SSIM
  `l1`   DOUBLE DEFAULT NULL,           -- 指標：L1
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
