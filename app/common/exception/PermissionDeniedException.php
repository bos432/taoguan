<?php

declare(strict_types=1);

namespace app\common\exception;

use think\exception\HttpException;

class PermissionDeniedException extends HttpException
{
    public const BUSINESS_CODE = 40301;
    public const ERROR_CODE = 'AUTH_FORBIDDEN';

    public function __construct(private string $permission, string $message = '暂无权限执行此操作')
    {
        parent::__construct(403, $message);
    }

    public function permission(): string
    {
        return $this->permission;
    }
}
