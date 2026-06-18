<?php


namespace app\api\controller\setting;

use app\common\controller\BaseController;
use app\common\service\member\SettingService as MemberSetting;
use app\common\service\content\SettingService as ContentSetting;
use app\common\service\file\SettingService as FileSetting;
use app\common\service\system\GoodsReleaseSwitchService;
use app\common\service\system\UiThemeSwitchService;
use app\common\service\system\SettingService as SystemSetting;
use app\common\service\setting\SettingService;
use app\common\service\setting\LinkService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("璁剧疆")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("100")
 */
class Setting extends BaseController
{
    /**
     * @Apidoc\Title("璁剧疆淇℃伅")
     * @Apidoc\Returned("member", type="object", desc="浼氬憳璁剧疆", children={
     *   @Apidoc\Returned(ref="app\common\model\member\SettingModel", field="is_captcha_register,is_captcha_login,is_register,is_login,is_captcha_register,is_captcha_login,is_phone_register,is_phone_login,is_email_register,is_email_login"),
     *   @Apidoc\Returned(ref="app\common\service\member\SettingService\info", field="default_avatar_url,token_type,token_name")
     * })
     * @Apidoc\Returned("content", type="object", desc="鍐呭璁剧疆", children={
     *   @Apidoc\Returned(ref="app\common\model\content\SettingModel", withoutField="create_uid,update_uid,create_time,update_time"),
     *   @Apidoc\Returned(ref="app\common\service\content\SettingService\info")
     * })
     * @Apidoc\Returned("file", type="object", desc="鏂囦欢璁剧疆", children={
     *   @Apidoc\Returned(ref="app\common\model\file\SettingModel", field="is_upload_api,storage,image_ext,image_size,video_ext,video_size,audio_ext,audio_size,word_ext,word_size,other_ext,other_size,is_api_file,api_file_types,api_file_group_ids,api_file_tag_ids"),
     *   @Apidoc\Returned(ref="app\common\service\file\SettingService\info", field="accept_ext")
     * })
     * @Apidoc\Returned("setting", type="object", desc="璁剧疆绠＄悊", children={
     *   @Apidoc\Returned(ref="app\common\model\setting\SettingModel"),
     *   @Apidoc\Returned(ref="app\common\service\setting\SettingService\info")
     * })
     * @Apidoc\Returned("link", type="array", desc="鍙嬮摼鍒楄〃", children={
     *   @Apidoc\Returned(ref="app\common\model\setting\LinkModel", field="link_id,image_id,name,name_color,url,desc"),
     *   @Apidoc\Returned(ref="app\common\model\setting\LinkModel\getImageUrlAttr", field="image_url")
     * })
     * @Apidoc\NotHeaders()
     * @Apidoc\NotQuerys()
     * @Apidoc\NotParams()
     */
    public function setting()
    {
        $data['member']  = MemberSetting::info('default_avatar_url');
        $data['system'] = SystemSetting::info('system_name,page_title,logo_url,member_website,service_type,service_phone,service_qq,service_wechat,service_wechat_image_url,platform_voucher_image_id,platform_voucher_image_url,wx_approved,review_hero_title,review_hero_desc,review_intro_title,review_intro_desc,review_primary_btn_text,review_secondary_btn_text,review_intro_image_id,review_intro_image_url');
        $data['system'] = array_merge($data['system'], GoodsReleaseSwitchService::info());
        $data['system'] = array_merge($data['system'], UiThemeSwitchService::info());
        $data['system']['service_phone_list'] = $data['system']['service_phone'] ? explode(',', $data['system']['service_phone']) : [];
        $data['system']['service_qq_list'] = $data['system']['service_qq'] ? explode(',', $data['system']['service_qq']) : [];
        $data['system']['service_wechat_list'] = $data['system']['service_wechat'] ? explode(',', $data['system']['service_wechat']) : [];
        $data['system']['review_mode'] = intval($data['system']['wx_approved'] ?? 0);
        return success($data);
    }
    /**
     * @Apidoc\Title("鏌ヨ瀹㈡湇淇℃伅")
     * @Apidoc\Query(ref="app\common\model\MemberHelpModel", field="id")
     * @Apidoc\Returned(ref="app\common\model\MemberHelpModel")
     */
    public function getServiceInfo()
    {
        $data = SystemSetting::info('service_type,service_phone,service_qq,service_wechat,service_wechat_image_id,service_wechat_image_url,platform_voucher_image_id,platform_voucher_image_url');
        if($data['service_phone']){
            $data['service_phone'] = explode(',',$data['service_phone']);
        }else{
            $data['service_phone'] =[];
        }
        if($data['service_qq']){
            $data['service_qq'] = explode(',',$data['service_qq']);
        }else{
            $data['service_qq'] =[];
        }
        if($data['service_wechat']){
            $data['service_wechat'] = explode(',',$data['service_wechat']);
        }else{
            $data['service_wechat'] =[];
        }
        return success($data);
    }
}
