<?php


namespace app\common\validate\setting;

use think\Validate;

/**
 * 设置管理验证器
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
