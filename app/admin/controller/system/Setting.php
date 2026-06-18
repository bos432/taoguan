<?php


namespace app\admin\controller\system;

use app\common\controller\BaseController;
use app\common\service\system\GoodsReleaseSwitchService;
use app\common\service\system\UiThemeSwitchService;
use app\common\validate\system\SettingValidate;
use app\common\service\system\SettingService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("系统设置")
 * @Apidoc\Group("system")
 * @Apidoc\Sort("1000")
 */
class Setting extends BaseController
{
    /**
     * @Apidoc\Title("系统设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="system_name,page_title,logo_id,favicon_id,login_bg_id,login_bg_color,page_limit")
     * @Apidoc\Returned(ref="app\common\service\system\SettingService\info", field="logo_url,favicon_url,login_bg_url")
     */
    public function systemInfo()
    {
        $data = SettingService::info('system_name,page_title,logo_id,favicon_id,login_bg_id,login_bg_color,page_limit,logo_url,favicon_url,login_bg_url,member_website,service_type,service_phone,service_qq,service_wechat,service_wechat_image_id,service_wechat_image_url,platform_voucher_image_id,platform_voucher_image_url,wx_approved,review_hero_title,review_hero_desc,review_intro_title,review_intro_desc,review_primary_btn_text,review_secondary_btn_text,review_intro_image_id,review_intro_image_url');
        $data = array_merge($data, GoodsReleaseSwitchService::info());
        $data = array_merge($data, UiThemeSwitchService::info());

        return success($data);
    }

    /**
     * @Apidoc\Title("系统设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="system_name,page_title,logo_id,favicon_id,login_bg_id,login_bg_color,page_limit")
     */
    public function systemEdit()
    {
        $param = $this->params([
            'system_name/s'    => '',
            'page_title/s'     => '',
            'logo_id/d'        => 0,
            'favicon_id/d'     => 0,
            'login_bg_id/d'    => 0,
            'login_bg_color/s' => '',
            'page_limit/d'     => 20,
            'member_website/s'     => '',
            'service_type/d'=>1,//客服中心模式：1、展示联系方式 2、在线咨询
            'service_phone/s'=>'',//客服电话，多个逗号隔开
            'service_qq/s'=>'',//客服QQ，多个逗号隔开
            'service_wechat/s'=>'',//客服微信号
            'service_wechat_image_id/d'=>0,//客服微信二维码
            'platform_voucher_image_id/d'=>0,//平台收款码
            'wx_approved/d'=>0,//小程序过审
            'review_hero_title/s' => '',
            'review_hero_desc/s' => '',
            'review_intro_title/s' => '',
            'review_intro_desc/s' => '',
            'review_primary_btn_text/s' => '',
            'review_secondary_btn_text/s' => '',
            'review_intro_image_id/d' => 0,
            'goods_release_enabled/d' => intval(GoodsReleaseSwitchService::info()['goods_release_enabled'] ?? 1),
            'ui_theme_style/s' => strval(UiThemeSwitchService::info()['ui_theme_style'] ?? 'origin'),
        ]);

        validate(SettingValidate::class)->scene('system_edit')->check($param);

        $goodsReleaseConfig = GoodsReleaseSwitchService::edit([
            'goods_release_enabled' => $param['goods_release_enabled'],
        ]);
        unset($param['goods_release_enabled']);

        $themeConfig = UiThemeSwitchService::edit([
            'ui_theme_style' => $param['ui_theme_style'],
        ]);
        unset($param['ui_theme_style']);

        $data = SettingService::edit($param);
        $data = array_merge($data, $goodsReleaseConfig);
        $data = array_merge($data, $themeConfig);

        return success($data);
    }

    /**
     * @Apidoc\Title("缓存设置信息")
     * @Apidoc\Returned(ref="app\common\service\system\SettingService\info", field="cache_type")
     */
    public function cacheInfo()
    {
        $data = SettingService::info('cache_type');

        return success($data);
    }

