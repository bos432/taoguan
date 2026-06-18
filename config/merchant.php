<?php
// merchant配置
return [
    // token方式：header、param
    'token_type' => env('merchant.token_type', 'header'),
    // token名称；前后端必须一致
    'token_name' => env('merchant.token_name', 'MerchantToken'),

    // 系统超管用户ID（所有权限）
    'super_ids' => env('merchant.super_ids', [1]),
    // 系统超管用户是否隐藏
    'super_hide' => env('merchant.super_hide', true),
    // 系统超管用户上传文件大小是否不受限制
    'super_upload_size' => env('merchant.super_upload_size', false),

    // 菜单免登url（无需登录）
    'menu_is_unlogin' => [
        'merchant/system.Login/setting',
        'merchant/system.Login/captcha',
        'merchant/system.Login/login',
    ],

    // 菜单免权url（无需权限）
    'menu_is_unauth' => [
        'merchant/system.Index/index',
        'merchant/system.Index/notice',
        'merchant/system.Notice/info',
        'merchant/system.Login/logout',
        'merchant/system.UserCenter/info',
        'merchant/system.UserPreference/bootstrap',
        'merchant/system.UserPreference/runtime',
        'merchant/system.UserPreference/list',
        'merchant/system.UserPreference/save',
        'merchant/system.UserPreference/batchSave',
        'merchant/system.UserPreference/dele',
        'merchant/system.UserPreference/clear',
    ],

    // 菜单免限url（不限速率）
    'menu_is_unrate' => [
        'merchant/file.File/add',
        'merchant/file.File/list',
    ],

    // 日志记录请求参数排除字段（敏感、内容多等信息）
    'log_param_without' => [
        'password',
        'password_old',
        'password_new',
        'password_confirm',
        'content',
        'images',
        'videos',
        'audios',
        'words',
        'annexs',
        'others',
        'token_key',
    ],

    // 地区级别：1省2市3区县4街道乡镇
    'region_level' => 3,
];
