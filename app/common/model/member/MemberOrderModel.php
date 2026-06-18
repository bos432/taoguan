<?php
namespace app\common\model\member;
use app\common\model\merchant\MerchantModel;
use app\common\model\order\MemberOrderLogModel;
use think\Model;

class MemberOrderModel extends Model
{
    // 表名
    protected $name = 'member_order';
    // 表主键
    protected $pk = 'id';
    const STATUS = [
        ['value' => 0, 'label' => '待付款','code' => 'p_pay'],
        ['value' => 1, 'label' => '待发货','code' => 'p_shipment'],
        ['value' => 2, 'label' => '待收货','code' => 'p_receipt'],
        ['value' => 3, 'label' => '待评价','code' => 'p_evaluate'],
        ['value' => 4, 'label' => '完成','code' => 'success'],
        ['value' => 5, 'label' => '售后','code' => 'service'],
        ['value' => 6, 'label' => '已退款','code' => 'refund'],
    ];
    //日志角色
    const ROLE_TYPE = [
        ['value' => 1, 'label' => '平台','code' => 'admin'],
        ['value' => 2, 'label' => '商户','code' => 'mer'],
        ['value' => 3, 'label' => '用户','code' => 'member'],
        ['value' => 4, 'label' => '系统','code' => 'system'],
    ];
    //支付方式
    const PAY_TYPE = [
        ['value' => 1, 'label' => '微信支付','code' => 'weChat'],
        ['value' => 2, 'label' => '支付凭证','code' => 'voucher'],
    ];
    /**
     * 查询状态
     * @Author: 易军辉
     * @DateTime:2024-12-15 16:10
     * @param $key 编码或value
     * @param $type 1、查询value  2、查询名称 3、查询编码
     * @return mixed|void
     */
    public static  function getStatus($key,$type=1)
    {
        foreach (self::STATUS as $status) {
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
    //查询日志角色
    public static  function getRoleType($key,$type=1)
    {
        foreach (self::ROLE_TYPE as $status) {
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
    //查询支付方式
    public static  function getPayType($key,$type=1)
    {
        foreach (self::PAY_TYPE as $status) {
            if ($status['code'] == $key || $status['value'] == $key) {
                switch ($type) {
                    case 1:
                        return $status['value']; // 返回value
                    case 2:
                        return $status['label']; // 返回名称
                    case 3:
                        return $status['code']; // 返回code
                    default:
                        return $status; // 未知类型，返回null
                }
            }
        }
    }
    /**
     * @title:批量生成订单号
     * @author：易军辉
     * @date：2024/12/14
     * @param $order_num 订单号数量
     * @return array
     */
    public static function generateOrderNumber($order_num)
    {
        $time_on = date('ymdHis');
        $order_ons = array();
        $i =0;
        while (count($order_ons) < $order_num) {
            $time_on=bcadd($time_on,1,0);
            $unique_prefix = bin2hex(random_bytes(1)); // 随机前缀，确保分布式唯一性
            $no = $time_on.$unique_prefix.str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            if (!in_array($no, $order_ons)) {
                $order_ons[] = $no;
                $i++;
            }
        }
        //查询是否有重复
        $shop_orders = self::where('order_no','in', $order_ons)->field('order_no')->select();
        foreach ($shop_orders as $key=>$val) {
            $index = array_search($val['order_on'], $order_ons);
            if ($index !== false) {
                $order_ons[$index] = $val['order_on'].$key;
            }
        }
        return $order_ons;
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
        if (intval($this['merchant_id'] ?? 0) === 0) {
            return '平台自营';
        }
        $title = $this['merchant']['title'] ?? '';
        return MerchantModel::formatDisplayTitle($title);
    }
    //关联订单详情
    public function detaileds()
    {
        return $this->hasMany(MemberOrderDetailedModel::class, 'member_order_id', 'id');
    }
    // 关联用户
    public function member()
    {
        return $this->hasOne(MemberModel::class, 'member_id','member_id');
    }
    /**
     * 获取用户名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("category_names", type="string", desc="分类名称")
     */
    public function getMemberTitleAttr()
    {
        $title = $this->hideSpace(($this['member']['nickname'] ?? ''),1);
        return $title;
    }
    /**
     * 详情问答（间隔隐藏字符）
     * @Author: 易军辉
     * @DateTime:2024-09-07 19:54
     * @param $str
     */
    private function hideSpace($str)
    {
        $length = mb_strlen($str); // 获取字符串长度，支持多字节字符

        // 小于3个字不需要隐藏
        if ($length < 3) {
            return $str;
        }

        // 如果字符串长度为3，只隐藏中间一个字
        if ($length == 3) {
            return mb_substr($str, 0, 1) . '*' . mb_substr($str, 2, 1);
        }

        // 对于长度大于3的情况，将中间3个字符替换成***
        $start = mb_substr($str, 0, floor(($length - 3) / 2)); // 前半部分
        $end = mb_substr($str, -ceil(($length - 3) / 2)); // 后半部分

        return $start . '***' . $end;
    }
    //关联订单日志
    public function logs()
    {
        return $this->hasMany(MemberOrderLogModel::class, 'member_order_id', 'id')->order('create_time','desc');
    }
}
