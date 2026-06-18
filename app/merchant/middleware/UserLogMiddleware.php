<?php


namespace app\merchant\middleware;

use app\common\service\merchant\MerchantUserLogService;
use Closure;
use think\Request;
use think\Response;

/**
 * 日志记录中间件
 */
class UserLogMiddleware
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
        $response = $next($request);

        $user_id = mer_user_id();
        if ($user_id) {
            $response_data = $response->getData();
            if (isset($response_data['code'])) {
                $user_log['response_code'] = $response_data['code'];
            }
            if (isset($response_data['msg'])) {
                $user_log['response_msg'] = $response_data['msg'];
            } else {
                if (isset($response_data['message'])) {
                    $user_log['response_msg'] = $response_data['message'];
                }
            }
            $user_log['mer_user_id'] = $user_id;
            $user_log['mer_id'] = mer_id();
            MerchantUserLogService::add($user_log);
        }

        return $response;
    }
}
