<?php


namespace app\common\model\member;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 会员标签模型
 */
class TagModel extends Model
{
    // 表名
    protected $name = 'member_tag';
    // 表主键
    protected $pk = 'tag_id';
}
