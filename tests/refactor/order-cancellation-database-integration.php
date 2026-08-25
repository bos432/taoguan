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
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)->where('status', 0)->where('pay_status', 0)
    ->with(['detaileds'])->field('id,order_no,status,pay_status,total_num,total_price,pay_price,member_id,merchant_id')->find();
if (!$candidate || $candidate['detaileds']->isEmpty()) {
    throw new RuntimeException('No cancellable order with details in isolated snapshot');
}
$before = $candidate->toArray();
$stockBefore = [];
foreach ($before['detaileds'] as $detail) {
    $stockBefore[(int) $detail['goods_id']] = (int) Db::name('goods')->where('id', $detail['goods_id'])->value('stock');
}
$requestId = 'integration:cancel:' . bin2hex(random_bytes(6));
$params = [
    'member_id' => (int) $before['member_id'],
    '_operation_context' => [
        'request_id' => $requestId, 'source' => 'uniapp-weixin', 'operator_type' => 'member',
        'operator_id' => (int) $before['member_id'], 'reason' => 'integration cancel',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order cancellation integration failed: {$message}");
    }
};

Db::startTrans();
try {
    $first = MemberOrderService::cancelOrder([(int) $before['id']], $params);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert($first === true && (int) $after['is_delete'] === 1, 'order soft deleted');
    $assert((int) $after['status'] === 0, 'compatibility status remains pending payment');
    foreach ($before['detaileds'] as $detail) {
        $stock = (int) Db::name('goods')->where('id', $detail['goods_id'])->value('stock');
        $assert($stock === $stockBefore[(int) $detail['goods_id']] + (int) $detail['quantity'], 'stock restored once');
    }
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::CANCELED)->count() === 1, 'cancel event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation completed');

    $second = MemberOrderService::cancelOrder([(int) $before['id']], $params);
    $assert($second === true, 'duplicate cancellation returns original result');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate creates no event');
    foreach ($before['detaileds'] as $detail) {
        $stock = (int) Db::name('goods')->where('id', $detail['goods_id'])->value('stock');
        $assert($stock === $stockBefore[(int) $detail['goods_id']] + (int) $detail['quantity'], 'duplicate does not restore stock twice');
    }
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['is_delete'] === 0, 'order rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Order cancellation database integration passed: {$assertions} assertions\n";
