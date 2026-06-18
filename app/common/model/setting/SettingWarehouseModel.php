<?php
namespace app\common\model\setting;
use think\Model;

class SettingWarehouseModel extends Model
{
    // 表名
    protected $name = 'setting_warehouse';
    // 表主键
    protected $pk = 'id';
    // 关联大厅
    public function hall()
    {
        return $this->hasOne(SettingHallModel::class, 'id','setting_hall_id');
    }
    /**
     * 获取大厅名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getHallTitleAttr()
    {
        $title = $this['hall']['title'] ?? '';
        return $title;
    }
}
