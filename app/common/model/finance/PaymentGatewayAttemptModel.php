<?php

declare(strict_types=1);

namespace app\common\model\finance;

use think\Model;

class PaymentGatewayAttemptModel extends Model
{
    protected $name = 'payment_gateway_attempt';
    protected $pk = 'id';
}
