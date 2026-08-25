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
$orderId = (int) Db::name('member_order_detailed')->where('member_order_id', '>', 0)->value('member_order_id');
$candidate = MemberOrderModel::where('id', $orderId)->field('id,order_no,status,pay_status,pay_type,total_num,total_price,pay_price,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No order for fulfillment integration');
}
$before = $candidate->toArray();
$receiveRequestId = 'integration:receive:' . bin2hex(random_bytes(5));
$evaluateRequestId = 'integration:evaluate:' . bin2hex(random_bytes(5));
$base = ['member_id' => (int) $before['member_id']];
$receive = $base + ['_operation_context' => [
    'request_id' => $receiveRequestId, 'source' => 'uniapp-weixin', 'operator_type' => 'member',
    'operator_id' => (int) $before['member_id'], 'reason' => 'integration receive',
]];
$evaluate = $base + [
    'evaluate_content' => 'integration evaluation', 'evaluate_num' => 5,
    '_operation_context' => [
        'request_id' => $evaluateRequestId, 'source' => 'uniapp-weixin', 'operator_type' => 'member',
        'operator_id' => (int) $before['member_id'], 'reason' => 'integration evaluate',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Order fulfillment integration failed: {$message}");
    }
};

Db::startTrans();
try {
    MemberOrderModel::where('id', $before['id'])->update(['status' => 2, 'pay_status' => 1, 'pay_type' => 2]);
    $assert(MemberOrderService::confirmReceipt([$orderId], $receive) === true, 'receive keeps compatibility return');
    $assert((int) MemberOrderModel::where('id', $orderId)->value('status') === 3, 'receive moves to pending evaluation');
    $assert(OrderBusinessEventModel::where('request_id', $receiveRequestId)->where('event_type', Policy::RECEIVED)->count() === 1, 'receive event persisted');
    $assert(MemberOrderService::confirmReceipt([$orderId], $receive) === true, 'duplicate receive returns true');
    $assert(OrderBusinessEventModel::where('request_id', $receiveRequestId)->count() === 1, 'duplicate receive creates no event');

    $assert(MemberOrderService::submitEvaluation([$orderId], $evaluate) === true, 'evaluation keeps compatibility return');
    $assert((int) MemberOrderModel::where('id', $orderId)->value('status') === 4, 'evaluation completes order');
    $detail = Db::name('member_order_detailed')->where('member_order_id', $orderId)->find();
    $assert($detail['evaluate_content'] === 'integration evaluation' && (int) $detail['evaluate_num'] === 5, 'evaluation detail persisted');
    $assert(OrderBusinessEventModel::where('request_id', $evaluateRequestId)->where('event_type', Policy::EVALUATED)->count() === 1, 'evaluation event persisted');
    $assert(MemberOrderService::submitEvaluation([$orderId], $evaluate) === true, 'duplicate evaluation returns true');
    $assert(OrderBusinessEventModel::where('request_id', $evaluateRequestId)->count() === 1, 'duplicate evaluation creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $orderId)->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'], 'order rolled back');
$assert(BusinessOperationRequestModel::whereIn('request_id', [$receiveRequestId, $evaluateRequestId])->count() === 0, 'operations rolled back');
$assert(OrderBusinessEventModel::whereIn('request_id', [$receiveRequestId, $evaluateRequestId])->count() === 0, 'events rolled back');

echo "Order fulfillment integration passed: {$assertions} assertions\n";
