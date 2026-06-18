<?php


namespace app\inspection\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;
use app\common\cache\inspection\InspectionUserCache;
use app\common\service\inspection\InspectionMenuService;
use app\common\service\utils\RetCodeUtils;

/**
 * 接口校验中间件
 */
class ApiVerifyMiddleware
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $user_id = ins_user_id();

        // 用户是否系统超管
        if (!ins_user_is_super($user_id)) {
            $menu_url = ins_menu_url();
            $debug    = Config::get('app.app_debug');

            // 菜单是否存在
            if (!ins_menu_is_exist($menu_url)) {
                $msg = '接口地址不存在';
                if ($debug) {
                    $msg .= '：' . $menu_url;
                }
                exception($msg, RetCodeUtils::API_URL_ERROR);
            }

            // 菜单是否已禁用
            if (ins_menu_is_disable($menu_url)) {
                $msg = '接口已被禁用';
                if ($debug) {
                    $msg .= '：' . $menu_url;
                }
                exception($msg, RetCodeUtils::API_URL_ERROR);
            }

            // 菜单是否免权
            if (!ins_menu_is_unauth($menu_url)) {
                $user = InspectionUserCache::get($user_id);
                if (empty($user)) {
                    exception('登录已失效，请重新登录', RetCodeUtils::LOGIN_INVALID);
                }

                if (!in_array($menu_url, $user['roles'])) {
                    $menu = InspectionMenuService::info($menu_url);
                    $msg = '你没有权限操作：' . $menu['menu_name'];
                    if ($debug) {
                        $msg .= '(' . $menu_url . ')';
                    }
                    exception($msg, RetCodeUtils::NO_PERMISSION);
                }
            }
        }

        return $next($request);
    }
}
