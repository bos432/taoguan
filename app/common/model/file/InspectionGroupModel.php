<?php


namespace app\common\model\file;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 文件分组模型
 */
class InspectionGroupModel extends Model
{
    // 表名
    protected $name = 'inspection_file_group';
    // 表主键
    protected $pk = 'group_id';
}
