<?php
namespace app\common\model\finance;
use think\Model;

class MerchantBillModel extends Model
{
    // 表名
    protected $name = 'merchant_bill';
    // 表主键
    protected $pk = 'id';
    const TYPE = [
        ['value' => 1, 'label' => '收入','code' => 'RECHARGE'],
        ['value' => 2, 'label' => '支出','code' => 'CONSUMPTION'],
        ['value' => 3, 'label' => '提现','code' => 'WITHDRAWAL'],
        ['value' => 4, 'label' => '退款','code' => 'REFUND']
    ];

    /**
     * 查询类型
     * @Author: 易军辉
     * @DateTime:2024-06-18 16:10
     * @param $key 编码或value
     * @param $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getType($key,$type=1)
    {
        foreach (self::TYPE as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    default:
                        return $status['code']; // 未知类型，返回null
                }
            }
        }
    }
}
