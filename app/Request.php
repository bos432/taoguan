<?php


// 应用请求对象类
namespace app;

class Request extends \think\Request
{
    // 全局过滤规则
    protected $filter = [];
}
