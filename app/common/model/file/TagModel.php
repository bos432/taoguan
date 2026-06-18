<?php


namespace app\common\model\file;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 文件标签模型
 */
class TagModel extends Model
{
    // 表名
    protected $name = 'file_tag';
    // 表主键
    protected $pk = 'tag_id';
}
