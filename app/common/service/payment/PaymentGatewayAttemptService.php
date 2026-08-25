<?php

declare(strict_types=1);

namespace app\common\service\payment;

use app\common\model\finance\PaymentGatewayAttemptModel;
use InvalidArgumentException;

class PaymentGatewayAttemptService
{
    public static function prepare(array $data): array
    {
        foreach (['business_type', 'provider', 'merchant_request_no', 'member_order_id', 'order_no'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException("Gateway attempt requires {$field}");
            }
        }
        $now = date('Y-m-d H:i:s');
        $payload = [
            'operation_request_id' => intval($data['operation_request_id'] ?? 0) ?: null,
            'business_type' => trim((string) $data['business_type']),
            'provider' => trim((string) $data['provider']),
            'merchant_request_no' => trim((string) $data['merchant_request_no']),
            'member_order_id' => intval($data['member_order_id']),
            'order_no' => trim((string) $data['order_no']),
            'total_amount' => number_format(floatval($data['total_amount'] ?? 0), 2, '.', ''),
            'amount' => number_format(floatval($data['amount'] ?? 0), 2, '.', ''),
            'status' => 0,
            'attempt_count' => 0,
            'request_json' => empty($data['request']) ? null : json_encode($data['request'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'create_time' => $now,
            'update_time' => $now,
        ];
        try {
            $payload['id'] = (int) PaymentGatewayAttemptModel::insertGetId($payload);
            $payload['duplicate'] = false;
            return $payload;
        } catch (\Throwable $throwable) {
            $existing = PaymentGatewayAttemptModel::where('provider', $payload['provider'])
                ->where('business_type', $payload['business_type'])
                ->where('merchant_request_no', $payload['merchant_request_no'])->find();
            if (!$existing) {
                throw $throwable;
            }
            $row = $existing->toArray();
            $row['duplicate'] = true;
            return $row;
        }
    }

    public static function markRequesting(int $id): void
    {
        self::update($id, ['status' => 1, 'attempt_count' => ['inc', 1], 'requested_at' => date('Y-m-d H:i:s')]);
    }

    public static function succeed(int $id, array $result): void
    {
        self::update($id, [
            'status' => 2,
            'provider_transaction_id' => (string) ($result['provider_transaction_id'] ?? ''),
            'response_json' => json_encode($result['response'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function fail(int $id, array $result, bool $unknown = false): void
    {
        self::update($id, [
            'status' => $unknown ? 4 : 3,
            'response_json' => json_encode($result['response'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'error_message' => mb_substr((string) ($result['error'] ?? '支付网关调用失败'), 0, 500),
            'completed_at' => $unknown ? null : date('Y-m-d H:i:s'),
        ]);
    }

    private static function update(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid gateway attempt id');
        }
        $increment = $data['attempt_count'] ?? null;
        unset($data['attempt_count']);
        $data['update_time'] = date('Y-m-d H:i:s');
        $query = PaymentGatewayAttemptModel::where('id', $id);
        if (is_array($increment) && $increment[0] === 'inc') {
            $query->inc('attempt_count', intval($increment[1]));
        }
        $query->update($data);
    }
}
