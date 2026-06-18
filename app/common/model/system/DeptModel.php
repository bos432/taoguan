<?php


namespace app\common\model\system;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 部门管理模型
 */
class DeptModel extends Model
{
    // 表名
    protected $name = 'system_dept';
    // 表主键
    protected $pk = 'dept_id';
}
