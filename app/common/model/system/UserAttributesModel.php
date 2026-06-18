<?php


namespace app\common\model\system;

use think\model\Pivot;

/**
 * 用户属性关联模型
 */
class UserAttributesModel extends Pivot
{
    // 表名
    protected $name = 'system_user_attributes';
}
