<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\support\refactor\SqlSnapshotAudit;

$fixture = tempnam(sys_get_temp_dir(), 'snapshot-audit-');
if ($fixture === false) {
    throw new RuntimeException('Cannot create test fixture');
}

$sql = <<<'SQL'
CREATE TABLE `ya_member_order` (
  `id` int(11) NOT NULL,
  `is_delete` tinyint(1) DEFAULT '0',
  `order_no` varchar(255) DEFAULT NULL,
  `pay_price` decimal(10,2) DEFAULT '0.00',
  `pay_status` tinyint(1) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `refund_status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
INSERT INTO `ya_member_order` VALUES (1,0,'A\'1',12.34,1,4,0),(2,0,'B2',0.01,0,0,0);
CREATE TABLE `ya_member_order_detailed` (`id` int(11), `member_order_id` int(11), `quantity` int(11), `total` decimal(10,2)) ENGINE=InnoDB;
INSERT INTO `ya_member_order_detailed` VALUES (10,1,2,12.34),(11,999,1,1.00);
CREATE TABLE `ya_member_order_log` (`id` int(11), `is_delete` tinyint(1), `member_order_id` int(11), `role_type` tinyint(1)) ENGINE=InnoDB;
CREATE TABLE `ya_merchant` (`id` int(11), `is_delete` tinyint(1), `auth_state` tinyint(1), `member_id` int(11), `member_is_super` tinyint(1)) ENGINE=InnoDB;
CREATE TABLE `ya_member` (`member_id` int(11), `is_delete` tinyint(1), `is_disable` tinyint(1), `is_super` tinyint(1)) ENGINE=InnoDB;
CREATE TABLE `ya_goods` (`id` int(11), `is_delete` tinyint(1), `status` tinyint(1), `stock` int(11), `merchant_id` int(11)) ENGINE=InnoDB;
CREATE TABLE `ya_merchant_purchase_ledger` (`id` int(11), `is_delete` tinyint(1), `member_order_id` int(11), `member_order_detailed_id` int(11), `buyer_merchant_id` int(11), `source_type` varchar(20), `quantity` int(11), `total` decimal(10,2)) ENGINE=InnoDB;
SQL;

file_put_contents($fixture, $sql);
$report = (new SqlSnapshotAudit())->audit($fixture);
unlink($fixture);

$assertions = [
    [$report['diagnostics']['table_counts']['ya_member_order'] === 2, 'order count'],
    [$report['diagnostics']['orders']['paid_pay_price'] === '12.34', 'paid amount'],
    [$report['diagnostics']['orders']['status_distribution'] === [['value' => '0', 'count' => 1], ['value' => '4', 'count' => 1]], 'status distribution'],
    [$report['diagnostics']['order_details']['quantity'] === 3, 'detail quantity'],
    [$report['diagnostics']['order_details']['missing_order_count'] === 1, 'missing order reference'],
    [$report['schema']['ya_member_order']['columns'][2]['name'] === 'order_no', 'schema columns'],
];

foreach ($assertions as [$passed, $label]) {
    if (!$passed) {
        throw new RuntimeException("Snapshot audit assertion failed: {$label}");
    }
}

echo 'SQL snapshot audit passed: ' . count($assertions) . " assertions\n";
