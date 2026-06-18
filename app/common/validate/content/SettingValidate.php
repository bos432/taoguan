<?php


namespace app\common\validate\content;

use think\Validate;

/**
 * 内容设置验证器
 */
class SettingValidate extends Validate
{
    // 验证规则
    protected $rule = [];

    // 错误信息
    protected $message = [];

    // 验证场景
    protected $scene = [
        'edit' => [],
    ];
}
