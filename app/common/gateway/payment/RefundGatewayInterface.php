<?php

declare(strict_types=1);

namespace app\common\gateway\payment;

interface RefundGatewayInterface
{
    /** @return array{success: bool, provider_transaction_id: string, response: array, error: string} */
    public function refund(array $request): array;
}
