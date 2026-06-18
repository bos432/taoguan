-- Phase 1 verification SQL
-- Run before and after deployment.

SHOW TABLES LIKE 'ya_system_user_preference';
SHOW TABLES LIKE 'ya_member_preference';
SHOW TABLES LIKE 'ya_merchant_user_preference';

SHOW COLUMNS FROM `ya_merchant` LIKE 'merchant_purchase_daily_quantity_limit';
SHOW COLUMNS FROM `ya_merchant` LIKE 'merchant_purchase_daily_amount_limit';
SHOW COLUMNS FROM `ya_merchant` LIKE 'member_is_super';

SHOW COLUMNS FROM `ya_setting_notice`;

SELECT COUNT(*) AS menu_setting_count
FROM `ya_system_menu`
WHERE `menu_url` LIKE '%system.Setting%'
   OR `path` LIKE '%system/setting%'
   OR `menu_name` LIKE '%系统设置%';

SELECT COUNT(*) AS merchant_count FROM `ya_merchant`;
SELECT COUNT(*) AS goods_count FROM `ya_goods`;
SELECT COUNT(*) AS member_count FROM `ya_member`;

-- Optional manual checks
-- 1) Confirm production admin user can open 系统设置
-- 2) Confirm popup notice module can load without unknown column errors
-- 3) Confirm admin/merchant preference APIs no longer report missing table errors
