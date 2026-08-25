<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\member\MemberOrderService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$memberId = intval(Db::name('member')->alias('m')
    ->leftJoin('merchant mer', 'mer.member_id=m.member_id AND mer.is_delete=0')
    ->whereNull('mer.id')->where('m.is_delete', 0)->value('m.member_id'));
$goods = Db::name('goods')->where('is_disable', 0)->where('is_delete', 0)
    ->where('status', 1)->where('stock', '>', 0)->where('merchant_id', '>', 0)->find();
if ($memberId <= 0 || !$goods) {
    throw new RuntimeException('No member or goods for order creation integration');
}
$requestId = 'integration:order-create:' . bin2hex(random_bytes(5));
$param = [
    'member_id' => $memberId,
    'pay_type' => 2,
    'delivery_type' => 2,
    'self_name' => 'Integration Member',
    'self_phone' => '13800138000',
    'merchant_list' => [[
        'id' => intval($goods['merchant_id']),
        'goods' => [[
            'id' => intval($goods['id']),
            'title' => strval($goods['title']),
            'cart_num' => 1,
            'price' => strval($goods['price']),
        ]],
    ]],
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'uniapp-weixin',
        'operator_type' => 'member',
        'operator_id' => $memberId,
        'reason' => 'integration order create',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order creation integration failed: {$message}");
    }
};
$orderCountBefore = Db::name('member_order')->count();
$stockBefore = intval($goods['stock']);

Db::startTrans();
try {
    $assert(MemberOrderService::confirmOrder($param) === true, 'voucher creation keeps compatibility return');
    $event = OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::CREATED)->find();
    $assert($event !== null, 'order created event persisted');
    $orderId = intval($event['member_order_id']);
    $order = Db::name('member_order')->where('id', $orderId)->find();
    $assert($order !== null && intval($order['status']) === 0 && intval($order['pay_status']) === 0, 'pending order persisted');
    $assert(intval($order['pay_type']) === 2 && intval($order['member_id']) === $memberId, 'voucher member order persisted');
    $assert(Db::name('member_order_detailed')->where('member_order_id', $orderId)->where('goods_id', intval($goods['id']))->count() === 1, 'order detail persisted');
    $assert(Db::name('member_order_log')->where('member_order_id', $orderId)->where('title', '订单生成')->count() === 1, 'legacy creation log persisted');
    $assert(intval(Db::name('goods')->where('id', intval($goods['id']))->value('stock')) === $stockBefore - 1, 'goods stock decremented');
    $operation = BusinessOperationRequestModel::where('request_id', $requestId)->find();
    $assert($operation !== null && intval($operation['status']) === 1, 'creation operation completed');

    $assert(MemberOrderService::confirmOrder($param) === true, 'duplicate creation replays true');
    $assert(Db::name('member_order')->count() === $orderCountBefore + 1, 'duplicate creates no order');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate creates no event');
    $assert(intval(Db::name('goods')->where('id', intval($goods['id']))->value('stock')) === $stockBefore - 1, 'duplicate does not decrement stock');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$assert(Db::name('member_order')->count() === $orderCountBefore, 'orders rolled back');
$assert(intval(Db::name('goods')->where('id', intval($goods['id']))->value('stock')) === $stockBefore, 'stock rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');

echo "Order creation integration passed: {$assertions} assertions\n";
