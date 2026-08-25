<?php

declare(strict_types=1);

namespace app\common\gateway\payment;

interface PrepaymentGatewayInterface
{
    /** @return array{success: bool, bridge_config: array, provider_transaction_id: string, response: array, error: string} */
    public function prepay(array $request): array;
}
