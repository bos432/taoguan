<?php


// 事件定义文件
return [
    // 事件绑定
    'bind'      => [],

    // 事件监听
    'listen'    => [
        'AppInit'   => [],
        'HttpRun'   => [],
        'HttpEnd'   => [],
        'LogLevel'  => [],
        'LogWrite'  => [],
        // 用户日志事件
        'UserLog'   => ['app\listener\UserLogListener'],
        // 会员日志事件
        'MemberLog' => ['app\listener\MemberLogListener'],
    ],

    // 事件订阅
    'subscribe' => [],
];
