<?php


namespace app\common\model\inspection;

use think\Model;
use app\common\service\system\SettingService;
use hg\apidoc\annotation as Apidoc;

/**
 * 菜单管理模型
 */
class InspectionMenuModel extends Model
{
    // 表名
    protected $name = 'inspection_menu';
    // 表主键
    protected $pk = 'menu_id';

    public function getMenuTypeNameAttr($value, $data)
    {
        return SettingService::menuTypes($data['menu_type'] ?? 0);
    }
}
