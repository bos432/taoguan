<?php


namespace app\common\model\file;

use think\model\Pivot;

/**
 * 文件标签关联模型
 */
class TagsModel extends Pivot
{
    // 表名
    protected $name = 'file_tags';
}
