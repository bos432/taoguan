<?php


namespace app\common\model\member;

use think\model\Pivot;

/**
 * 会员属性关联模型
 */
class AttributesModel extends Pivot
{
    // 表名
    protected $name = 'member_attributes';
}
