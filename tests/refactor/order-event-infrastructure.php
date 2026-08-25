<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\order\OrderBusinessEventService;
use app\common\service\operation\BusinessOperationRequestService;

$root = dirname(__DIR__, 2);
$sql = file_get_contents($root . '/private/migrations/20260825_add_business_operation_and_order_event.sql');
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order event infrastructure failed: {$message}");
    }
};

$assert(is_string($sql) && str_contains($sql, 'CREATE TABLE IF NOT EXISTS `ya_business_operation_request`'), 'idempotency table is additive');
$assert(str_contains($sql, 'UNIQUE KEY `uniq_operation_request` (`operation_type`,`source`,`request_id`)'), 'operation request uniqueness');
$assert(str_contains($sql, 'CREATE TABLE IF NOT EXISTS `ya_order_business_event`'), 'event table is additive');
$assert(str_contains($sql, 'UNIQUE KEY `uniq_event_request_order` (`event_type`,`source`,`request_id`,`member_order_id`)'), 'event uniqueness supports batch orders');
foreach (['event_type', 'order_no', 'before_status', 'after_status', 'amount', 'quantity', 'member_id', 'merchant_id', 'operator_type', 'operator_id', 'source', 'request_id', 'reason', 'occurred_at'] as $field) {
    $assert(str_contains($sql, "`{$field}`"), "migration contains {$field}");
}
$assert(str_contains($sql, '回滚前提'), 'migration includes rollback instructions');

$payload = OrderBusinessEventService::payload(
    'payment.voucher_approved',
    ['id' => 12, 'order_no' => 'T001', 'status' => 0, 'pay_status' => 0, 'member_id' => 9, 'merchant_id' => 3],
    ['id' => 12, 'order_no' => 'T001', 'status' => 4, 'pay_status' => 1, 'member_id' => 9, 'merchant_id' => 3],
    ['request_id' => 'test:voucher:001', 'source' => 'admin-next', 'operator_type' => 'platform_admin', 'operator_id' => 2, 'reason' => 'approved'],
    ['amount' => '2998.00', 'quantity' => 1, 'occurred_at' => '2026-08-25 10:00:00']
);
$assert($payload['before_status'] === 0 && $payload['after_status'] === 4, 'before and after states retained');
$assert($payload['amount'] === '2998.00' && $payload['quantity'] === 1, 'amount and quantity retained');
$assert($payload['request_id'] === 'test:voucher:001' && $payload['operator_id'] === 2, 'operation context retained');

$operation = BusinessOperationRequestService::payload('payment.voucher_approve', [
    'request_id' => 'test:voucher:001',
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 2,
]);
$assert($operation['operation_type'] === 'payment.voucher_approve' && $operation['status'] === 0, 'operation starts in processing state');
$assert($operation['source'] === 'admin-next' && $operation['request_id'] === 'test:voucher:001', 'operation idempotency key retained');

try {
    OrderBusinessEventService::payload('x', ['id' => 1, 'order_no' => 'T'], ['id' => 1, 'order_no' => 'T'], ['request_id' => '', 'source' => 'legacy', 'operator_type' => 'system']);
    $assert(false, 'empty request id rejected');
} catch (InvalidArgumentException) {
    $assert(true, 'empty request id rejected');
}

echo "Order event infrastructure passed: {$assertions} assertions\n";
