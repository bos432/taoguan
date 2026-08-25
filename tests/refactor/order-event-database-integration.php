<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\operation\BusinessOperationRequestService;
use app\common\service\order\OrderBusinessEventService;
use think\facade\Db;

$app = new think\App();
$app->initialize();

$requestId = 'integration:' . date('YmdHis') . ':' . bin2hex(random_bytes(3));
$context = [
    'request_id' => $requestId,
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 1,
    'reason' => 'refactor integration test',
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order event database integration failed: {$message}");
    }
};

Db::startTrans();
try {
    $operation = BusinessOperationRequestService::begin('payment.voucher_approve', $context);
    $duplicate = BusinessOperationRequestService::begin('payment.voucher_approve', $context);
    $assert($operation['duplicate'] === false, 'first operation acquires request');
    $assert($duplicate['duplicate'] === true, 'duplicate operation reuses request');
    $assert((int) $duplicate['id'] === (int) $operation['id'], 'duplicate operation returns same id');

    $before = ['id' => 900000001, 'order_no' => 'REF-INTEGRATION-001', 'status' => 0, 'pay_status' => 0, 'refund_status' => 0, 'member_id' => 1, 'merchant_id' => 1];
    $after = array_merge($before, Policy::after(Policy::VOUCHER_APPROVED));
    $eventId = OrderBusinessEventService::record(Policy::VOUCHER_APPROVED, $before, $after, $context, [
        'operation_request_id' => $operation['id'],
        'amount' => '2998.00',
        'quantity' => 1,
    ]);
    $assert($eventId > 0, 'event inserted');
    $assert(OrderBusinessEventModel::where('id', $eventId)->value('after_status') === 4, 'event state persisted');

    $duplicateRejected = false;
    try {
        OrderBusinessEventService::record(Policy::VOUCHER_APPROVED, $before, $after, $context, [
            'operation_request_id' => $operation['id'],
            'amount' => '2998.00',
            'quantity' => 1,
        ]);
    } catch (Throwable) {
        $duplicateRejected = true;
    }
    $assert($duplicateRejected, 'duplicate order event rejected by database');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'only one event remains');

    BusinessOperationRequestService::complete((int) $operation['id'], ['event_id' => $eventId]);
    $assert(BusinessOperationRequestModel::where('id', $operation['id'])->value('status') === 1, 'operation completed');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation transaction rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event transaction rolled back');

echo "Order event database integration passed: {$assertions} assertions\n";
