<?php

declare(strict_types=1);

namespace app\listener;

use app\common\service\system\UserLogService;

/**
 * 用户日志事件
 */
class UserLogListener
{
    /**
     * 事件监听处理
     * 
     * @param mixed $event
     *
     * @return mixed
     */
    public function handle($event)
    {
        UserLogService::clearLog();
    }
}
