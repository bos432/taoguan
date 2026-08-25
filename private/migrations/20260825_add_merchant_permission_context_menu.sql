INSERT INTO `ya_merchant_menu` (
  `menu_pid`, `menu_type`, `menu_name`, `menu_url`, `hidden`,
  `is_unlogin`, `is_unauth`, `is_unrate`, `is_disable`, `is_delete`, `create_time`
)
SELECT
  COALESCE((SELECT `menu_id` FROM `ya_merchant_menu`
    WHERE `menu_url` = 'merchant/system.UserCenter/info' AND `is_delete` = 0 LIMIT 1), 0),
  2, '统一权限上下文', 'merchant/system.UserCenter/permissionContext', 1,
  0, 0, 0, 0, 0, NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `ya_merchant_menu`
  WHERE `menu_url` = 'merchant/system.UserCenter/permissionContext' AND `is_delete` = 0
);

-- 验证：该 URL 应只有一条未删除记录，hidden=1、is_unlogin=0。
-- 回滚：DELETE FROM ya_merchant_menu WHERE menu_url='merchant/system.UserCenter/permissionContext';
