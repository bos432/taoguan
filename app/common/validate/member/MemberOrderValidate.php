<?php
namespace app\common\validate\member;
use think\Validate;
/**
 * 订单管理验证器
 */
class MemberOrderValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
        'source' => 'require',
        'goods_ids' => 'require',
        'member_id' => 'require',
        'total_num' => 'require',
        'total_price' => 'require',
        'merchant_list' => ['require', 'array'],
        'delivery_type' => ['require', 'checkDeliveryExisted'],
        'setting_delivery_id' => 'require',
        'kuaidi_order_no' => 'require',
        'evaluate_content' => 'require',
        'evaluate_num' => 'require',
        'refund_reason_wap_explain' => 'require',
        'refund_reason_wap_imgs' => 'require',
        'refund_type' => 'require',
        'refund_price' => 'require',
        'refund_status' => 'require',
        'refund_express' => 'require',
        'pay_type' =>  ['require', 'in' => '1,2'],
        'pay_status'=>  ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'source.require'=>'系统繁忙，请稍后重试',
        'goods_ids.require'=>'请选择需要结算的商品',
        'member_id.require'=>'请登录',
        'total_num.require'=>'结算数量异常',
        'total_price.require'=>'结算金额异常',
        'merchant_list.require'=>'结算商品异常',
        'delivery_type.require'=>'请选择收货方式',
        'setting_delivery_id.require'=>'请选择快递公司',
        'kuaidi_order_no.require'=>'请输入运单号',
        'pick_up_code.require'=>'请输入提货码',
        'evaluate_content.require'=>'请输入评价内容',
        'evaluate_num.require'=>'请选择评分',
        'refund_reason_wap_explain.require'=>'请输入申请原因',
        'refund_reason_wap_imgs.require'=>'请上传说明图片',
        'refund_type.require'=>'请选择售后类型',
        'refund_price.require'=>'请输入退款金额',
        'refund_status.require'=>'请选择售后状态',
        'refund_delivery_id.require'=>'请选择快递公司',
        'refund_express.require'=>'请输入快递单号',
        'pay_type.require'=>'请选择支付方式',
        'pay_status.require'=>'请选择支付状态',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  [],
        'edit'    =>  ['id', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'getConfirmOrder'=>  ['source','goods_ids','member_id'],
        'confirmOrder'=>  ['member_id','total_num','total_price','merchant_list','delivery_type','pay_type'],
        'delivery'=>['ids','setting_delivery_id','kuaidi_order_no'],
        'logistics'=>['id'],
        'cancelOrder'=>['id'],
        'payOrder'=>['id'],
        'getOrderCode'=>['id'],
        'takeDelivery'=>['ids','pick_up_code'],
        'confirmReceipt'=>['id'],
        'submitEvaluation'=>['id','evaluate_content','evaluate_num'],
        'submitService'=>['id','refund_reason_wap_explain','refund_reason_wap_imgs'],
        'serviceOrder'=>['id','refund_type','refund_price','refund_status'],
        'returnGoods'=>['id','refund_express','refund_delivery_id'],
        'orderPayAuth'=>['ids','pay_status']
    ];
    // 自定义验证规则：验证收货方式
    protected function checkDeliveryExisted($value, $rule, $data = [])
    {
        // 手机号验证正则
        $phoneRegex = '/^1[3-9]\d{9}$/';
        switch ($data['delivery_type']){
            case 1://快递配送
                if(!isset($data['take_name'])){
                    return "请输入收货人";
                }
                if (!isset($data['take_phone']) || !preg_match($phoneRegex, $data['take_phone'])) {
                    return "请输入正确的联系电话";
                }
                if(!isset($data['take_region'][0]) || !isset($data['take_region'][1])){
                    return "请选择收货地区";
                }
                if(!isset($data['take_address'])){
                    return "请输入详细地址";
                }
                break;
            case 2://门店自提
                if(!isset($data['self_name'])){
                    return "请输入联系人";
                }
                if (!isset($data['self_phone']) || !preg_match($phoneRegex, $data['self_phone'])) {
                    return "请输入正确的预留手机号";
                }
                break;
        }
        return true;
    }
}
