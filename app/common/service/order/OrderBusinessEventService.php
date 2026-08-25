<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\domain\operation\BusinessOperationContext;
use app\common\model\order\OrderBusinessEventModel;
use InvalidArgumentException;

class OrderBusinessEventService
{
    public static function payload(string $eventType, array $before, array $after, array $context, array $metrics = []): array
    {
        $context = BusinessOperationContext::normalize($context);
        $orderId = (int) ($after['id'] ?? $before['id'] ?? 0);
        $orderNo = trim((string) ($after['order_no'] ?? $before['order_no'] ?? ''));
        if ($eventType === '' || $orderId <= 0 || $orderNo === '') {
            throw new InvalidArgumentException('Order event requires event_type, order id and order_no');
        }

        return [
            'operation_request_id' => (int) ($metrics['operation_request_id'] ?? 0) ?: null,
            'event_type' => $eventType,
            'member_order_id' => $orderId,
            'order_no' => $orderNo,
            'before_status' => self::nullableInt($before, 'status'),
            'after_status' => self::nullableInt($after, 'status'),
            'before_pay_status' => self::nullableInt($before, 'pay_status'),
            'after_pay_status' => self::nullableInt($after, 'pay_status'),
            'before_refund_status' => self::nullableInt($before, 'refund_status'),
            'after_refund_status' => self::nullableInt($after, 'refund_status'),
            'amount' => number_format((float) ($metrics['amount'] ?? 0), 2, '.', ''),
            'quantity' => max(0, (int) ($metrics['quantity'] ?? $after['total_num'] ?? $before['total_num'] ?? 0)),
            'member_id' => max(0, (int) ($after['member_id'] ?? $before['member_id'] ?? 0)),
            'merchant_id' => max(0, (int) ($after['merchant_id'] ?? $before['merchant_id'] ?? 0)),
            'operator_type' => $context['operator_type'],
            'operator_id' => $context['operator_id'],
            'source' => $context['source'],
            'request_id' => $context['request_id'],
            'reason' => $context['reason'] !== '' ? $context['reason'] : null,
            'payload_json' => empty($metrics['payload']) ? null : json_encode($metrics['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at' => (string) ($metrics['occurred_at'] ?? date('Y-m-d H:i:s')),
            'create_time' => date('Y-m-d H:i:s'),
        ];
    }

    public static function record(string $eventType, array $before, array $after, array $context, array $metrics = []): int
    {
        return (int) OrderBusinessEventModel::insertGetId(self::payload($eventType, $before, $after, $context, $metrics));
    }

    private static function nullableInt(array $row, string $field): ?int
    {
        return array_key_exists($field, $row) && $row[$field] !== null ? (int) $row[$field] : null;
    }
}
