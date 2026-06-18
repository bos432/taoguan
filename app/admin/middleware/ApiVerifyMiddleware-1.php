<?php


namespace app\admin\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;
use app\common\cache\system\UserCache;
use app\common\service\system\MenuService;
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
        $user_id = user_id();
        $menu_url = menu_url();
        
        // 特殊接口绕过权限检查（批量更换缩略图）
        if ($menu_url === 'admin/goods.Goods/batchUpdateThumbnail') {
            return $next($request);
        }

        // 用户是否系统超管
        if (!user_is_super($user_id)) {
            if ($menu_url === 'admin/merchant.Merchant/grantMobileAudit') {
                $menu_url = 'admin/merchant.Merchant/auth';
            }
            $debug    = Config::get('app.app_debug');

            // 菜单是否存在
            if (!menu_is_exist($menu_url)) {
                $msg = '接口地址不存在';
                if ($debug) {
                    $msg .= '：' . $menu_url;
                }
                exception($msg, RetCodeUtils::API_URL_ERROR);
            }

            // 菜单是否已禁用
            if (menu_is_disable($menu_url)) {
                $msg = '接口已被禁用';
                if ($debug) {
                    $msg .= '：' . $menu_url;
                }
                exception($msg, RetCodeUtils::API_URL_ERROR);
            }

            // 菜单是否免权
            if (!menu_is_unauth($menu_url)) {
                $user = UserCache::get($user_id);
                if (empty($user)) {
                    exception('登录已失效，请重新登录', RetCodeUtils::LOGIN_INVALID);
                }

                if (!in_array($menu_url, $user['roles'])) {
                    $menu = MenuService::info($menu_url);
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
