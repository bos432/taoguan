<?php

declare(strict_types=1);

namespace app\common\model\order;

use think\Model;

class OrderBusinessEventModel extends Model
{
    protected $name = 'order_business_event';
    protected $pk = 'id';
}
