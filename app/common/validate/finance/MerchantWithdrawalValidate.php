<?php
namespace app\common\validate\finance;
use app\common\model\merchant\MerchantModel;
use think\Validate;
/**
 * 商家提现验证器
 */
class MerchantWithdrawalValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
        'code' => ['require'],
        'amount' => ['require', 'checkAmountExisted'],
        'auth_status' => ['require', 'in' => '1,2,3'],
    ];
    // 错误信息
    protected $message = [
        'amount.require' => '请输入提现金额',
        'code.require' => '请选择提现方式',
        'auth_status.require' => '请选择审核状态',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['amount'],
        'edit'    =>  ['id', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'auth' =>  ['ids', 'auth_status'],
        'withdrawalDownload' =>  ['ids', 'code'],
    ];
    //验证必填项
    protected function checkCodeExisted($value, $rule, $data = [])
    {
        switch ($data['code']){
            case 'OnLineAlipay':
                if(!isset($data['merchant_account_id']) || !$data['merchant_account_id']){
                    return '请选择支付宝账号';
                }
                break;
            case 'Alipay':
                if(!isset($data['account']) || !$data['account']){
                    return '请输入支付宝账号';
                }
                break;
            case 'WeChat':
                if(!isset($data['image_id']) || !$data['image_id']){
                    return '请上传收款码';
                }
                break;
            case 'Card':
                if(!isset($data['merchant_account_id']) || !$data['merchant_account_id']){
                    return '请选择银行卡';
                }
                break;
        }
        return true;
    }
    //验证金额
    protected function checkAmountExisted($value, $rule, $data = [])
    {
        if($data['amount']<=0){
            return '提现金额不能小于等于0';
        }
        $merchant = MerchantModel::where('id',mer_id())->field('id,mer_money')->find();
        if($data['amount']>$merchant['mer_money']){
            return '您的本金余额不足';
        }
        return true;
    }
}
