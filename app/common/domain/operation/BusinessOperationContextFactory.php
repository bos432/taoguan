<?php

declare(strict_types=1);

namespace app\common\domain\operation;

use think\facade\Request;

class BusinessOperationContextFactory
{
    public static function fromRequest(string $source, string $operatorType, int $operatorId, string $reason = ''): array
    {
        $requestId = trim((string) Request::header('X-Request-Id', Request::header('Request-Id', '')));
        if ($requestId === '') {
            $requestId = trim((string) Request::param('request_id', ''));
        }
        if ($requestId === '') {
            $requestId = 'compat:' . date('YmdHis') . ':' . bin2hex(random_bytes(8));
        }
        return BusinessOperationContext::normalize([
            'request_id' => $requestId,
            'source' => $source,
            'operator_type' => $operatorType,
            'operator_id' => $operatorId,
            'reason' => $reason,
        ]);
    }
}
