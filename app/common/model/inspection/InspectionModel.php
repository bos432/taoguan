<?php

namespace app\common\model\inspection;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class InspectionModel extends Model
{
    // 表名
    protected $name = 'inspection';
    // 表主键
    protected $pk = 'id';
}
