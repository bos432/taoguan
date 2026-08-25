<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\service\report\DailyOrderReconciliationService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$orderTemplate = Db::name('member_order')->where('id', '>', 0)->find();
$detailTemplate = Db::name('member_order_detailed')->where('id', '>', 0)->find();
$billTemplate = Db::name('member_bill')->where('id', '>', 0)->find();
$ledgerTemplate = Db::name('merchant_purchase_ledger')->where('id', '>', 0)->find();
if (!$orderTemplate || !$detailTemplate || !$billTemplate || !$ledgerTemplate) {
    throw new RuntimeException('Daily reconciliation fixtures require order, detail, bill and ledger templates');
}
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Daily reconciliation integration failed: {$message}");
};
$date = '2098-12-30';
$time = $date . ' 12:00:00';
$requestNo = 'RECON-' . bin2hex(random_bytes(5));

Db::startTrans();
try {
    unset($orderTemplate['id']);
    $orderTemplate = array_merge($orderTemplate, [
        'order_no' => $requestNo, 'create_time' => $time, 'update_time' => $time,
        'pay_time' => $time, 'pay_status' => 1, 'pay_type' => 2, 'pay_price' => '0.00',
        'total_price' => '29.98', 'total_num' => 2, 'delivery_type' => 2,
        'delivery_time' => $time, 'status' => 3, 'is_disable' => 0, 'is_delete' => 0,
    ]);
    $orderId = intval(Db::name('member_order')->insertGetId($orderTemplate));

    unset($detailTemplate['id']);
    $detailTemplate = array_merge($detailTemplate, [
        'member_order_id' => $orderId, 'quantity' => 2, 'price' => '14.99', 'total' => '29.98',
    ]);
    $detailId = intval(Db::name('member_order_detailed')->insertGetId($detailTemplate));

    unset($billTemplate['id']);
    $billTemplate = array_merge($billTemplate, [
        'order_id' => $orderId, 'member_id' => $orderTemplate['member_id'], 'title' => '购买商品',
        'in_out' => 2, 'money' => '29.98', 'create_time' => $time, 'update_time' => $time,
        'is_disable' => 0, 'is_delete' => 0,
    ]);
    Db::name('member_bill')->insert($billTemplate);

    unset($ledgerTemplate['id']);
    $ledgerTemplate = array_merge($ledgerTemplate, [
        'member_order_id' => $orderId, 'member_order_detailed_id' => $detailId,
        'order_no' => $requestNo, 'quantity' => 2,
        'price' => '14.99', 'total' => '29.98', 'order_pay_price' => '29.98',
        'pay_type' => 2, 'pay_time' => $time, 'create_time' => $time, 'update_time' => $time,
        'is_disable' => 0, 'is_delete' => 0,
    ]);
    Db::name('merchant_purchase_ledger')->insert($ledgerTemplate);

    Db::name('order_business_event')->insert([
        'event_type' => OrderStateTransitionPolicy::PICKED_UP, 'member_order_id' => $orderId,
        'order_no' => $requestNo, 'before_status' => 1, 'after_status' => 3,
        'before_pay_status' => 1, 'after_pay_status' => 1, 'amount' => '29.98', 'quantity' => 2,
        'member_id' => $orderTemplate['member_id'], 'merchant_id' => $orderTemplate['merchant_id'],
        'operator_type' => 'system', 'operator_id' => 0, 'source' => 'test',
        'request_id' => $requestNo . '-writeoff', 'occurred_at' => $time, 'create_time' => $time,
    ]);
    Db::name('order_business_event')->insert([
        'event_type' => OrderStateTransitionPolicy::VOUCHER_APPROVED, 'member_order_id' => $orderId,
        'order_no' => $requestNo, 'before_status' => 0, 'after_status' => 4,
        'before_pay_status' => 0, 'after_pay_status' => 1, 'amount' => '29.98', 'quantity' => 2,
        'member_id' => $orderTemplate['member_id'], 'merchant_id' => $orderTemplate['merchant_id'],
        'operator_type' => 'system', 'operator_id' => 0, 'source' => 'test',
        'request_id' => $requestNo . '-payment', 'occurred_at' => $time, 'create_time' => $time,
    ]);

    $report = DailyOrderReconciliationService::report($date);
    $assert($report['period']['date'] === $date, 'period returned');
    $assert($report['order_summary']['paid_count'] === 1, 'paid order counted');
    $assert(abs($report['order_summary']['paid_recorded_amount']) < 0.001, 'recorded pay amount preserved');
    $assert(abs($report['order_summary']['paid_accounting_amount'] - 29.98) < 0.001, 'voucher total used as accounting amount');
    $assert($report['financial_summary']['ledger_order_count'] === 1, 'ledger order counted');
    $assert(abs($report['financial_summary']['bill_recorded_amount'] - 29.98) < 0.001, 'member bill amount counted');
    $assert($report['writeoff_summary']['event_count'] === 1, 'writeoff event counted');
    $assert($report['writeoff_summary']['legacy_candidate_count'] === 0, 'event-backed writeoff excluded from legacy candidates');
    $assert($report['event_coverage']['missing_payment_event_count'] === 0, 'payment event coverage complete');
    $assert($report['status'] === 'ok' && $report['anomaly_count'] === 0, 'balanced report is healthy');
    Db::name('member_bill')->where('order_id', $orderId)->delete();
    $attentionReport = DailyOrderReconciliationService::report($date);
    $assert($attentionReport['status'] === 'attention', 'missing bill changes report status');
    $assert(in_array('missing_bill', array_column($attentionReport['anomalies'], 'type'), true), 'missing bill identifies order');
    $invalidRejected = false;
    try {
        DailyOrderReconciliationService::report('2098-02-30');
    } catch (InvalidArgumentException) {
        $invalidRejected = true;
    }
    $assert($invalidRejected, 'invalid date rejected');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('member_order')->where('order_no', $requestNo)->count() === 0, 'fixtures rolled back');
echo "Daily order reconciliation integration passed: {$assertions} assertions\n";
