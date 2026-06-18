<?php


namespace app\common\model\setting;

use think\model\Pivot;

/**
 * 设置文件关联模型
 */
class SettingFilesModel extends Pivot
{
    // 表名
    protected $name = 'setting_files';
}
