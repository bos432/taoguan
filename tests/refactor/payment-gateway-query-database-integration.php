<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\payment\PaymentGatewayAttemptQueryService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$order = Db::name('member_order')->where('id', '>', 0)->find();
if (!$order) {
    throw new RuntimeException('No order for gateway query integration');
}
$requestNo = 'QUERY-' . bin2hex(random_bytes(5));
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Gateway query integration failed: {$message}");
};

Db::startTrans();
try {
    $id = intval(Db::name('payment_gateway_attempt')->insertGetId([
        'business_type' => 'prepayment', 'provider' => 'wechat', 'merchant_request_no' => $requestNo,
        'member_order_id' => $order['id'], 'order_no' => $order['order_no'], 'total_amount' => '10.00',
        'amount' => '10.00', 'status' => 4, 'attempt_count' => 1,
        'request_json' => json_encode(['out_trade_no' => $requestNo]),
        'response_json' => json_encode(['return_code' => 'UNKNOWN']),
        'error_message' => 'integration unknown', 'create_time' => date('Y-m-d H:i:s'),
        'update_time' => date('Y-m-d H:i:s'),
    ]));
    $list = PaymentGatewayAttemptQueryService::list(['status' => 4, 'keyword' => $requestNo]);
    $assert(intval($list['count']) === 1 && intval($list['list'][0]['id']) === $id, 'filtered list returns attempt');
    $assert($list['list'][0]['status_title'] === '结果未知' && $list['list'][0]['needs_attention'] === true, 'attention metadata returned');
    $assert($list['list'][0]['business_type_title'] === '微信预下单', 'business title returned');
    $summary = PaymentGatewayAttemptQueryService::summary();
    $assert(intval($summary['status_counts'][4] ?? 0) >= 1 && intval($summary['attention']) >= 1, 'summary includes unknown attempt');
    $info = PaymentGatewayAttemptQueryService::info($id);
    $assert(($info['request']['out_trade_no'] ?? '') === $requestNo, 'request JSON decoded');
    $assert(($info['response']['return_code'] ?? '') === 'UNKNOWN', 'response JSON decoded');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('payment_gateway_attempt')->where('merchant_request_no', $requestNo)->count() === 0, 'query fixture rolled back');
echo "Payment gateway query integration passed: {$assertions} assertions\n";
