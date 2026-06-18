<?php
namespace app\common\validate\order;
use think\Validate;
/**
 * 检测订单验证器
 */
class InspectionOrderValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'inspection_id' => 'require',
        'inspection_state'=> 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'inspection_state.require' => '请选择检测状态',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['inspection_id', ],
        'edit'    =>  ['id', 'inspection_state', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
}
