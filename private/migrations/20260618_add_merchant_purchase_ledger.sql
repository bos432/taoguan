CREATE TABLE IF NOT EXISTS `ya_merchant_purchase_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `is_disable` tinyint(1) DEFAULT '0' COMMENT '是否禁用，1是0否',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除，1是0否',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '修改时间',
  `member_order_id` int(11) NOT NULL COMMENT '订单ID',
  `member_order_detailed_id` int(11) NOT NULL COMMENT '订单明细ID',
  `order_no` varchar(255) DEFAULT NULL COMMENT '订单号',
  `buyer_member_id` int(11) DEFAULT NULL COMMENT '买家会员ID',
  `buyer_merchant_id` int(11) DEFAULT '0' COMMENT '买方商家ID',
  `buyer_merchant_title` varchar(255) DEFAULT NULL COMMENT '买方商家名称快照',
  `source_type` varchar(20) DEFAULT 'platform' COMMENT '来源类型：platform平台 merchant商家',
  `source_merchant_id` int(11) DEFAULT '0' COMMENT '来源商家ID，0为平台',
  `source_merchant_title` varchar(255) DEFAULT NULL COMMENT '来源名称快照',
  `goods_id` int(11) DEFAULT NULL COMMENT '原商品ID',
  `goods_title` varchar(255) DEFAULT NULL COMMENT '商品名称快照',
  `goods_code` varchar(255) DEFAULT NULL COMMENT '商品编码快照',
  `goods_spec` varchar(255) DEFAULT NULL COMMENT '规格快照',
  `goods_unit` varchar(255) DEFAULT NULL COMMENT '单位快照',
  `quantity` int(11) DEFAULT '0' COMMENT '购买数量',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '购买单价',
  `total` decimal(10,2) DEFAULT '0.00' COMMENT '明细金额',
  `order_pay_price` decimal(10,2) DEFAULT '0.00' COMMENT '订单实付金额',
  `pay_type` tinyint(1) DEFAULT NULL COMMENT '支付方式',
  `pay_time` datetime DEFAULT NULL COMMENT '确认支付时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_order_detail` (`member_order_detailed_id`),
  KEY `idx_buyer_pay_time` (`buyer_merchant_id`,`pay_time`),
  KEY `idx_source_pay_time` (`source_merchant_id`,`pay_time`),
  KEY `idx_order` (`member_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='商家采购财务来源流水';

INSERT IGNORE INTO `ya_merchant_purchase_ledger` (
  `is_disable`,
  `is_delete`,
  `create_time`,
  `update_time`,
  `member_order_id`,
  `member_order_detailed_id`,
  `order_no`,
  `buyer_member_id`,
  `buyer_merchant_id`,
  `buyer_merchant_title`,
  `source_type`,
  `source_merchant_id`,
  `source_merchant_title`,
  `goods_id`,
  `goods_title`,
  `goods_code`,
  `goods_spec`,
  `goods_unit`,
  `quantity`,
  `price`,
  `total`,
  `order_pay_price`,
  `pay_type`,
  `pay_time`
)
SELECT
  0,
  0,
  NOW(),
  NOW(),
  o.id,
  d.id,
  o.order_no,
  o.member_id,
  bm.id,
  bm.title,
  CASE WHEN IFNULL(g.merchant_id, 0) > 0 THEN 'merchant' ELSE 'platform' END,
  IFNULL(g.merchant_id, 0),
  CASE WHEN IFNULL(g.merchant_id, 0) > 0 THEN IFNULL(sm.title, '') ELSE '平台自营' END,
  d.goods_id,
  IFNULL(g.title, ''),
  IFNULL(g.code, ''),
  IFNULL(g.spec, ''),
  IFNULL(g.unit, ''),
  IFNULL(d.quantity, 0),
  IFNULL(d.price, 0),
  IFNULL(d.total, 0),
  IFNULL(o.pay_price, 0),
  o.pay_type,
  o.pay_time
FROM `ya_member_order` o
INNER JOIN `ya_member_order_detailed` d ON d.member_order_id = o.id
INNER JOIN `ya_merchant` bm ON bm.member_id = o.member_id AND bm.is_delete = 0 AND bm.auth_state = 1
LEFT JOIN `ya_goods` g ON g.id = d.goods_id
LEFT JOIN `ya_merchant` sm ON sm.id = g.merchant_id
WHERE o.is_delete = 0
  AND o.is_disable = 0
  AND o.pay_status = 1
  AND o.pay_time IS NOT NULL;
