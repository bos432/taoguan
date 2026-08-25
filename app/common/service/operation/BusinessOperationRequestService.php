<?php

declare(strict_types=1);

namespace app\common\service\operation;

use app\common\domain\operation\BusinessOperationContext;
use app\common\model\order\BusinessOperationRequestModel;
use InvalidArgumentException;

class BusinessOperationRequestService
{
    public static function payload(string $operationType, array $context): array
    {
        $operationType = trim($operationType);
        if ($operationType === '' || strlen($operationType) > 64) {
            throw new InvalidArgumentException('Invalid operation_type');
        }
        $context = BusinessOperationContext::normalize($context);
        $now = date('Y-m-d H:i:s');
        return [
            'operation_type' => $operationType,
            'source' => $context['source'],
            'request_id' => $context['request_id'],
            'operator_type' => $context['operator_type'],
            'operator_id' => $context['operator_id'],
            'status' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ];
    }

    public static function begin(string $operationType, array $context): array
    {
        $payload = self::payload($operationType, $context);
        try {
            $payload['id'] = (int) BusinessOperationRequestModel::insertGetId($payload);
            $payload['duplicate'] = false;
            return $payload;
        } catch (\Throwable $throwable) {
            $existing = BusinessOperationRequestModel::where('operation_type', $payload['operation_type'])
                ->where('source', $payload['source'])
                ->where('request_id', $payload['request_id'])
                ->find();
            if (!$existing) {
                throw $throwable;
            }
            $row = $existing->toArray();
            $row['duplicate'] = true;
            return $row;
        }
    }

    public static function complete(int $id, mixed $result = null): void
    {
        self::finish($id, 1, $result, null);
    }

    public static function fail(int $id, string $message): void
    {
        self::finish($id, 2, null, mb_substr($message, 0, 500));
    }

    private static function finish(int $id, int $status, mixed $result, ?string $errorMessage): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid operation request id');
        }
        BusinessOperationRequestModel::where('id', $id)->update([
            'status' => $status,
            'result_json' => $result === null ? null : json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION),
            'error_message' => $errorMessage,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
