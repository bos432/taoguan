<?php

declare(strict_types=1);

namespace app\common\domain\order;

use app\common\model\member\MemberOrderModel;

class OrderStateTransitionPolicy
{
    public const CREATED = 'order.created';
    public const WECHAT_PAID = 'payment.wechat_succeeded';
    public const VOUCHER_APPROVED = 'payment.voucher_approved';
    public const VOUCHER_REJECTED = 'payment.voucher_rejected';
    public const DELIVERED = 'order.delivered';
    public const PICKED_UP = 'order.picked_up';
    public const RECEIVED = 'order.received';
    public const EVALUATED = 'order.evaluated';
    public const SERVICE_REQUESTED = 'refund.requested';
    public const SERVICE_APPROVED = 'refund.service_approved';
    public const SERVICE_REJECTED = 'refund.service_rejected';
    public const REFUNDED = 'refund.completed';
    public const CANCELED = 'order.canceled';

    public static function transitions(): array
    {
        $status = static fn(string $code): int => (int) MemberOrderModel::getStatus($code, 1);

        return [
            self::CREATED => ['before' => [], 'after' => ['status' => $status('p_pay'), 'pay_status' => 0, 'refund_status' => 0]],
            self::WECHAT_PAID => ['before' => ['status' => [$status('p_pay')], 'pay_status' => [0]], 'after' => ['status' => $status('p_shipment'), 'pay_status' => 1]],
            self::VOUCHER_APPROVED => ['before' => ['status' => [$status('p_pay')], 'pay_status' => [0]], 'after' => ['status' => $status('success'), 'pay_status' => 1]],
            self::VOUCHER_REJECTED => ['before' => ['status' => [$status('p_pay')], 'pay_status' => [0]], 'after' => ['status' => $status('cancel'), 'pay_status' => 2]],
            self::DELIVERED => ['before' => ['status' => [$status('p_shipment')]], 'after' => ['status' => $status('p_receipt')]],
            self::PICKED_UP => ['before' => ['status' => [$status('p_shipment')]], 'after' => ['status' => $status('p_evaluate')]],
            self::RECEIVED => ['before' => ['status' => [$status('p_receipt')]], 'after' => ['status' => $status('p_evaluate')]],
            self::EVALUATED => ['before' => ['status' => [$status('p_evaluate')]], 'after' => ['status' => $status('success')]],
            self::SERVICE_REQUESTED => ['before' => ['status' => [$status('success')]], 'after' => ['status' => $status('service'), 'refund_status' => 1]],
            self::SERVICE_APPROVED => ['before' => ['status' => [$status('service')], 'refund_status' => [1]], 'after' => ['refund_status' => 2]],
            self::SERVICE_REJECTED => ['before' => ['status' => [$status('service')], 'refund_status' => [1]], 'after' => ['refund_status' => 3]],
            self::REFUNDED => ['before' => ['status' => [$status('service')], 'refund_status' => [1, 2]], 'after' => ['status' => $status('refund')]],
            self::CANCELED => ['before' => ['status' => [$status('p_pay')], 'pay_status' => [0]], 'after' => ['is_delete' => 1]],
        ];
    }

    public static function canApply(string $eventType, array $order): bool
    {
        $transition = self::transitions()[$eventType] ?? null;
        if ($transition === null) {
            return false;
        }

        foreach ($transition['before'] as $field => $allowedValues) {
            if (!in_array((int) ($order[$field] ?? -1), $allowedValues, true)) {
                return false;
            }
        }
        return true;
    }

    public static function after(string $eventType): array
    {
        return self::transitions()[$eventType]['after'] ?? [];
    }
}
