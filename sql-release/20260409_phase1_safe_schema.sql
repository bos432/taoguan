-- Phase 1 safe schema patch
-- Target: keep existing production data untouched, only add missing tables/columns.
-- Run on production after full backup.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Admin user preference table
CREATE TABLE IF NOT EXISTS `ya_system_user_preference` (
  `preference_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Preference ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Admin User ID',
  `preference_group` varchar(32) NOT NULL DEFAULT 'ui' COMMENT 'Preference Group',
  `preference_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'Preference Key',
  `preference_value` longtext NULL COMMENT 'Preference Value',
  `value_type` varchar(16) NOT NULL DEFAULT 'json' COMMENT 'Value Type',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'Remark',
  `create_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Created By',
  `update_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Updated By',
  `create_time` datetime DEFAULT NULL COMMENT 'Create Time',
  `update_time` datetime DEFAULT NULL COMMENT 'Update Time',
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `uk_user_group_key` (`user_id`,`preference_group`,`preference_key`),
  KEY `idx_preference_group` (`preference_group`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Admin user preference';

-- 2) Member preference table
CREATE TABLE IF NOT EXISTS `ya_member_preference` (
  `preference_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Preference ID',
  `member_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Member ID',
  `preference_group` varchar(32) NOT NULL DEFAULT 'ui' COMMENT 'Preference Group',
  `preference_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'Preference Key',
  `preference_value` longtext NULL COMMENT 'Preference Value',
  `value_type` varchar(16) NOT NULL DEFAULT 'json' COMMENT 'Value Type',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'Remark',
  `create_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Created By',
  `update_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Updated By',
  `create_time` datetime DEFAULT NULL COMMENT 'Create Time',
  `update_time` datetime DEFAULT NULL COMMENT 'Update Time',
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `uk_member_group_key` (`member_id`,`preference_group`,`preference_key`),
  KEY `idx_member_group` (`member_id`,`preference_group`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Member preference';

-- 3) Merchant user preference table
CREATE TABLE IF NOT EXISTS `ya_merchant_user_preference` (
  `preference_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Preference ID',
  `mer_user_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Merchant User ID',
  `mer_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Merchant ID',
  `preference_group` varchar(32) NOT NULL DEFAULT 'ui' COMMENT 'Preference Group',
  `preference_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'Preference Key',
  `preference_value` longtext NULL COMMENT 'Preference Value',
  `value_type` varchar(16) NOT NULL DEFAULT 'json' COMMENT 'Value Type',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'Remark',
  `create_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Created By',
  `update_uid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Updated By',
  `create_time` datetime DEFAULT NULL COMMENT 'Create Time',
  `update_time` datetime DEFAULT NULL COMMENT 'Update Time',
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `uk_mer_user_group_key` (`mer_user_id`,`preference_group`,`preference_key`),
  KEY `idx_mer_id` (`mer_id`),
  KEY `idx_update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Merchant user preference';

-- 4) Merchant member super flag
SET @db_name = DATABASE();

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_merchant'
    AND COLUMN_NAME = 'merchant_purchase_daily_quantity_limit'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_merchant` ADD COLUMN `merchant_purchase_daily_quantity_limit` int(11) NULL DEFAULT NULL COMMENT ''Merchant daily purchase quantity limit''',
  'SELECT ''skip merchant_purchase_daily_quantity_limit''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_merchant'
    AND COLUMN_NAME = 'merchant_purchase_daily_amount_limit'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_merchant` ADD COLUMN `merchant_purchase_daily_amount_limit` decimal(10,2) NULL DEFAULT NULL COMMENT ''Merchant daily purchase amount limit''',
  'SELECT ''skip merchant_purchase_daily_amount_limit''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_merchant'
    AND COLUMN_NAME = 'member_is_super'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_merchant` ADD COLUMN `member_is_super` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Bound member is merchant super admin'' AFTER `member_id`',
  'SELECT ''skip member_is_super''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Notice popup fields
SET @db_name = DATABASE();

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_enabled'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Popup enabled'' AFTER `type`',
  'SELECT ''skip popup_enabled''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_frequency'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_frequency` varchar(16) NOT NULL DEFAULT ''once'' COMMENT ''Popup frequency'' AFTER `popup_enabled`',
  'SELECT ''skip popup_frequency''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_scope'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_scope` varchar(32) NOT NULL DEFAULT ''all'' COMMENT ''Popup scope'' AFTER `popup_frequency`',
  'SELECT ''skip popup_scope''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_sort'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_sort` int NOT NULL DEFAULT 250 COMMENT ''Popup sort'' AFTER `popup_scope`',
  'SELECT ''skip popup_sort''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_button_text'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_button_text` varchar(64) NOT NULL DEFAULT ''查看详情'' COMMENT ''Popup button text'' AFTER `popup_sort`',
  'SELECT ''skip popup_button_text''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_jump_type'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_jump_type` varchar(16) NOT NULL DEFAULT ''detail'' COMMENT ''Popup jump type'' AFTER `popup_button_text`',
  'SELECT ''skip popup_jump_type''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'popup_jump_value'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `popup_jump_value` varchar(255) NOT NULL DEFAULT '''' COMMENT ''Popup jump value'' AFTER `popup_jump_type`',
  'SELECT ''skip popup_jump_value''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'ya_setting_notice'
    AND COLUMN_NAME = 'content_type'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ya_setting_notice` ADD COLUMN `content_type` varchar(16) NOT NULL DEFAULT ''html'' COMMENT ''Content type'' AFTER `popup_jump_value`',
  'SELECT ''skip content_type''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
