<?php


namespace app\common\model\file;

use think\model\Pivot;

/**
 * 文件标签关联模型
 */
class InspectionTagsModel extends Pivot
{
    // 表名
    protected $name = 'inspection_file_tags';
}
