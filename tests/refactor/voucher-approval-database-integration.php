<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\member\MemberOrderModel;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\member\MemberOrderService;
use app\common\service\order\VoucherPaymentReviewService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)
    ->where('status', 0)->where('pay_status', 0)->where('pay_type', 2)
    ->field('id,order_no,status,pay_status,total_price,pay_price,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No pending voucher order in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:voucher-approve:' . bin2hex(random_bytes(6));
$params = [
    'pay_status' => 1,
    'pay_price' => (float) $before['total_price'],
    'pay_auth_msg' => 'integration approval',
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'admin-next',
        'operator_type' => 'platform_admin',
        'operator_id' => 1,
        'reason' => 'integration approval',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Voucher approval integration failed: {$message}");
    }
};

$initialBills = Db::name('member_bill')->where('order_id', $before['id'])->count();
$initialLedger = Db::name('merchant_purchase_ledger')->where('member_order_id', $before['id'])->count();
Db::startTrans();
try {
    $first = VoucherPaymentReviewService::review([(int) $before['id']], $params);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert((int) $after['status'] === 4 && (int) $after['pay_status'] === 1, 'approval completes voucher order');
    $assert((float) $after['pay_price'] === (float) $before['total_price'], 'approved amount persisted');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::VOUCHER_APPROVED)->count() === 1, 'approval event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation marked complete');
    $assert(Db::name('member_bill')->where('order_id', $before['id'])->count() === $initialBills + 1, 'member bill written once');

    $ledgerAfterFirst = Db::name('merchant_purchase_ledger')->where('member_order_id', $before['id'])->count();
    $second = MemberOrderService::orderPayAuth([(int) $before['id']], $params);
    $assert($second === $first, 'duplicate request returns original result');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate request creates no event');
    $assert(Db::name('member_bill')->where('order_id', $before['id'])->count() === $initialBills + 1, 'duplicate request creates no bill');
    $assert(Db::name('merchant_purchase_ledger')->where('member_order_id', $before['id'])->count() === $ledgerAfterFirst, 'duplicate request creates no ledger row');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'] && (int) $restored['pay_status'] === (int) $before['pay_status'], 'order rolled back');
$assert(Db::name('member_bill')->where('order_id', $before['id'])->count() === $initialBills, 'bill rolled back');
$assert(Db::name('merchant_purchase_ledger')->where('member_order_id', $before['id'])->count() === $initialLedger, 'ledger rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Voucher approval database integration passed: {$assertions} assertions\n";
