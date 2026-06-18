<?php
namespace app\common\model\trace;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class TraceTacheModel extends Model
{
    // 表名
    protected $name = 'trace_tache';
    // 表主键
    protected $pk = 'id';

    //关联模板属性
    public function attributes()
    {
        return $this->hasMany(TraceTacheAttributesModel::class, 'trace_tache_id', 'id');
    }
}
