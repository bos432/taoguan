<?php
// inspection配置
return [
    // token方式：header、param
    'token_type' => env('inspection.token_type', 'header'),
    // token名称；前后端必须一致
    'token_name' => env('inspection.token_name', 'InspectionToken'),

    // 系统超管用户ID（所有权限）
    'super_ids' => env('inspection.super_ids', [1]),
    // 系统超管用户是否隐藏
    'super_hide' => env('inspection.super_hide', true),
    // 系统超管用户上传文件大小是否不受限制
    'super_upload_size' => env('inspection.super_upload_size', false),

    // 菜单免登url（无需登录）
    'menu_is_unlogin' => [
        'inspection/system.Login/setting',
        'inspection/system.Login/captcha',
        'inspection/system.Login/login',
    ],

    // 菜单免权url（无需权限）
    'menu_is_unauth' => [
        'inspection/system.Index/index',
        'inspection/system.Index/notice',
        'inspection/system.Notice/info',
        'inspection/system.Login/logout',
        'inspection/system.UserCenter/info',
    ],

    // 菜单免限url（不限速率）
    'menu_is_unrate' => [
        'inspection/file.File/add',
        'inspection/file.File/list',
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
