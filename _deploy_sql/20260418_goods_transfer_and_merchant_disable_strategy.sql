SET @goods_menu_pid := (
    SELECT `menu_id`
    FROM `ya_system_menu`
    WHERE `menu_url` = 'admin/goods.Goods/list'
    LIMIT 1
);

SET @next_menu_id := (
    SELECT IFNULL(MAX(`menu_id`), 0) + 1
    FROM `ya_system_menu`
);

INSERT INTO `ya_system_menu` (
    `menu_id`, `menu_pid`, `menu_type`, `menu_name`, `menu_url`,
    `path`, `name`, `component`, `meta_icon`, `meta_query`,
    `hidden`, `keep_alive`, `always_show`, `sort`,
    `is_unlogin`, `is_unauth`, `is_unrate`, `is_disable`, `is_delete`,
    `create_uid`, `update_uid`, `delete_uid`,
    `create_time`, `update_time`, `delete_time`
)
SELECT
    @next_menu_id, @goods_menu_pid, 2, '批量迁移到平台自营', 'admin/goods.Goods/transferToPlatform',
    '', '', '', '', '',
    0, 1, 0, 250,
    0, 0, 0, 0, 0,
    0, 0, 0,
    NOW(), NULL, NULL
FROM DUAL
WHERE @goods_menu_pid IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `ya_system_menu`
      WHERE `menu_url` = 'admin/goods.Goods/transferToPlatform'
  );

INSERT INTO `ya_system_menu` (
    `menu_id`, `menu_pid`, `menu_type`, `menu_name`, `menu_url`,
    `path`, `name`, `component`, `meta_icon`, `meta_query`,
    `hidden`, `keep_alive`, `always_show`, `sort`,
    `is_unlogin`, `is_unauth`, `is_unrate`, `is_disable`, `is_delete`,
    `create_uid`, `update_uid`, `delete_uid`,
    `create_time`, `update_time`, `delete_time`
)
SELECT
    @next_menu_id + 1, @goods_menu_pid, 2, '批量迁移到指定商家', 'admin/goods.Goods/transferToMerchant',
    '', '', '', '', '',
    0, 1, 0, 250,
    0, 0, 0, 0, 0,
    0, 0, 0,
    NOW(), NULL, NULL
FROM DUAL
WHERE @goods_menu_pid IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `ya_system_menu`
      WHERE `menu_url` = 'admin/goods.Goods/transferToMerchant'
  );
