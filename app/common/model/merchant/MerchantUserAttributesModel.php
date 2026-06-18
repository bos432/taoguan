<?php


namespace app\common\model\merchant;

use think\model\Pivot;

/**
 * 用户属性关联模型
 */
class MerchantUserAttributesModel extends Pivot
{
    // 表名
    protected $name = 'merchant_user_attributes';
}
