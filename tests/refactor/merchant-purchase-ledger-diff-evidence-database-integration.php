<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\domain\order\OrderStateTransitionPolicy as Policy;
use app\common\service\order\OrderBusinessEventService;
use app\common\service\report\MerchantPurchaseLedgerDiffEvidenceService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$order = Db::name('member_order')->where('is_delete', 0)->where('id', '>', 0)->find();
if (!$order) throw new RuntimeException('No order for diff evidence integration');
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Diff evidence integration failed: {$message}");
};
$requestId = 'DIFF-EVIDENCE-' . bin2hex(random_bytes(5));

Db::startTrans();
try {
    OrderBusinessEventService::record(Policy::VOUCHER_APPROVED, $order, array_merge($order, [
        'status' => 4, 'pay_status' => 1,
    ]), [
        'request_id' => $requestId, 'source' => 'admin-next', 'operator_type' => 'system',
        'operator_id' => 0, 'reason' => '差额证据测试',
    ], ['amount' => 2998, 'quantity' => 1, 'occurred_at' => '2026-08-25 12:00:00']);
    $result = MerchantPurchaseLedgerDiffEvidenceService::enrich([
        'match_type' => 'balance', 'message' => '未配平买入订单',
        'orders' => [[
            'member_order_id' => $order['id'], 'order_no' => $order['order_no'],
            'amount' => 2998, 'quantity' => 1, 'remaining_amount' => 2998,
            'remaining_quantity' => 1, 'diagnosis_type' => 'still_in_stock',
            'diagnosis_message' => '仍在库存，尚未卖出',
        ]],
        'candidate_orders' => [], 'goods_gaps' => [],
    ]);
    $row = $result['orders'][0];
    $assert(intval($row['member_order_id']) === intval($order['id']), 'order identity retained');
    $assert(abs(floatval($row['match_evidence']['matched_amount']) - 2998) < 0.001, 'matched amount returned');
    $assert(intval($row['match_evidence']['remaining_quantity']) === 1, 'unmatched quantity returned');
    $assert($row['match_evidence']['reason_code'] === 'still_in_stock', 'reason code returned');
    $assert($row['match_evidence']['reason'] === '仍在库存，尚未卖出', 'reason returned');
    $assert(($row['timeline']['order']['order_no'] ?? '') === $order['order_no'], 'timeline order returned');
    $events = array_filter($row['timeline']['events'] ?? [], static fn(array $event): bool => $event['request_id'] === $requestId);
    $assert(count($events) === 1, 'business event included');
    $assert(intval($result['evidence_summary']['order_count']) === 1, 'evidence order count returned');
    $assert(intval($result['evidence_summary']['event_count']) >= 1, 'evidence event count returned');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('order_business_event')->where('request_id', $requestId)->count() === 0, 'fixture rolled back');
echo "Merchant purchase ledger diff evidence integration passed: {$assertions} assertions\n";
