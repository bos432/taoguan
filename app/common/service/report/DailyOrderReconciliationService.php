<?php

declare(strict_types=1);

namespace app\common\service\report;

use app\common\domain\order\OrderStateTransitionPolicy;
use DateTimeImmutable;
use InvalidArgumentException;
use think\facade\Db;

class DailyOrderReconciliationService
{
    private const PAYMENT_EVENTS = [
        OrderStateTransitionPolicy::WECHAT_PAID,
        OrderStateTransitionPolicy::VOUCHER_APPROVED,
    ];

    public static function report(string $date): array
    {
        [$start, $end] = self::period($date);
        $orders = self::orderSummary($start, $end);
        $ledger = self::ledgerSummary($start, $end);
        $bills = self::billSummary($start, $end);
        $writeoff = self::writeoffSummary($start, $end);
        $coverage = self::eventCoverage($start, $end, intval($orders['paid_count']));
        $anomalies = array_merge(
            self::missingBillOrders($start, $end),
            self::ledgerAnomalies($date),
            $coverage['missing_payment_events'],
            self::gatewayAnomalies($start, $end)
        );

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => ['date' => $date, 'start_time' => $start, 'end_time' => $end],
            'status' => $anomalies === [] ? 'ok' : 'attention',
            'anomaly_count' => count($anomalies),
            'order_summary' => $orders,
            'financial_summary' => [
                'ledger_order_count' => intval($ledger['order_count'] ?? 0),
                'ledger_amount' => self::money($ledger['amount'] ?? 0),
                'ledger_quantity' => intval($ledger['quantity'] ?? 0),
                'bill_order_count' => intval($bills['order_count'] ?? 0),
                'bill_recorded_amount' => self::money($bills['recorded_amount'] ?? 0),
                'bill_accounting_amount' => self::money($bills['accounting_amount'] ?? 0),
                'bill_legacy_adjustment_amount' => self::money(
                    floatval($bills['accounting_amount'] ?? 0) - floatval($bills['recorded_amount'] ?? 0)
                ),
            ],
            'writeoff_summary' => $writeoff,
            'event_coverage' => array_diff_key($coverage, ['missing_payment_events' => true]),
            'anomalies' => array_slice($anomalies, 0, 100),
            'notes' => [
                'paid_recorded_amount 为数据库 pay_price 原值；paid_accounting_amount 对凭证订单按 total_price、微信订单按 pay_price 核算。',
                'bill_recorded_amount 为会员账单原值；bill_accounting_amount 使用与订单台账一致的历史兼容口径。',
                '核销事件为 order.picked_up；事件覆盖前仅按自提订单 delivery_time 统计历史候选。',
                '采购流水只覆盖已形成商家采购流水的订单，不能与全平台支付总额直接相减。',
            ],
        ];
    }

    private static function period(string $date): array
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$parsed || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('对账日期必须是有效的 YYYY-MM-DD');
        }
        return [$date . ' 00:00:00', $date . ' 23:59:59'];
    }

    private static function orderSummary(string $start, string $end): array
    {
        $active = Db::name('member_order')->where('is_disable', 0)->where('is_delete', 0);
        $created = (clone $active)->whereBetweenTime('create_time', $start, $end);
        $paid = (clone $active)->where('pay_status', 1)->whereBetweenTime('pay_time', $start, $end);

        return [
            'created_count' => intval((clone $created)->count()),
            'created_amount' => self::money((clone $created)->sum('total_price')),
            'created_quantity' => intval((clone $created)->sum('total_num')),
            'paid_count' => intval((clone $paid)->count()),
            'paid_recorded_amount' => self::money((clone $paid)->sum('pay_price')),
            'paid_accounting_amount' => self::money((clone $paid)->sum(Db::raw(self::paidAmountExpression()))),
            'paid_quantity' => intval((clone $paid)->sum('total_num')),
        ];
    }

    private static function ledgerSummary(string $start, string $end): array
    {
        return Db::name('merchant_purchase_ledger')
            ->where('is_delete', 0)->whereBetweenTime('pay_time', $start, $end)
            ->field('COUNT(DISTINCT member_order_id) as order_count,SUM(total) as amount,SUM(quantity) as quantity')
            ->find() ?: [];
    }

    private static function billSummary(string $start, string $end): array
    {
        return Db::name('member_order')->alias('o')
            ->join('member_bill b', 'b.order_id=o.id AND b.is_delete=0 AND b.title="购买商品" AND b.in_out=2')
            ->where('o.is_disable', 0)->where('o.is_delete', 0)->where('o.pay_status', 1)
            ->whereBetweenTime('o.pay_time', $start, $end)
            ->field('COUNT(DISTINCT o.id) as order_count,SUM(b.money) as recorded_amount,SUM(' . self::paidAmountExpression('o.') . ') as accounting_amount')
            ->find() ?: [];
    }

    private static function writeoffSummary(string $start, string $end): array
    {
        $events = Db::name('order_business_event')
            ->where('event_type', OrderStateTransitionPolicy::PICKED_UP)
            ->whereBetweenTime('occurred_at', $start, $end)
            ->field('COUNT(DISTINCT member_order_id) as order_count,SUM(amount) as amount,SUM(quantity) as quantity')
            ->find() ?: [];
        $legacy = Db::name('member_order')->alias('o')
            ->where('o.is_disable', 0)->where('o.is_delete', 0)->where('o.delivery_type', 2)
            ->whereBetweenTime('o.delivery_time', $start, $end)
            ->whereNotExists(static function ($query) {
                $query->table('ya_order_business_event')->whereRaw('member_order_id=o.id')
                    ->where('event_type', OrderStateTransitionPolicy::PICKED_UP);
            });

        return [
            'event_count' => intval($events['order_count'] ?? 0),
            'event_amount' => self::money($events['amount'] ?? 0),
            'event_quantity' => intval($events['quantity'] ?? 0),
            'legacy_candidate_count' => intval((clone $legacy)->count()),
            'legacy_candidate_amount' => self::money((clone $legacy)->sum(Db::raw(self::paidAmountExpression('o.')))),
        ];
    }

    private static function eventCoverage(string $start, string $end, int $paidCount): array
    {
        $coverageStart = strval(Db::name('order_business_event')->min('occurred_at') ?: '');
        $paymentEventCount = intval(Db::name('order_business_event')
            ->whereIn('event_type', self::PAYMENT_EVENTS)->whereBetweenTime('occurred_at', $start, $end)
            ->distinct(true)->count('member_order_id'));
        $expected = 0;
        $missing = [];
        if ($coverageStart !== '' && $end >= $coverageStart) {
            $effectiveStart = max($start, $coverageStart);
            $expectedQuery = Db::name('member_order')->alias('o')
                ->where('o.is_disable', 0)->where('o.is_delete', 0)->where('o.pay_status', 1)
                ->whereBetweenTime('o.pay_time', $effectiveStart, $end)
                ->whereNotExists(static function ($query) {
                    $query->table('ya_order_business_event')->whereRaw('member_order_id=o.id')
                        ->whereIn('event_type', self::PAYMENT_EVENTS);
                });
            $expected = intval((clone $expectedQuery)->count());
            foreach ((clone $expectedQuery)->field('o.id,o.order_no,o.pay_type,o.total_price,o.pay_price')->limit(50)->select()->toArray() as $row) {
                $missing[] = self::anomaly('missing_payment_event', $row, '事件覆盖期内已支付订单缺少支付成功事件');
            }
        }

        $mode = $coverageStart === '' ? 'legacy_only' : (($expected === 0 && $paymentEventCount === $paidCount) ? 'event' : 'hybrid');
        return [
            'mode' => $mode,
            'coverage_start' => $coverageStart,
            'payment_event_count' => $paymentEventCount,
            'missing_payment_event_count' => $expected,
            'missing_payment_events' => $missing,
        ];
    }

    private static function missingBillOrders(string $start, string $end): array
    {
        $query = Db::name('member_order')->alias('o')
            ->where('o.is_disable', 0)->where('o.is_delete', 0)->where('o.pay_status', 1)
            ->whereBetweenTime('o.pay_time', $start, $end)
            ->whereNotExists(static function ($subQuery) {
                $subQuery->table('ya_member_bill')->whereRaw('order_id=o.id')->where('is_delete', 0)
                    ->where('title', '购买商品')->where('in_out', 2);
            });
        return array_map(static fn(array $row): array => self::anomaly(
            'missing_bill', $row, '已支付订单缺少会员购买账单'
        ), $query->field('o.id,o.order_no,o.pay_type,o.total_price,o.pay_price')->limit(50)->select()->toArray());
    }

    private static function ledgerAnomalies(string $date): array
    {
        $result = MerchantPurchaseLedgerReportService::reconciliation([
            'start_date' => $date, 'end_date' => $date,
        ]);
        return array_map(static function (array $row): array {
            return [
                'type' => 'ledger_' . strval($row['reconcile_status'] ?? 'unknown'),
                'member_order_id' => intval($row['member_order_id'] ?? 0),
                'order_no' => strval($row['order_no'] ?? ''),
                'amount' => self::money($row['order_pay_price'] ?? 0),
                'reason' => strval($row['reconcile_message'] ?? '采购流水核算异常'),
            ];
        }, $result['exception_list'] ?? []);
    }

    private static function gatewayAnomalies(string $start, string $end): array
    {
        $rows = Db::name('payment_gateway_attempt')->whereIn('status', [1, 3, 4])
            ->whereBetweenTime('update_time', $start, $end)
            ->field('member_order_id,order_no,amount,status,error_message')->limit(50)->select()->toArray();
        return array_map(static function (array $row): array {
            return [
                'type' => 'payment_gateway_' . intval($row['status'] ?? 0),
                'member_order_id' => intval($row['member_order_id'] ?? 0),
                'order_no' => strval($row['order_no'] ?? ''),
                'amount' => self::money($row['amount'] ?? 0),
                'reason' => strval($row['error_message'] ?? '支付网关调用需要人工核对'),
            ];
        }, $rows);
    }

    private static function anomaly(string $type, array $row, string $reason): array
    {
        $amount = intval($row['pay_type'] ?? 0) === 2 ? $row['total_price'] ?? 0 : $row['pay_price'] ?? 0;
        return [
            'type' => $type,
            'member_order_id' => intval($row['id'] ?? 0),
            'order_no' => strval($row['order_no'] ?? ''),
            'amount' => self::money($amount),
            'reason' => $reason,
        ];
    }

    private static function paidAmountExpression(string $prefix = ''): string
    {
        return 'CASE WHEN ' . $prefix . 'pay_type=2 THEN ' . $prefix . 'total_price ELSE ' . $prefix . 'pay_price END';
    }

    private static function money(mixed $value): float
    {
        return round(floatval($value), 2);
    }
}
