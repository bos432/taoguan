<?php
namespace app\common\model\member;
use app\common\model\goods\GoodsModel;
use think\Model;

class MemberOrderDetailedModel extends Model
{
    // 表名
    protected $name = 'member_order_detailed';
    // 表主键
    protected $pk = 'id';
    // 关联商品
    public function goods()
    {
        return $this->hasOne(GoodsModel::class, 'id','goods_id');
    }
}
