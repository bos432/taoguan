<?php

namespace app\common\validate\system;

use think\Validate;

class SettingValidate extends Validate
{
    protected $rule = [
        'page_limit' => ['require', 'between' => '1,1000'],
        'goods_release_enabled' => ['in' => '0,1'],
        'ui_theme_style' => ['in' => 'origin,red_energy,yellow_energy,jade_modern'],
        'token_key' => ['require', 'alphaNum', 'length' => '6,32'],
        'token_exp' => ['require', 'between' => '1,8760'],
        'captcha_switch' => ['require', 'in' => '0,1'],
        'log_switch' => ['require', 'in' => '0,1'],
        'log_save_time' => ['require', 'between' => '0,99999'],
        'api_rate_num' => ['require', 'between' => '0,999'],
        'api_rate_time' => ['require', 'between' => '1,999'],
        'email_secure' => ['require'],
        'email_test' => ['require'],
    ];

    protected $message = [
        'page_limit.between' => '分页每页数量必须在 1-1000 之间',
        'goods_release_enabled.in' => '商品发布开关只能为开启或关闭',
        'ui_theme_style.in' => '前端主题样式不在允许范围内',
        'token_key.require' => 'Token 密钥不能为空',
        'token_key.alphaNum' => 'Token 密钥只能包含字母和数字',
        'token_key.length' => 'Token 密钥长度必须在 6-32 位之间',
        'token_exp.require' => 'Token 有效时长不能为空',
        'token_exp.between' => 'Token 有效时长必须在 1-8760 小时之间',
        'captcha_switch.require' => '验证码开关不能为空',
        'captcha_switch.in' => '验证码开关只能为开启或关闭',
        'log_switch.require' => '日志开关不能为空',
        'log_switch.in' => '日志开关只能为开启或关闭',
        'log_save_time.between' => '日志保留天数必须在 0-99999 之间',
        'api_rate_num.require' => '接口限流次数不能为空',
        'api_rate_num.between' => '接口限流次数必须在 0-999 之间',
        'api_rate_time.require' => '接口限流时间不能为空',
        'api_rate_time.between' => '接口限流时间必须在 1-999 秒之间',
        'email_secure.require' => 'SMTP 安全协议不能为空',
        'email_test.require' => '测试邮箱不能为空',
    ];

    protected $scene = [
        'system_edit' => ['page_limit', 'goods_release_enabled', 'ui_theme_style'],
        'token_edit' => ['token_key', 'token_exp'],
        'captcha_edit' => ['captcha_switch'],
        'log_edit' => ['log_switch', 'log_save_time'],
        'api_edit' => ['api_rate_num', 'api_rate_time'],
        'email_edit' => ['email_secure'],
        'email_test' => ['email_test'],
    ];
}
