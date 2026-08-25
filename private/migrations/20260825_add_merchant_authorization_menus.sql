INSERT INTO `ya_system_menu` (
  `menu_pid`, `menu_type`, `menu_name`, `menu_url`, `hidden`,
  `is_unlogin`, `is_unauth`, `is_unrate`, `is_disable`, `is_delete`, `create_time`
)
SELECT parent.`menu_id`, 2, item.`menu_name`, item.`menu_url`, 1,
  0, 0, 0, 0, 0, NOW()
FROM `ya_system_menu` parent
JOIN (
  SELECT '绑定/解绑商家会员' AS `menu_name`, 'admin/merchant.Merchant/memberBind' AS `menu_url`
  UNION ALL SELECT '授予/取消商家超管', 'admin/merchant.Merchant/memberSuper'
  UNION ALL SELECT '查看商家授权记录', 'admin/merchant.Merchant/authorizationLogs'
) item
WHERE parent.`menu_url` = 'admin/merchant.Merchant/list'
  AND parent.`is_delete` = 0
  AND NOT EXISTS (
    SELECT 1 FROM `ya_system_menu` existing
    WHERE existing.`menu_url` = item.`menu_url` AND existing.`is_delete` = 0
  );

-- 验证：三个 URL 各有一条未删除记录，menu_pid 指向商家管理，hidden=1、is_unlogin=0。
-- 本迁移不写 system_role_menus，普通角色必须在角色菜单中显式分配。
-- 回滚：先确认 system_role_menus 无关联，再按上述三个 menu_url 删除。
