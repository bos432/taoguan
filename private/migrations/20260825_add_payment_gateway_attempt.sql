CREATE TABLE IF NOT EXISTS `ya_payment_gateway_attempt` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `operation_request_id` bigint unsigned DEFAULT NULL COMMENT '业务操作请求ID',
  `business_type` varchar(32) NOT NULL COMMENT '业务类型：refund等',
  `provider` varchar(32) NOT NULL COMMENT '支付提供方',
  `merchant_request_no` varchar(64) NOT NULL COMMENT '商户侧请求编号',
  `provider_transaction_id` varchar(128) DEFAULT NULL COMMENT '提供方交易编号',
  `member_order_id` int(11) NOT NULL COMMENT '订单ID',
  `order_no` varchar(255) NOT NULL COMMENT '订单号',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '原支付金额',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本次金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0准备 1请求中 2成功 3失败 4结果未知',
  `attempt_count` int(11) NOT NULL DEFAULT '0' COMMENT '调用次数',
  `request_json` longtext COMMENT '脱敏请求参数',
  `response_json` longtext COMMENT '提供方响应',
  `error_message` varchar(500) DEFAULT NULL COMMENT '错误或补偿说明',
  `requested_at` datetime DEFAULT NULL COMMENT '最近请求时间',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_gateway_request` (`provider`,`business_type`,`merchant_request_no`),
  KEY `idx_gateway_order` (`member_order_id`,`business_type`,`id`),
  KEY `idx_gateway_status_time` (`status`,`update_time`,`id`),
  KEY `idx_gateway_operation` (`operation_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='外部支付网关调用记录';

-- 验证：返回 1，且 SHOW INDEX 包含 uniq_gateway_request。
-- SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='ya_payment_gateway_attempt';
-- 回滚前提：仅在未接入真实网关且表为空时执行。
-- DROP TABLE IF EXISTS `ya_payment_gateway_attempt`;
