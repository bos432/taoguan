<?php


namespace app\common\model\inspection;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 角色管理模型
 */
class InspectionRoleModel extends Model
{
    // 表名
    protected $name = 'inspection_role';
    // 表主键
    protected $pk = 'role_id';

    // 关联菜单
    public function menus()
    {
        return $this->belongsToMany(InspectionMenuModel::class, InspectionRoleMenusModel::class, 'menu_id', 'role_id');
    }
    /**
     * 获取菜单id
     * @Apidoc\Field("")
     * @Apidoc\AddField("menu_ids", type="array", desc="菜单id")
     */
    public function getMenuIdsAttr()
    {
        return relation_fields($this['menus'], 'menu_id');
    }
}
