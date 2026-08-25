<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\gateway\payment\PrepaymentGatewayInterface;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\member\MemberOrderService;
use think\facade\Db;

final class FakePrepaymentGateway implements PrepaymentGatewayInterface
{
    public int $calls = 0;

    public function prepay(array $request): array
    {
        $this->calls++;
        return [
            'success' => true,
            'bridge_config' => ['appId' => 'fake-app', 'package' => 'prepay_id=fake-prepay'],
            'provider_transaction_id' => 'fake-prepay',
            'response' => ['return_code' => 'SUCCESS', 'return_msg' => 'OK', 'prepay_id' => 'fake-prepay'],
            'error' => '',
        ];
    }
}

$app = new think\App();
$app->initialize();
$memberId = intval(Db::name('member')->alias('m')
    ->leftJoin('merchant mer', 'mer.member_id=m.member_id AND mer.is_delete=0')
    ->whereNull('mer.id')->where('m.is_delete', 0)->value('m.member_id'));
$goods = Db::name('goods')->where('is_disable', 0)->where('is_delete', 0)
    ->where('status', 1)->where('stock', '>', 0)->where('merchant_id', '>', 0)->find();
if ($memberId <= 0 || !$goods) {
    throw new RuntimeException('No member or goods for prepayment integration');
}
$gateway = new FakePrepaymentGateway();
$requestId = 'integration:prepayment:' . bin2hex(random_bytes(5));
$param = [
    'member_id' => $memberId,
    'pay_type' => 1,
    'delivery_type' => 2,
    'self_name' => 'Integration Member',
    'self_phone' => '13800138000',
    'merchant_list' => [[
        'id' => intval($goods['merchant_id']),
        'goods' => [[
            'id' => intval($goods['id']), 'title' => strval($goods['title']),
            'cart_num' => 1, 'price' => strval($goods['price']),
        ]],
    ]],
    '_prepayment_gateway' => $gateway,
    '_operation_context' => [
        'request_id' => $requestId, 'source' => 'uniapp-weixin', 'operator_type' => 'member',
        'operator_id' => $memberId, 'reason' => 'integration prepayment',
    ],
];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Wechat prepayment integration failed: {$message}");
    }
};
$orderCountBefore = Db::name('member_order')->count();
$stockBefore = intval($goods['stock']);

Db::startTrans();
try {
    $result = MemberOrderService::confirmOrder($param);
    $assert(($result['package'] ?? '') === 'prepay_id=fake-prepay', 'bridge config returned');
    $assert($gateway->calls === 1, 'fake gateway called once');
    $event = OrderBusinessEventModel::where('request_id', $requestId)->find();
    $assert($event !== null, 'creation event persisted');
    $assert(Db::name('member_order')->where('id', intval($event['member_order_id']))->where('pay_type', 1)->count() === 1, 'wechat order persisted');
    $operation = BusinessOperationRequestModel::where('request_id', $requestId)->find();
    $storedResult = json_decode(strval($operation['result_json'] ?? ''), true) ?: [];
    $assert(($storedResult['package'] ?? '') === 'prepay_id=fake-prepay', 'bridge config stored for replay');

    $duplicate = MemberOrderService::confirmOrder($param);
    $assert(($duplicate['package'] ?? '') === 'prepay_id=fake-prepay', 'duplicate replays bridge config');
    $assert($gateway->calls === 1, 'duplicate does not call gateway');
    $assert(Db::name('member_order')->count() === $orderCountBefore + 1, 'duplicate creates no order');
    $assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 1, 'duplicate creates no event');
    $assert(intval(Db::name('goods')->where('id', intval($goods['id']))->value('stock')) === $stockBefore - 1, 'duplicate decrements stock once');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$assert(Db::name('member_order')->count() === $orderCountBefore, 'orders rolled back');
$assert(intval(Db::name('goods')->where('id', intval($goods['id']))->value('stock')) === $stockBefore, 'stock rolled back');
$assert(BusinessOperationRequestModel::where('request_id', $requestId)->count() === 0, 'operation rolled back');
$assert(OrderBusinessEventModel::where('request_id', $requestId)->count() === 0, 'event rolled back');

echo "Wechat prepayment gateway integration passed: {$assertions} assertions\n";
