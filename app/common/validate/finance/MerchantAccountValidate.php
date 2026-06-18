<?php
namespace app\common\validate\finance;
use app\common\model\finance\MerchantAccountModel;
use think\Validate;
/**
 * 商家银行卡管理验证器
 */
class MerchantAccountValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
        'name' => 'require',
        'alipay_account' => 'require',
        'card_no' => ['require', 'checkExisted'],
        'type' => ['require', 'in' => '1,2'],
        'bank' => 'require',
        'bank_branch' => 'require',
    ];
    // 错误信息
    protected $message = [
        'alipay_account.require' => '请输入支付宝账号',
        'name.require' => '请输入姓名',
        'card_no.require' => '请输入银行卡号',
        'type.require' => '请选择账户',
        'bank.require' => '请输入开户银行',
        'bank_branch.require' => '请输入开户支行',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['name','card_no'],
        'edit'    =>  ['id','name','card_no'],
        'add1'     =>  ['type','name','bank','bank_branch','card_no'],
        'add2'     =>  ['type','alipay_account'],
        'edit1'     =>  ['id','type','name','bank','bank_branch','card_no'],
        'edit2'     =>  ['id','type','alipay_account'],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'addAlipay'=>  ['alipay_account'],
    ];
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new MerchantAccountModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['card_no', '=', $data['card_no']];
        $where[] = ['source', '=', $data['source']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '银行卡号已存在：' . $data['card_no'];
        }

        return true;
    }
}
