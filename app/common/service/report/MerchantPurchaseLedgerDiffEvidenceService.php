<?php

declare(strict_types=1);

namespace app\common\service\report;

use app\common\service\order\OrderTimelineQueryService;
use think\facade\Db;

class MerchantPurchaseLedgerDiffEvidenceService
{
    public static function enrich(array $result): array
    {
        $cache = [];
        $summary = ['order_ids' => [], 'event_count' => 0, 'legacy_log_count' => 0, 'coverage_counts' => []];
        $matchType = strval($result['match_type'] ?? 'none');
        $message = strval($result['message'] ?? '');

        foreach (['orders', 'candidate_orders'] as $key) {
            $result[$key] = self::enrichRows($result[$key] ?? [], $matchType, $message, $cache, $summary);
        }

        $result['evidence_summary'] = [
            'order_count' => count($summary['order_ids']),
            'event_count' => $summary['event_count'],
            'legacy_log_count' => $summary['legacy_log_count'],
            'coverage_counts' => $summary['coverage_counts'],
        ];
        return $result;
    }

    private static function enrichRows(array $rows, string $matchType, string $message, array &$cache, array &$summary): array
    {
        foreach ($rows as &$row) {
            $orderId = intval($row['member_order_id'] ?? 0);
            if ($orderId <= 0 && trim(strval($row['order_no'] ?? '')) !== '') {
                $orderId = intval(Db::name('member_order')->where('order_no', trim(strval($row['order_no'])))->value('id'));
            }
            $row['member_order_id'] = $orderId;
            $row['match_evidence'] = [
                'match_type' => $matchType,
                'side' => strval($row['side'] ?? ''),
                'matched_amount' => self::money($row['amount'] ?? $row['total'] ?? 0),
                'matched_quantity' => intval($row['quantity'] ?? 0),
                'remaining_amount' => self::money($row['remaining_amount'] ?? $row['amount'] ?? 0),
                'remaining_quantity' => intval($row['remaining_quantity'] ?? $row['quantity'] ?? 0),
                'reason_code' => strval($row['diagnosis_type'] ?? $matchType),
                'reason' => strval($row['diagnosis_message'] ?? $message),
            ];
            if ($orderId <= 0) {
                $row['timeline'] = null;
                $row['timeline_error'] = '未找到对应订单，无法加载流转证据';
                continue;
            }
            if (!array_key_exists($orderId, $cache)) {
                try {
                    $cache[$orderId] = OrderTimelineQueryService::byOrderId($orderId);
                } catch (\Throwable $throwable) {
                    $cache[$orderId] = ['error' => $throwable->getMessage()];
                }
            }
            if (isset($cache[$orderId]['error'])) {
                $row['timeline'] = null;
                $row['timeline_error'] = strval($cache[$orderId]['error']);
                continue;
            }
            $row['timeline'] = $cache[$orderId];
            $row['timeline_error'] = '';
            if (!isset($summary['order_ids'][$orderId])) {
                $summary['order_ids'][$orderId] = $orderId;
                $coverage = strval($row['timeline']['coverage'] ?? 'legacy_only');
                $summary['event_count'] += count($row['timeline']['events'] ?? []);
                $summary['legacy_log_count'] += count($row['timeline']['legacy_logs'] ?? []);
                $summary['coverage_counts'][$coverage] = intval($summary['coverage_counts'][$coverage] ?? 0) + 1;
            }
        }
        unset($row);
        return $rows;
    }

    private static function money(mixed $value): float
    {
        return round(floatval($value), 2);
    }
}
