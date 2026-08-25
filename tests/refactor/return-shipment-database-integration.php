<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\member\MemberOrderModel;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\member\MemberOrderService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$delivery = Db::name('setting_delivery')->where('is_disable', 0)->where('is_delete', 0)->find();
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)->find();
if (!$delivery || !$candidate) {
    throw new RuntimeException('No delivery or order for return shipment integration');
}
$before = $candidate->toArray();
$orderId = intval($before['id']);
$requestId = 'integration:return-shipment:' . bin2hex(random_bytes(5));
$trackingNo = 'RETURN-' . bin2hex(random_bytes(4));
$param = [
    'member_id' => intval($before['member_id']),
    'refund_delivery_id' => intval($delivery['id']),
    'refund_express' => $trackingNo,
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'uniapp-weixin',
        'operator_type' => 'member',
        'operator_id' => intval($before['member_id']),
        'reason' => 'integration return shipment',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Return shipment integration failed: {$message}");
    }
};

Db::startTrans();
try {
    MemberOrderModel::where('id', $orderId)->update([
        'status' => 5,
        'refund_type' => 2,
        'refund_status' => 2,
        'refund_price' => '12.34',
    ]);
    $logBefore = Db::name('member_order_log')->where('member_order_id', $orderId)
        ->where('title', '买家已发货')->count();

    $assert(MemberOrderService::returnGoods([$orderId], $param) === true, 'return shipment keeps compatibility return');
    $after = MemberOrderModel::where('id', $orderId)->find()->toArray();
    $assert(intval($after['status']) === 5 && intval($after['refund_status']) === 2, 'return shipment preserves service state');
    $assert($after['refund_express'] === $trackingNo, 'return tracking number persisted');
    $assert(intval($after['refund_delivery_id']) === intval($delivery['id']), 'return delivery company persisted');
    $assert($after['refund_express_name'] === $delivery['title'] && $after['refund_express_code'] === $delivery['code'], 'return delivery metadata persisted');
    $assert(Db::name('member_order_log')->where('member_order_id', $orderId)->where('title', '买家已发货')->count() === $logBefore + 1, 'legacy return log persisted');
    $event = OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::RETURN_SHIPPED)->find();
    $assert($event !== null, 'return shipment event persisted');
    $payload = json_decode(strval($event['payload_json'] ?? ''), true) ?: [];
    $assert(($payload['refund_express'] ?? '') === $trackingNo, 'event payload includes return tracking');

    $assert(MemberOrderService::returnGoods([$orderId], $param) === true, 'duplicate return shipment returns true');
    $assert(Db::name('member_order_log')->where('member_order_id', $orderId)->where('title', '买家已发货')->count() === $logBefore + 1, 'duplicate creates no legacy log');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $orderId)->find()->toArray();
$assert(intval($restored['status']) === intval($before['status']), 'order rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');

echo "Return shipment integration passed: {$assertions} assertions\n";
