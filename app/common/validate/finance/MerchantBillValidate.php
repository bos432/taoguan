<?php
namespace app\common\validate\finance;
use think\Validate;
/**
 * 账单明细验证器
 */
class MerchantBillValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  [],
        'edit'    =>  ['id', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
}
