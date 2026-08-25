CREATE TABLE IF NOT EXISTS `ya_business_operation_request` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `operation_type` varchar(64) NOT NULL COMMENT '操作类型',
  `source` varchar(32) NOT NULL COMMENT '来源端',
  `request_id` varchar(64) NOT NULL COMMENT '调用方幂等请求编号',
  `operator_type` varchar(32) NOT NULL COMMENT '操作人类型',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0处理中 1成功 2失败',
  `result_json` longtext COMMENT '兼容接口结果JSON',
  `error_message` varchar(500) DEFAULT NULL COMMENT '失败原因',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_operation_request` (`operation_type`,`source`,`request_id`),
  KEY `idx_operation_status_time` (`status`,`create_time`),
  KEY `idx_operation_operator` (`operator_type`,`operator_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='业务写操作幂等请求';

CREATE TABLE IF NOT EXISTS `ya_order_business_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `operation_request_id` bigint unsigned DEFAULT NULL COMMENT '幂等操作请求ID',
  `event_type` varchar(64) NOT NULL COMMENT '业务事件类型',
  `member_order_id` int(11) NOT NULL COMMENT '订单ID',
  `order_no` varchar(255) NOT NULL COMMENT '订单号',
  `before_status` tinyint(1) DEFAULT NULL COMMENT '操作前订单状态',
  `after_status` tinyint(1) DEFAULT NULL COMMENT '操作后订单状态',
  `before_pay_status` tinyint(1) DEFAULT NULL COMMENT '操作前支付状态',
  `after_pay_status` tinyint(1) DEFAULT NULL COMMENT '操作后支付状态',
  `before_refund_status` tinyint(1) DEFAULT NULL COMMENT '操作前售后状态',
  `after_refund_status` tinyint(1) DEFAULT NULL COMMENT '操作后售后状态',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '事件金额',
  `quantity` int(11) NOT NULL DEFAULT '0' COMMENT '事件数量',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `merchant_id` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `operator_type` varchar(32) NOT NULL COMMENT '操作人类型',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `source` varchar(32) NOT NULL COMMENT '来源端',
  `request_id` varchar(64) NOT NULL COMMENT '调用方幂等请求编号',
  `reason` varchar(500) DEFAULT NULL COMMENT '操作原因',
  `payload_json` longtext COMMENT '事件扩展JSON',
  `occurred_at` datetime NOT NULL COMMENT '业务发生时间',
  `create_time` datetime NOT NULL COMMENT '记录创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_request_order` (`event_type`,`source`,`request_id`,`member_order_id`),
  KEY `idx_event_order_time` (`member_order_id`,`occurred_at`,`id`),
  KEY `idx_event_order_no` (`order_no`,`occurred_at`,`id`),
  KEY `idx_event_merchant_time` (`merchant_id`,`occurred_at`,`id`),
  KEY `idx_event_type_time` (`event_type`,`occurred_at`,`id`),
  KEY `idx_event_operation` (`operation_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单业务事件流水';

-- 验证：两条查询均应返回 1，且 SHOW INDEX 应包含上述唯一键。
-- SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='ya_business_operation_request';
-- SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='ya_order_business_event';
-- 回滚前提：仅在尚未接入任何写入口且两表均为空时执行。
-- DROP TABLE IF EXISTS `ya_order_business_event`;
-- DROP TABLE IF EXISTS `ya_business_operation_request`;
