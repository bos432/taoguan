<?php


// admin公共函数文件
use think\facade\Request;
use app\common\service\inspection\InspectionMenuService;

/**
 * 菜单url获取
 * 应用/控制器/操作 
 * 
 * @return string eg：admin/Index/index
 */
function ins_menu_url()
{
    return app('http')->getName() . '/' . Request::pathinfo();
}

/**
 * 菜单是否存在
 *
 * @param string $menu_url 菜单url
 *
 * @return bool
 */
function ins_menu_is_exist($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = ins_menu_url();
    }

    $url_list = InspectionMenuService::menuList();
    if (in_array($menu_url, $url_list)) {
        return true;
    }

    return false;
}

/**
 * 菜单是否已禁用
 *
 * @param string $menu_url 菜单url
 *
 * @return bool
 */
function ins_menu_is_disable($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = ins_menu_url();
    }

    $menu = InspectionMenuService::info($menu_url);
    if ($menu['is_disable'] == 1) {
        return true;
    }

    return false;
}

/**
 * 菜单是否免登
 *
 * @param string $menu_url 菜单url
 *
 * @return bool
 */
function ins_menu_is_unlogin($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = ins_menu_url();
    }

    $unlogin_url = InspectionMenuService::unloginList();
    if (in_array($menu_url, $unlogin_url)) {
        return true;
    }

    return false;
}

/**
 * 菜单是否免权
 *
 * @param string $menu_url 菜单url
 *
 * @return bool
 */
function ins_menu_is_unauth($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = ins_menu_url();
    }

    $unauth_url = InspectionMenuService::unauthList();
    if (in_array($menu_url, $unauth_url)) {
        return true;
    }

    return false;
}

/**
 * 菜单是否免限
 *
 * @param string $menu_url 菜单url
 *
 * @return bool
 */
function ins_menu_is_unrate($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = ins_menu_url();
    }

    $unrate_url = InspectionMenuService::unrateList();
    if (in_array($menu_url, $unrate_url)) {
        return true;
    }

    return false;
}