    /**
     * @Apidoc\Title("缓存设置清除")
     * @Apidoc\Method("POST")
     */
    public function cacheClear()
    {
        $data = SettingService::cacheClear();

        return success($data, '清除缓存成功');
    }

    /**
     * @Apidoc\Title("Token设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="token_key,token_exp,is_multi_login")
     */
    public function tokenInfo()
    {
        $data = SettingService::info('token_key,token_exp,is_multi_login');

        return success($data);
    }

    /**
     * @Apidoc\Title("Token设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="token_key,token_exp,is_multi_login")
     */
    public function tokenEdit()
    {
        $param = $this->params(['token_key/s' => '', 'token_exp/d' => 12, 'is_multi_login/d' => 0]);

        validate(SettingValidate::class)->scene('token_edit')->check($param);

        $data = SettingService::edit($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("验证码设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="captcha_switch,captcha_mode,captcha_type")
     */
    public function captchaInfo()
    {
        $data = SettingService::info('captcha_switch,captcha_mode,captcha_type');

        return success($data);
    }

    /**
     * @Apidoc\Title("验证码设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="captcha_switch,captcha_mode,captcha_type")
     */
    public function captchaEdit()
    {
        $param = $this->params(['captcha_switch/d' => 0, 'captcha_mode/d' => 1, 'captcha_type/d' => 1]);

        validate(SettingValidate::class)->scene('captcha_edit')->check($param);

        $data = SettingService::edit($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("日志设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="log_switch,log_save_time")
     */
    public function logInfo()
    {
        $data = SettingService::info('log_switch,log_save_time');

        return success($data);
    }

    /**
     * @Apidoc\Title("日志设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="log_switch,log_save_time")
     */
    public function logEdit()
    {
        $param = $this->params(['log_switch/d' => 0, 'log_save_time/d' => 0]);

        validate(SettingValidate::class)->scene('log_edit')->check($param);

        $data = SettingService::edit($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("接口设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="api_rate_num,api_rate_time")
     */
    public function apiInfo()
    {
        $data = SettingService::info('api_rate_num,api_rate_time');

        return success($data);
    }

    /**
     * @Apidoc\Title("接口设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="api_rate_num,api_rate_time")
     */
    public function apiEdit()
    {
        $param = $this->params(['api_rate_num/d' => 3, 'api_rate_time/d' => 1]);

        validate(SettingValidate::class)->scene('api_edit')->check($param);

        $data = SettingService::edit($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("邮件设置信息")
     * @Apidoc\Returned(ref="app\common\model\system\SettingModel", field="email_host,email_port,email_secure,email_username,email_password,email_setfrom,email_test")
     */
    public function emailInfo()
    {
        $data = SettingService::info('email_host,email_port,email_secure,email_username,email_password,email_setfrom,email_test');

        return success($data);
    }

    /**
     * @Apidoc\Title("邮件设置修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="email_host,email_port,email_secure,email_username,email_password,email_setfrom,email_test")
     */
    public function emailEdit()
    {
        $param = $this->params([
            'email_host/s'     => '',
            'email_port/s'     => '',
            'email_secure/s'   => 'ssl',
            'email_username/s' => '',
            'email_password/s' => '',
            'email_setfrom/s'  => '',
            'email_test/s'     => '',
        ]);

        validate(SettingValidate::class)->scene('email_edit')->check($param);

        $data = SettingService::edit($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("邮件设置测试")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\SettingModel", field="email_test")
     */
    public function emailTest()
    {
        $param = $this->params(['email_test/s' => '']);

        validate(SettingValidate::class)->scene('email_test')->check($param);

        $data = SettingService::emailTest($param);

        return success($data, '发送成功');
    }

    /**
     * @Apidoc\Title("服务器信息")
     * @Apidoc\Param("force", type="int", default=0, desc="是否强制刷新")
     */
    public function serverInfo()
    {
        $force = $this->param('force/d', 0);

        $data = SettingService::serverInfo($force);

        return success($data);
    }
}
