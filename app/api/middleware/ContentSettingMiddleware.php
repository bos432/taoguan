<?php


namespace app\api\middleware;

use Closure;
use think\Request;
use think\Response;
use app\common\service\content\SettingService;

/**
 * 内容设置中间件
 */
class ContentSettingMiddleware
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
        $setting = SettingService::info('is_api_content');
        if (!$setting['is_api_content']) {
            return error('内容功能未开启');
        }

        return $next($request);
    }
}
