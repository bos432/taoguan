<?php


namespace app\common\model\system;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 职位管理模型
 */
class PostModel extends Model
{
    // 表名
    protected $name = 'system_post';
    // 表主键
    protected $pk = 'post_id';
}
