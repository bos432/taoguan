<?php


namespace app\common\model\setting;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 协议管理模型
 */
class AccordModel extends Model
{
    // 表名
    protected $name = 'setting_accord';
    // 表主键
    protected $pk = 'accord_id';
}
