<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order transition characterization failed: {$message}");
    }
};

$assert(count(Policy::transitions()) === 14, 'all current transitions are registered');
$assert(Policy::canApply(Policy::WECHAT_PAID, ['status' => 0, 'pay_status' => 0]), 'pending order accepts WeChat payment');
$assert(!Policy::canApply(Policy::WECHAT_PAID, ['status' => 4, 'pay_status' => 1]), 'paid order rejects duplicate WeChat transition');
$assert(Policy::after(Policy::WECHAT_PAID) === ['status' => 1, 'pay_status' => 1], 'WeChat payment goes to pending shipment');
$assert(Policy::after(Policy::VOUCHER_APPROVED) === ['status' => 4, 'pay_status' => 1], 'voucher approval keeps current direct-complete behavior');
$assert(Policy::after(Policy::VOUCHER_REJECTED) === ['status' => 7, 'pay_status' => 2], 'voucher rejection cancels order');
$assert(Policy::after(Policy::CANCELED) === ['is_delete' => 1], 'buyer cancellation keeps current soft-delete behavior');
$assert(Policy::canApply(Policy::DELIVERED, ['status' => 1]), 'pending shipment can be delivered');
$assert(Policy::canApply(Policy::PICKED_UP, ['status' => 1]), 'pending shipment can be picked up');
$assert(!Policy::canApply(Policy::PICKED_UP, ['status' => 2]), 'shipped order cannot use pickup transition');
$assert(Policy::canApply(Policy::SERVICE_REQUESTED, ['status' => 4]), 'completed order can request service');
$assert(Policy::canApply(Policy::RETURN_SHIPPED, ['status' => 5, 'refund_status' => 2]), 'approved return can submit shipping');
$assert(!Policy::canApply(Policy::RETURN_SHIPPED, ['status' => 5, 'refund_status' => 1]), 'pending review cannot submit return shipping');
$assert(Policy::after(Policy::RETURN_SHIPPED) === ['status' => 5, 'refund_status' => 2], 'return shipping preserves service state');
$assert(Policy::canApply(Policy::REFUNDED, ['status' => 5, 'refund_status' => 2]), 'approved service can complete refund');
$assert(!Policy::canApply('unknown', ['status' => 0]), 'unknown event is rejected');

echo "Order transition characterization passed: {$assertions} assertions\n";
