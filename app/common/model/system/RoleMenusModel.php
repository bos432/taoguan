<?php


namespace app\common\model\system;

use think\model\Pivot;

/**
 * 角色菜单关联模型
 */
class RoleMenusModel extends Pivot
{
    // 表名
    protected $name = 'system_role_menus';
}
