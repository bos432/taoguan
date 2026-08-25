<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\gateway\payment\RefundGatewayInterface;
use app\common\model\finance\PaymentGatewayAttemptModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\order\OrderRefundService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$candidate = MemberOrderModel::where('is_delete', 0)->where('is_disable', 0)->where('pay_status', 1)
    ->field('id,order_no,status,pay_status,pay_type,pay_common_on,pay_price,total_price,total_num,refund_status,member_id,merchant_id')->find();
if (!$candidate) {
    throw new RuntimeException('No paid WeChat order in isolated snapshot');
}
$before = $candidate->toArray();
$requestId = 'integration:wechat-refund:' . bin2hex(random_bytes(6));
$refundPrice = min(1.0, max(0.01, floatval($before['pay_price'] ?? $before['total_price'])));
$params = [
    'refund_status' => 2, 'refund_type' => 1, 'refund_price' => $refundPrice,
    '_operation_context' => [
        'request_id' => $requestId, 'source' => 'admin-next', 'operator_type' => 'platform_admin',
        'operator_id' => 1, 'reason' => 'integration WeChat refund',
    ],
];
$gateway = new class implements RefundGatewayInterface {
    public int $calls = 0;
    public function refund(array $request): array
    {
        $this->calls++;
        return ['success' => true, 'provider_transaction_id' => 'FAKE-WX-REFUND', 'response' => ['result_code' => 'SUCCESS'], 'error' => ''];
    }
};
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("WeChat refund saga failed: {$message}");
    }
};

Db::startTrans();
try {
    MemberOrderModel::where('id', $before['id'])->update([
        'status' => 5, 'refund_status' => 1, 'refund_type' => 1, 'refund_price' => $refundPrice,
        'pay_type' => 1, 'pay_common_on' => 'REF-WX-PAY-' . $before['id'],
    ]);
    $merchantIds = Db::name('member_order_detailed')->alias('d')->leftJoin('goods g', 'g.id=d.goods_id')
        ->where('d.member_order_id', $before['id'])->where('g.merchant_id', '>', 0)->column('g.merchant_id');
    if ($merchantIds) {
        Db::name('merchant')->whereIn('id', $merchantIds)->update(['mer_money' => 999999]);
    }
    $first = OrderRefundService::reviewWechat((int) $before['id'], $params, 1, $gateway);
    $after = MemberOrderModel::where('id', $before['id'])->find()->toArray();
    $attempt = PaymentGatewayAttemptModel::where('member_order_id', $before['id'])->where('business_type', 'refund')->order('id', 'desc')->find()->toArray();
    $assert($first === true && $gateway->calls === 1, 'fake gateway called once');
    $assert((int) $after['status'] === 6 && (int) $after['refund_status'] === 2, 'order refund finalized');
    $assert((int) $attempt['status'] === 2 && $attempt['provider_transaction_id'] === 'FAKE-WX-REFUND', 'gateway success retained');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'refund event persisted');
    $assert(BusinessOperationRequestModel::where('request_id', $requestId)->value('status') === 1, 'operation completed');

    $second = OrderRefundService::reviewWechat((int) $before['id'], $params, 1, $gateway);
    $assert($second === true && $gateway->calls === 1, 'duplicate request never calls gateway again');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate request creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::where('id', $before['id'])->find()->toArray();
$assert((int) $restored['status'] === (int) $before['status'], 'order rolled back');
$assert(PaymentGatewayAttemptModel::where('member_order_id', $before['id'])->where('merchant_request_no', $attempt['merchant_request_no'] ?? '')->count() === 0, 'gateway attempt rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');

$unknownRequestId = 'integration:wechat-refund-unknown:' . bin2hex(random_bytes(6));
$unknownParams = $params;
$unknownParams['_operation_context']['request_id'] = $unknownRequestId;
$unknownGateway = new class implements RefundGatewayInterface {
    public int $calls = 0;
    public function refund(array $request): array
    {
        $this->calls++;
        throw new RuntimeException('simulated network timeout');
    }
};
Db::startTrans();
try {
    MemberOrderModel::where('id', $before['id'])->update([
        'status' => 5, 'refund_status' => 1, 'refund_type' => 1, 'refund_price' => $refundPrice,
        'pay_type' => 1, 'pay_common_on' => 'REF-WX-PAY-' . $before['id'],
    ]);
    $unknownThrown = false;
    try {
        OrderRefundService::reviewWechat((int) $before['id'], $unknownParams, 1, $unknownGateway);
    } catch (RuntimeException $exception) {
        $unknownThrown = str_contains($exception->getMessage(), '结果未知');
    }
    $unknownAttempt = PaymentGatewayAttemptModel::where('member_order_id', $before['id'])->where('status', 4)->order('id', 'desc')->find();
    $assert($unknownThrown && $unknownGateway->calls === 1, 'network exception becomes unknown result');
    $assert($unknownAttempt !== null, 'unknown gateway attempt retained');
    $assert((int) MemberOrderModel::where('id', $before['id'])->value('status') === 5, 'unknown result does not refund order');
    $retryBlocked = false;
    try {
        OrderRefundService::reviewWechat((int) $before['id'], $unknownParams, 1, $unknownGateway);
    } catch (Throwable) {
        $retryBlocked = true;
    }
    $assert($retryBlocked && $unknownGateway->calls === 1, 'unknown result blocks duplicate gateway call');
    $assert(OrderBusinessEventModel::where('request_id', $unknownRequestId)->count() === 0, 'unknown result creates no completed event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(PaymentGatewayAttemptModel::where('member_order_id', $before['id'])->where('status', 4)->count() === 0, 'unknown attempt test rolled back');

echo "WeChat refund saga passed: {$assertions} assertions\n";
