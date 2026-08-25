CREATE TABLE IF NOT EXISTS `ya_merchant_authorization_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operation_request_id` bigint unsigned DEFAULT NULL,
  `merchant_id` int NOT NULL,
  `member_id` int NOT NULL DEFAULT 0,
  `authorization_type` varchar(32) NOT NULL,
  `before_value` tinyint NOT NULL DEFAULT 0,
  `after_value` tinyint NOT NULL DEFAULT 0,
  `operator_type` varchar(32) NOT NULL,
  `operator_id` int NOT NULL DEFAULT 0,
  `source` varchar(32) NOT NULL,
  `request_id` varchar(64) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_merchant_authorization_request` (`authorization_type`,`source`,`request_id`,`merchant_id`),
  KEY `idx_merchant_authorization_time` (`merchant_id`,`authorization_type`,`create_time`),
  KEY `idx_merchant_authorization_operator` (`operator_type`,`operator_id`,`create_time`),
  KEY `idx_merchant_authorization_operation` (`operation_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家高风险授权审计日志';

-- 验证：information_schema.tables 中应存在 ya_merchant_authorization_log，且包含上述唯一键。
-- 回滚前提：仅在尚未接入真实授权且表为空时执行。
-- DROP TABLE IF EXISTS `ya_merchant_authorization_log`;
