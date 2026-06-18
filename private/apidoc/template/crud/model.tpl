<?php


namespace {$tables[0].namespace};

use think\Model;
use hg\apidoc\annotation as Apidoc;

class {$tables[0].model_name} extends Model
{
    // 表名
    protected $name = '{$tables[0].table_name}';
    // 表主键
    protected $pk = '{$custom.field_pk}';
}
