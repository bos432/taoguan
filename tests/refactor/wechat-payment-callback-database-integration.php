<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\model\member\MemberOrderModel;
use app\common\model\order\BusinessOperationRequestModel;
use app\common\model\order\OrderBusinessEventModel;
use app\common\service\order\WechatPaymentCallbackService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$ids = Db::name('member_order_detailed')->group('member_order_id')->limit(2)->column('member_order_id');
if (count($ids) < 2) {
    throw new RuntimeException('Need two isolated snapshot orders');
}
$ordersBefore = MemberOrderModel::whereIn('id', $ids)->order('id')->select()->toArray();
$firstId = (int) $ordersBefore[0]['id'];
$secondId = (int) $ordersBefore[1]['id'];
$outTradeNo = 'REF-WECHAT-' . bin2hex(random_bytes(5));
$transactionId = 'WXREF' . bin2hex(random_bytes(8));
$message = ['out_trade_no' => $outTradeNo, 'transaction_id' => $transactionId, 'return_code' => 'SUCCESS', 'result_code' => 'SUCCESS'];
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("WeChat callback integration failed: {$message}");
    }
};

$initialSecondBills = Db::name('member_bill')->where('order_id', $secondId)->count();
Db::startTrans();
try {
    MemberOrderModel::where('id', $firstId)->update(['pay_common_on' => $outTradeNo, 'status' => 1, 'pay_status' => 1, 'pay_type' => 1]);
    MemberOrderModel::where('id', $secondId)->update(['pay_common_on' => $outTradeNo, 'status' => 0, 'pay_status' => 0, 'pay_type' => 1]);
    $result = WechatPaymentCallbackService::handleSuccess($message);
    $first = MemberOrderModel::where('id', $firstId)->find()->toArray();
    $second = MemberOrderModel::where('id', $secondId)->find()->toArray();

    $assert($result === ['processed' => 1, 'skipped' => 1], 'already-paid order is skipped without ending batch');
    $assert((int) $first['pay_status'] === 1, 'already-paid order remains paid');
    $assert((int) $second['status'] === 1 && (int) $second['pay_status'] === 1, 'later shared order is paid');
    $assert(Db::name('member_bill')->where('order_id', $secondId)->count() === $initialSecondBills + 1, 'later shared order receives one bill');
    $assert(OrderBusinessEventModel::where('request_id', 'wechat:' . $transactionId)->where('event_type', Policy::WECHAT_PAID)->count() === 1, 'one payment event recorded');

    $duplicate = WechatPaymentCallbackService::handleSuccess($message);
    $assert($duplicate === $result, 'duplicate callback returns original result');
    $assert(Db::name('member_bill')->where('order_id', $secondId)->count() === $initialSecondBills + 1, 'duplicate callback creates no bill');
    $assert(OrderBusinessEventModel::where('request_id', 'wechat:' . $transactionId)->count() === 1, 'duplicate callback creates no event');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}

$restored = MemberOrderModel::whereIn('id', [$firstId, $secondId])->order('id')->select()->toArray();
$assert((int) $restored[0]['status'] === (int) $ordersBefore[0]['status'], 'first order rolled back');
$assert((int) $restored[1]['status'] === (int) $ordersBefore[1]['status'], 'second order rolled back');
$assert(BusinessOperationRequestModel::where('request_id', 'wechat:' . $transactionId)->count() === 0, 'operation rolled back');
$assert(OrderBusinessEventModel::where('request_id', 'wechat:' . $transactionId)->count() === 0, 'event rolled back');

echo "WeChat payment callback database integration passed: {$assertions} assertions\n";
