<?php


// 短信配置 https://gitee.com/skyselang/easy-sms
return [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,
    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
        // 默认可用的发送网关
        'gateways' => ['Juhe'],
    ],
    // 可用的网关配置
    'gateways' => [
        // 错误日志
        'errorlog' => [
            'file' => runtime_path() . '/easysms/' . date('Ym') . '/' . date('Ymd') . 'easysms.log',
        ],
        // 聚合
        'Juhe' => [
            'app_key' => '55aff53527e27208618967f123eec4ce',
        ],
        // 阿里云
        'aliyun' => [
            'access_key_id' => '',
            'access_key_secret' => '',
            'sign_name' => '',
        ],
    ],
];
