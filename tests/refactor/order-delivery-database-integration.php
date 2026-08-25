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
$detail = Db::name('member_order_detailed')->where('goods_id', '>', 0)->where('quantity', '>', 0)->find();
if (!$delivery || !$detail) {
    throw new RuntimeException('No delivery or order detail for delivery integration');
}
$candidate = MemberOrderModel::where('id', intval($detail['member_order_id']))->find();
if (!$candidate) {
    throw new RuntimeException('No order for delivery integration');
}
$before = $candidate->toArray();
$orderId = intval($before['id']);
$requestId = 'integration:delivery:' . bin2hex(random_bytes(5));
$context = [
    'request_id' => $requestId,
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 1,
    'reason' => 'integration delivery',
];
$param = [
    'setting_delivery_id' => intval($delivery['id']),
    'kuaidi_order_no' => 'DELIVERY-' . bin2hex(random_bytes(4)),
    '_operation_context' => $context,
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order delivery integration failed: {$message}");
    }
};

Db::startTrans();
try {
    MemberOrderModel::where('id', $orderId)->update(['status' => 1, 'pay_status' => 1]);
    $details = Db::name('member_order_detailed')->where('member_order_id', $orderId)->select()->toArray();
    foreach ($details as $row) {
        $available = intval(Db::name('goods_inventory')->where('goods_id', intval($row['goods_id']))
            ->where('is_disable', 0)->where('is_delete', 0)->sum('warehousing_num'));
        Db::name('goods_inventory')->insert([
            'goods_id' => intval($row['goods_id']),
            'merchant_id' => intval(Db::name('goods')->where('id', intval($row['goods_id']))->value('merchant_id')),
            'warehousing_num' => abs($available) + intval($row['quantity']) + 10,
            'inventory_type' => 1,
            'is_disable' => 0,
            'is_delete' => 0,
            'create_uid' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }
    $outboundBefore = Db::name('goods_inventory')->where('member_order_id', $orderId)
        ->where('inventory_type', 2)->count();
    $logBefore = Db::name('member_order_log')->where('member_order_id', $orderId)
        ->where('title', '订单发货')->count();

    $assert(MemberOrderService::delivery([$orderId], $param) === true, 'delivery keeps compatibility return');
    $after = MemberOrderModel::where('id', $orderId)->find()->toArray();
    $assert(intval($after['status']) === 2, 'delivery moves order to pending receipt');
    $assert($after['kuaidi_order_no'] === $param['kuaidi_order_no'], 'tracking number persisted');
    $assert(intval($after['setting_delivery_id']) === intval($delivery['id']), 'delivery company persisted');
    $assert($after['delivery_name'] === $delivery['title'] && $after['delivery_code'] === $delivery['code'], 'delivery metadata persisted');
    $assert(Db::name('goods_inventory')->where('member_order_id', $orderId)->where('inventory_type', 2)->count() > $outboundBefore, 'inventory outbound persisted');
    $assert(Db::name('member_order_log')->where('member_order_id', $orderId)->where('title', '订单发货')->count() === $logBefore + 1, 'legacy log persisted');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::DELIVERED)->count() === 1, 'delivery event persisted');

    $outboundAfter = Db::name('goods_inventory')->where('member_order_id', $orderId)
        ->where('inventory_type', 2)->count();
    $assert(MemberOrderService::delivery([$orderId], $param) === true, 'duplicate delivery returns true');
    $assert(Db::name('goods_inventory')->where('member_order_id', $orderId)->where('inventory_type', 2)->count() === $outboundAfter, 'duplicate delivery creates no outbound');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate delivery creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $orderId)->find()->toArray();
$assert(intval($restored['status']) === intval($before['status']), 'order rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');

echo "Order delivery integration passed: {$assertions} assertions\n";
