<?php
namespace app\common\model\goods;
use app\common\model\merchant\MerchantModel;
use think\Model;

class GoodsInventoryModel extends Model
{
    // 表名
    protected $name = 'goods_inventory';
    // 表主键
    protected $pk = 'id';
}
