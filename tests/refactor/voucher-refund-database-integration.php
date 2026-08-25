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
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)->where('pay_type', 2)->where('pay_status', 1)
    ->field('id,order_no,status,pay_status,pay_type,pay_price,total_price,total_num,refund_status,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No paid voucher order in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:voucher-refund:' . bin2hex(random_bytes(6));
$refundPrice = min(1.0, max(0.01, floatval($before['pay_price'] ?? $before['total_price'])));
$params = [
    'refund_status' => 2,
    'refund_type' => 1,
    'refund_price' => $refundPrice,
    'refund_reason' => '',
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'admin-next',
        'operator_type' => 'platform_admin',
        'operator_id' => 1,
        'reason' => 'integration voucher refund',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Voucher refund integration failed: {$message}");
    }
};

$initialBills = Db::name('member_bill')->where('order_id', $before['id'])->count();
Db::startTrans();
try {
    MemberOrderModel::where('id', $before['id'])->update(['status' => 5, 'refund_status' => 1, 'refund_type' => 1, 'refund_price' => $refundPrice]);
    $merchantIds = Db::name('member_order_detailed')->alias('d')->leftJoin('goods g', 'g.id=d.goods_id')
        ->where('d.member_order_id', $before['id'])->where('g.merchant_id', '>', 0)->column('g.merchant_id');
    if ($merchantIds) {
        Db::name('merchant')->whereIn('id', $merchantIds)->update(['mer_money' => 999999]);
    }

    $first = MemberOrderService::serviceOrder((int) $before['id'], $params, 1);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert($first === true, 'voucher refund keeps compatibility return');
    $assert((int) $after['status'] === 6 && (int) $after['refund_status'] === 2, 'voucher refund completes order');
    $assert((float) $after['refund_price'] === $refundPrice, 'refund amount persisted');
    $assert(trim((string) $after['out_refund_no']) !== '', 'refund number persisted');
    $assert(Db::name('member_bill')->where('order_id', $before['id'])->count() === $initialBills + 1, 'refund bill written once');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::REFUNDED)->count() === 1, 'refund event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation marked complete');

    $second = MemberOrderService::serviceOrder((int) $before['id'], $params, 1);
    $assert($second === true, 'duplicate refund returns original result');
    $assert(Db::name('member_bill')->where('order_id', $before['id'])->count() === $initialBills + 1, 'duplicate refund creates no bill');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate refund creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'], 'order rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Voucher refund database integration passed: {$assertions} assertions\n";
