<?php

declare(strict_types=1);

namespace app\listener;

use app\common\service\member\LogService;

/**
 * 会员日志事件
 */
class MemberLogListener
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
        LogService::clearLog();
    }
}
