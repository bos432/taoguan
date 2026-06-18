<?php


namespace app\inspection\middleware;

use Closure;
use think\Request;
use think\Response;
use app\common\service\utils\RetCodeUtils;

/**
 * 接口Token中间件
 */
class ApiTokenMiddleware
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
        // 菜单是否免登
        if (!ins_menu_is_unlogin()) {

            // 用户token获取
            $user_token = ins_user_token();
            if (empty($user_token)) {
                exception('请登录', RetCodeUtils::LOGIN_INVALID);
            }

            // 用户Token验证
            ins_user_token_verify($user_token);
        }

        return $next($request);
    }
}
