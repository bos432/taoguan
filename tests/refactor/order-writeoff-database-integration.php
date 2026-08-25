<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\member\MemberOrderModel;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\member\MemberOrderService;
use app\common\service\order\OrderWriteoffService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$candidateOrderId = (int) Db::name('member_order_detailed')->where('member_order_id', '>', 0)->value('member_order_id');
$candidate = MemberOrderModel::where('id', $candidateOrderId)->where('is_delete', 0)->where('is_disable', 0)
    ->where('pay_status', 1)
    ->field('id,order_no,status,pay_status,total_price,pay_price,total_num,member_id,merchant_id,pick_up_code,delivery_type')->find();
if (!$candidate) {
    throw new RuntimeException('No paid order with details in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:writeoff:' . bin2hex(random_bytes(6));
$pickUpCode = 'REFTEST' . $before['id'];
$params = [
    'pick_up_code' => $pickUpCode,
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'admin-next',
        'operator_type' => 'platform_admin',
        'operator_id' => 1,
        'reason' => 'integration writeoff',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order writeoff integration failed: {$message}");
    }
};

Db::startTrans();
try {
    MemberOrderModel::where('id', $before['id'])->update(['status' => 1, 'delivery_type' => 2, 'pick_up_code' => $pickUpCode]);
    $first = OrderWriteoffService::writeoff([(int) $before['id']], $params);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert($first === true, 'writeoff keeps compatibility return');
    $assert((int) $after['status'] === 3, 'writeoff moves order to pending evaluation');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::PICKED_UP)->count() === 1, 'writeoff event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation marked complete');

    $inventoryAfterFirst = Db::name('goods_inventory')->where('member_order_id', $before['id'])->count();
    $second = MemberOrderService::takeDelivery([(int) $before['id']], $params);
    $assert($second === true, 'duplicate writeoff returns original result');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate writeoff creates no event');
    $assert(Db::name('goods_inventory')->where('member_order_id', $before['id'])->count() === $inventoryAfterFirst, 'duplicate writeoff creates no inventory movement');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'], 'order rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Order writeoff database integration passed: {$assertions} assertions\n";
