<?php

declare(strict_types=1);

namespace app\common\domain\operation;

use InvalidArgumentException;

class BusinessOperationContext
{
    public const SOURCES = ['admin-next', 'merchant-web', 'uniapp-h5', 'uniapp-weixin', 'inspection', 'legacy', 'wechat'];
    public const OPERATOR_TYPES = ['platform_admin', 'merchant_user', 'member', 'inspection_user', 'system'];

    public static function normalize(array $context): array
    {
        $requestId = trim((string) ($context['request_id'] ?? ''));
        $source = trim((string) ($context['source'] ?? ''));
        $operatorType = trim((string) ($context['operator_type'] ?? ''));
        if ($requestId === '' || strlen($requestId) > 64 || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $requestId)) {
            throw new InvalidArgumentException('Invalid business request_id');
        }
        if (!in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException('Invalid business source');
        }
        if (!in_array($operatorType, self::OPERATOR_TYPES, true)) {
            throw new InvalidArgumentException('Invalid operator_type');
        }

        return [
            'request_id' => $requestId,
            'source' => $source,
            'operator_type' => $operatorType,
            'operator_id' => max(0, (int) ($context['operator_id'] ?? 0)),
            'reason' => mb_substr(trim((string) ($context['reason'] ?? '')), 0, 500),
        ];
    }
}
