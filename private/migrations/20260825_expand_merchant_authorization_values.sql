ALTER TABLE `ya_merchant_authorization_log`
  MODIFY COLUMN `before_value` int unsigned NOT NULL DEFAULT 0,
  MODIFY COLUMN `after_value` int unsigned NOT NULL DEFAULT 0;

-- 验证：information_schema.columns 中 before_value、after_value 类型应为 int unsigned。
-- 回滚说明：不建议缩回 tinyint，会员 ID 可能超过 255；回滚应用代码不需要回滚本兼容性扩展。
