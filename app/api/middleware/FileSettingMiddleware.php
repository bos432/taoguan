<?php


namespace app\api\middleware;

use Closure;
use think\Request;
use think\Response;
use app\common\service\file\SettingService;

/**
 * 文件设置中间件
 */
class FileSettingMiddleware
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
        $setting = SettingService::info('is_api_file');
        if (!$setting['is_api_file']) {
            return error('文件功能未开启');
        }

        return $next($request);
    }
}
