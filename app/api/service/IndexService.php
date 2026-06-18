<?php


namespace app\api\service;

/**
 * 首页
 */
class IndexService
{
    /**
     * 首页
     *
     * @return array
     */
    public static function index()
    {
        $data['name']   = "涛冠优选";
        $data['desc']   = '基于ThinkPHP8和Vue3的极简后台管理系统';

        return $data;
    }
}
