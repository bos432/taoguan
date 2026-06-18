<?php

namespace app\api\middleware;

use Closure;
use app\common\service\utils\RetCodeUtils;

class ApiTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        $currentApiUrl = api_url();
        if (in_array($currentApiUrl, [
            'api/setting.Accord/status',
            'api/setting.Notice/popupInfo',
            'api/setting.Notice/popupRead',
        ], true)) {
            return $next($request);
        }

        if (!api_is_unlogin()) {
            $apiToken = api_token();
            if (empty($apiToken)) {
                return error('请登录', [], RetCodeUtils::LOGIN_INVALID);
            }

            try {
                api_token_verify($apiToken);
            } catch (\Throwable $e) {
                return error($e->getMessage() ?: '登录已失效，请重新登录', [], RetCodeUtils::LOGIN_INVALID);
            }
        }

        return $next($request);
    }
}
