<?php
namespace app\common\model\goods;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class GoodsLabelModel extends Model
{
    // 表名
    protected $name = 'goods_label';
    // 表主键
    protected $pk = 'id';
}
