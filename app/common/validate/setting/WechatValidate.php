<?php


namespace app\common\validate\setting;

use think\Validate;

/**
 * 微信设置验证器
 */
class WechatValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'appid'     => ['require'],
        'appsecret' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'appid.require'     => '请输入AppID',
        'appsecret.require' => '请输入AppSecret',
    ];

    // 验证场景
    protected $scene = [
        'offiEdit' => ['appid', 'appsecret'],
        'miniEdit' => ['appid', 'appsecret'],
    ];
}
