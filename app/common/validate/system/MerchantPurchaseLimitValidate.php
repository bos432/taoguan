<?php

namespace app\common\validate\system;

use think\Validate;

class MerchantPurchaseLimitValidate extends Validate
{
    protected $rule = [
        'enabled' => ['require', 'in' => '0,1'],
        'daily_quantity_limit' => ['require', 'between' => '0,999999'],
        'daily_amount_limit' => ['require', 'float', 'egt' => '0', 'elt' => '999999999'],
    ];

    protected $message = [
        'enabled.require' => '请设置商家购买限制开关',
        'enabled.in' => '商家购买限制开关只能为开启或关闭',
        'daily_quantity_limit.require' => '请设置商家每日购买件数上限',
        'daily_quantity_limit.between' => '商家每日购买件数上限必须在 0-999999 之间',
        'daily_amount_limit.require' => '请设置商家每日购买金额上限',
        'daily_amount_limit.float' => '商家每日购买金额上限必须为数字',
        'daily_amount_limit.egt' => '商家每日购买金额上限不能小于 0',
        'daily_amount_limit.elt' => '商家每日购买金额上限超出允许范围',
    ];

    protected $scene = [
        'edit' => ['enabled', 'daily_quantity_limit', 'daily_amount_limit'],
    ];
}
