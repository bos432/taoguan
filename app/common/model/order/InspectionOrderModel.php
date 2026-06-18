<?php
namespace app\common\model\order;
use app\common\model\goods\GoodsModel;
use app\common\model\inspection\InspectionModel;
use app\common\model\merchant\MerchantModel;
use app\common\model\trace\TraceBatchModel;
use think\Model;

class InspectionOrderModel extends Model
{
    // 表名
    protected $name = 'inspection_order';
    // 表主键
    protected $pk = 'id';
    const STATE = [
        ['value' => 0, 'label' => '待检测','code' => 'wait'],
        ['value' => 1, 'label' => '已检测','code' => 'detected'],
        ['value' => 2, 'label' => '检测失败','code' => 'error'],
    ];
    /**
     * 查询状态
     * @Author: 易军辉
     * @DateTime:2024-12-06 16:10
     * @param $key 编码或value
     * @param $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getState($key,$type=1)
    {
        foreach (self::STATE as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    case 3:
                        return $status['code']; // 返回code
                    default:
                        return $status['code']; // 未知类型，返回null
                }
            }
        }
    }
    //关联商家
    public function merchant()
    {
        return $this->hasOne(MerchantModel::class, 'id','merchant_id');
    }
    //关联商家名称
    public function getMerchantTitleAttr()
    {
        $title = $this['merchant']['title'] ?? '';
        return MerchantModel::formatDisplayTitle($title);
    }
    // 关联批次
    public function batch()
    {
        return $this->hasOne(TraceBatchModel::class, 'id','trace_batch_id');
    }
    /**
     * 获取批次号
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="批次号")
     */
    public function getBatchTitleAttr()
    {
        $title = $this['batch']['title'] ?? '';
        return $title;
    }
    // 关联商品
    public function goods()
    {
        return $this->hasOne(GoodsModel::class, 'id','goods_id');
    }
    /**
     * 获取商品名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="商品名称")
     */
    public function getGoodsTitleAttr()
    {
        $title = $this['goods']['title'] ?? '';
        $spec = $this['goods']['spec'] ?? '';
        $unit = $this['goods']['unit'] ?? '';
        if($spec){
            $title .="(".$spec.")";
        }
        if(!$spec && $unit){
            $title .="(".$unit.")";
        }
        return $title;
    }
}
