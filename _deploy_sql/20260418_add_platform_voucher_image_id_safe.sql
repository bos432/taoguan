SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @db_name = DATABASE();

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_system_setting'
    AND COLUMN_NAME = 'platform_voucher_image_id'
);

SET @sql = IF(
  @col_exists = 0,
  'ALTER TABLE `ya_system_setting` ADD COLUMN `platform_voucher_image_id` int(11) DEFAULT NULL COMMENT ''平台收款码'' AFTER `service_wechat_image_id`',
  'SELECT ''skip platform_voucher_image_id'''
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
