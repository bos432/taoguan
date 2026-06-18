<?php


namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Event;

/**
 * 日志清除中间件
 */
class LogClear
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
        // 用户日志清除
        Event::trigger('UserLog');

        // 会员日志清除
        Event::trigger('MemberLog');

        return $next($request);
    }
}
