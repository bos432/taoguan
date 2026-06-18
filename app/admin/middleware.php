<?php


// 应用中间件定义文件
return [
    // 日志记录中间件
    \app\admin\middleware\UserLogMiddleware::class,
    // 接口Token中间件
    \app\admin\middleware\ApiTokenMiddleware::class,
    // 接口校验中间件
    \app\admin\middleware\ApiVerifyMiddleware::class,
    // 接口速率中间件
    \think\middleware\Throttle::class,
];
