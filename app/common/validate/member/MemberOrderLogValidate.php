<?php
namespace app\common\validate\member;
use think\Validate;
/**
 * 订单日志验证器
 */
class MemberOrderLogValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => 'require',
        'content' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title', 'content', ],
        'edit'    =>  ['id', 'title', 'content', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
}
