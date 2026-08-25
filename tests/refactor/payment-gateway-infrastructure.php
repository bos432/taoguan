<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\gateway\payment\RefundGatewayInterface;
use app\common\model\finance\PaymentGatewayAttemptModel;
use app\common\service\payment\PaymentGatewayAttemptService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$sql = file_get_contents(dirname(__DIR__, 2) . '/private/migrations/20260825_add_payment_gateway_attempt.sql');
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Payment gateway infrastructure failed: {$message}");
    }
};
$assert(str_contains((string) $sql, 'CREATE TABLE IF NOT EXISTS `ya_payment_gateway_attempt`'), 'migration is additive');
$assert(str_contains((string) $sql, 'UNIQUE KEY `uniq_gateway_request` (`provider`,`business_type`,`merchant_request_no`)'), 'gateway request is unique');
$assert(str_contains((string) $sql, '回滚前提'), 'rollback condition documented');

$fake = new class implements RefundGatewayInterface {
    public int $calls = 0;
    public function refund(array $request): array
    {
        $this->calls++;
        return ['success' => true, 'provider_transaction_id' => 'FAKE-1', 'response' => ['ok' => 1], 'error' => ''];
    }
};
$result = $fake->refund(['merchant_request_no' => 'R1']);
$assert($fake->calls === 1 && $result['success'] === true, 'fake gateway is injectable');

$requestNo = 'REF-GATEWAY-' . bin2hex(random_bytes(5));
Db::startTrans();
try {
    $attempt = PaymentGatewayAttemptService::prepare([
        'business_type' => 'refund', 'provider' => 'wechat', 'merchant_request_no' => $requestNo,
        'member_order_id' => 1, 'order_no' => 'ORDER-1', 'total_amount' => 100, 'amount' => 10,
        'request' => ['out_trade_no' => 'PAY-1'],
    ]);
    $duplicate = PaymentGatewayAttemptService::prepare([
        'business_type' => 'refund', 'provider' => 'wechat', 'merchant_request_no' => $requestNo,
        'member_order_id' => 1, 'order_no' => 'ORDER-1', 'total_amount' => 100, 'amount' => 10,
    ]);
    $assert($attempt['duplicate'] === false && $duplicate['duplicate'] === true, 'duplicate attempt reuses record');
    PaymentGatewayAttemptService::markRequesting((int) $attempt['id']);
    $assert((int) PaymentGatewayAttemptModel::where('id', $attempt['id'])->value('status') === 1, 'attempt marked requesting');
    $assert((int) PaymentGatewayAttemptModel::where('id', $attempt['id'])->value('attempt_count') === 1, 'attempt count incremented');
    PaymentGatewayAttemptService::succeed((int) $attempt['id'], $result);
    $row = PaymentGatewayAttemptModel::where('id', $attempt['id'])->find()->toArray();
    $assert((int) $row['status'] === 2 && $row['provider_transaction_id'] === 'FAKE-1', 'success response persisted');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(PaymentGatewayAttemptModel::where('merchant_request_no', $requestNo)->count() === 0, 'gateway attempt rolled back');

echo "Payment gateway infrastructure passed: {$assertions} assertions\n";
