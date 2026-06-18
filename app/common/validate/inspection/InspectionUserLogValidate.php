<?php


namespace app\common\validate\inspection;

use think\Validate;

/**
 * 用户日志验证器
 */
class InspectionUserLogValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids'    => ['require', 'array'],
        'log_id' => ['require'],
    ];

    // 错误信息
    protected $message = [];

    // 验证场景
    protected $scene = [
        'info' => ['log_id'],
        'dele' => ['ids'],
    ];
}
