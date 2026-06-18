<?php


namespace app\common\model\setting;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 地区管理模型
 */
class RegionModel extends Model
{
    // 表名
    protected $name = 'setting_region';
    // 表主键
    protected $pk = 'region_id';
}
