<?php
namespace app\common\model\trace;
use app\common\model\goods\GoodsModel;
use app\common\model\merchant\MerchantModel;
use app\common\model\setting\SettingCallModel;
use app\common\model\setting\SettingWarehouseModel;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class TraceBatchModel extends Model
{
    // 表名
    protected $name = 'trace_batch';
    // 表主键
    protected $pk = 'id';
    //审核状态:0、待审核 1、审核通过 2、审核失败
    const AUTH_STATUS = [
        ['value' => 0, 'label' => '待审核','code' => 'auth'],
        ['value' => 1, 'label' => '审核通过','code' => 'auth_success'],
        ['value' => 2, 'label' => '审核失败','code' => 'auth_error'],
    ];

    /**
     * @title: 查询审核状态
     * @author：易军辉
     * @date：2024/12/21
     * @param $key
     * @param int $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getAuthStatus($key,$type=1)
    {
        foreach (self::AUTH_STATUS as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    case 3:
                        return $status['code']; // 返回code
                    default:
                        return null;
                }
            }
        }
    }
    //生成批次号
    public static function createNo($where, $merchant_id)
    {
        // 生成批次号前缀，例如：SC320241102
        $prefix = 'SC';
        // 获取当前日期
        $date = date('Ymd');
        if($merchant_id && $merchant_id>0){
            $prefix .=$merchant_id;
        }else{
            $prefix .=operate_user_id();
        }
        $prefix .=$date;

        // 查询数据库中该前缀的最大批次号
        $maxNo = self::where($where)
            ->when($merchant_id,function ($query)use($merchant_id){
                $query->where('merchant_id',$merchant_id);
            })
            ->where('title', 'like', $prefix . '%')
            ->order('title', 'desc') // 降序排序以找到最大的批次号
            ->value('title'); // 直接获取 title 的最大值
        if ($maxNo) {
            // 提取当前最大批次号的最后三位数字，并加 1
            $currentNumber = (int)substr($maxNo, -3);
            $newNumber = str_pad($currentNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // 如果没有最大值，从 001 开始
            $newNumber = '001';
        }

        // 生成最终批次号
        $batchNo = $prefix . $newNumber;

        return $batchNo;
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

    /**
     * @title:根据批次号查询商品id
     * @author：易军辉
     * @date：2024/11/2
     * @param int $id
     */
    public static function getGoodsId($id=0){
        return self::where('id',$id)->value('goods_id');
    }


    // 关联秤
    public function call()
    {
        return $this->hasOne(SettingCallModel::class, 'id','setting_call_id');
    }
    //获取秤名称
    public function getCallTitleAttr()
    {
        $title = $this['call']['title'] ?? '';
        return $title;
    }
    // 关联仓库
    public function warehouse()
    {
        return $this->hasOne(SettingWarehouseModel::class, 'id','setting_warehouse_id');
    }
    //获取仓库名称
    public function getWarehouseTitleAttr()
    {
        $title = $this['warehouse']['title'] ?? '';
        return $title;
    }
    // 关联商家
    public function merchant()
    {
        return $this->hasOne(MerchantModel::class, 'id','merchant_id');
    }
    /**
     * 获取商家名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getMerchantTitleAttr()
    {
        $title = $this['merchant']['title'] ?? '';
        return MerchantModel::formatDisplayTitle($title);
    }
}
