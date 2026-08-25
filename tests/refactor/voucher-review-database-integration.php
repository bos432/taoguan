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

$candidate = MemberOrderModel::where('is_delete', 0)
    ->where('is_disable', 0)
    ->where('status', 0)
    ->where('pay_status', 0)
    ->where('pay_type', 2)
    ->field('id,order_no,status,pay_status,pay_auth_msg')
    ->find();
if (!$candidate) {
    throw new RuntimeException('No pending voucher order in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:voucher-reject:' . bin2hex(random_bytes(6));
$context = [
    'request_id' => $requestId,
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 1,
    'reason' => 'integration rejection',
];
$params = [
    'pay_status' => 0,
    'pay_price' => 0,
    'pay_auth_msg' => 'integration rejection',
    '_operation_context' => $context,
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Voucher review integration failed: {$message}");
    }
};

Db::startTrans();
try {
    $first = MemberOrderService::orderPayAuth([(int) $before['id']], $params);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert($first === array_diff_key($params, ['_operation_context' => true]), 'compatibility return excludes internal context');
    $assert((int) $after['status'] === 7 && (int) $after['pay_status'] === 2, 'voucher rejection updates order');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::VOUCHER_REJECTED)->count() === 1, 'rejection event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation marked complete');

    $second = MemberOrderService::orderPayAuth([(int) $before['id']], $params);
    $assert($second === $first, 'duplicate request returns original compatibility result');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate request creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'] && (int) $restored['pay_status'] === (int) $before['pay_status'], 'order rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Voucher review database integration passed: {$assertions} assertions\n";
