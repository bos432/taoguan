<?php

namespace app\api\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;
use app\common\service\member\ApiService;
use app\common\service\member\MemberService;
use app\common\service\member\SettingService;
use app\common\service\utils\RetCodeUtils;

class ApiVerifyMiddleware
{
    public function handle($request, Closure $next)
    {
        $setting = SettingService::info();

        if ($setting['is_member_api']) {
            $apiUrl = api_url();
            $memberId = member_id();

            if (!member_is_super($memberId)) {
                $debug = Config::get('app.app_debug');

                if (!api_is_exist($apiUrl)) {
                    $msg = '接口地址不存在';
                    if ($debug) {
                        $msg .= '：' . $apiUrl;
                    }
                    exception($msg, RetCodeUtils::API_URL_ERROR);
                }

                if (api_is_disable($apiUrl)) {
                    $msg = '接口已被停用';
                    if ($debug) {
                        $msg .= '：' . $apiUrl;
                    }
                    exception($msg, RetCodeUtils::API_URL_ERROR);
                }

                if (!api_is_unauth($apiUrl)) {
                    $member = MemberService::info($memberId);
                    if (empty($member)) {
                        exception('登录已失效，请重新登录', RetCodeUtils::LOGIN_INVALID);
                    }

                    if (!in_array($apiUrl, $member['api_urls'])) {
                        $api = ApiService::info($apiUrl);
                        $msg = '暂无权限访问：' . $api['api_name'];
                        if ($debug) {
                            $msg .= '(' . $apiUrl . ')';
                        }
                        exception($msg, RetCodeUtils::NO_PERMISSION);
                    }
                }
            }
        }

        return $next($request);
    }
}
