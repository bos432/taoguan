<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\member\MemberOrderModel;
use app\common\service\order\OrderBusinessEventService;
use app\common\service\order\OrderTimelineQueryService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$candidate = MemberOrderModel::where('is_delete', 0)->field('id,order_no,status,pay_status,refund_status,total_num,pay_price,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No order for timeline integration');
}
$order = $candidate->toArray();
$requestId = 'integration:timeline:' . bin2hex(random_bytes(5));
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order timeline integration failed: {$message}");
    }
};

$historical = OrderTimelineQueryService::byOrderId((int) $order['id']);
$assert(in_array($historical['coverage'], ['legacy_only', 'hybrid', 'event'], true), 'historical coverage declared');
$assert((int) $historical['order']['id'] === (int) $order['id'], 'order summary returned');

Db::startTrans();
try {
    $after = array_merge($order, ['status' => 7, 'pay_status' => 2]);
    OrderBusinessEventService::record(Policy::VOUCHER_REJECTED, $order, $after, [
        'request_id' => $requestId, 'source' => 'admin-next', 'operator_type' => 'platform_admin',
        'operator_id' => 1, 'reason' => 'timeline integration',
    ], ['amount' => 0, 'quantity' => (int) $order['total_num'], 'occurred_at' => '2026-08-25 12:00:00']);
    $timeline = OrderTimelineQueryService::byOrderId((int) $order['id']);
    $event = array_values(array_filter($timeline['events'], static fn(array $row): bool => $row['request_id'] === $requestId))[0] ?? null;
    $assert($event !== null, 'new event returned');
    $assert($event['before_status_title'] !== '' && $event['after_status_title'] === '取消', 'status titles returned');
    $assert($timeline['coverage'] === (empty($timeline['legacy_logs']) ? 'event' : 'hybrid'), 'coverage reflects both sources');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('order_business_event')->where('request_id', $requestId)->count() === 0, 'timeline test rolled back');

echo "Order timeline integration passed: {$assertions} assertions\n";
