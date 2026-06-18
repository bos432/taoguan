<?php


namespace app\common\model\member;

use think\model\Pivot;

/**
 * 会员分组接口关联模型
 */
class GroupApisModel extends Pivot
{
    // 表名
    protected $name = 'member_group_apis';
}
