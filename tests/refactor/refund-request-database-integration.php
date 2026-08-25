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
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)->where('status', 4)
    ->field('id,order_no,status,pay_status,refund_status,refund_type,refund_price,total_num,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No completed order in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:refund-request:' . bin2hex(random_bytes(6));
$params = [
    'member_id' => (int) $before['member_id'],
    'refund_type' => 2,
    'refund_price' => 0,
    'refund_reason_wap_explain' => 'integration refund request',
    'refund_reason_wap_imgs' => [['file_id' => 1]],
    '_operation_context' => [
        'request_id' => $requestId,
        'source' => 'uniapp-weixin',
        'operator_type' => 'member',
        'operator_id' => (int) $before['member_id'],
        'reason' => 'integration refund request',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Refund request integration failed: {$message}");
    }
};

Db::startTrans();
try {
    $first = MemberOrderService::submitService([(int) $before['id']], $params);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $assert($first === true, 'refund request keeps compatibility return');
    $assert((int) $after['status'] === 5 && (int) $after['refund_status'] === 1, 'completed order enters service');
    $assert((int) $after['refund_type'] === 2, 'refund type retained');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->where('event_type', Policy::SERVICE_REQUESTED)->count() === 1, 'refund request event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation marked complete');

    $second = MemberOrderService::submitService([(int) $before['id']], $params);
    $assert($second === true, 'duplicate refund request returns original result');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate refund request creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'] && (int) $restored['refund_status'] === (int) $before['refund_status'], 'order rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

echo "Refund request database integration passed: {$assertions} assertions\n";
