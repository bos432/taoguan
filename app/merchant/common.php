<?php


// admin公共函数文件
use think\facade\Request;
use app\common\service\merchant\MerchantMenuService;

/**
 * 菜单url获取
 * 应用/控制器/操作 
 * 
 * @return string eg：admin/Index/index
 */
function mer_menu_url()
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
function mer_menu_is_exist($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = mer_menu_url();
    }

    $config_urls = array_merge(
        config('merchant.menu_is_unlogin', []),
        config('merchant.menu_is_unauth', []),
        config('merchant.menu_is_unrate', [])
    );
    if (in_array($menu_url, $config_urls, true)) {
        return true;
    }

    $url_list = MerchantMenuService::menuList();
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
function mer_menu_is_disable($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = mer_menu_url();
    }

    $config_urls = array_merge(
        config('merchant.menu_is_unlogin', []),
        config('merchant.menu_is_unauth', []),
        config('merchant.menu_is_unrate', [])
    );
    if (in_array($menu_url, $config_urls, true)) {
        return false;
    }

    $menu = MerchantMenuService::info($menu_url);
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
function mer_menu_is_unlogin($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = mer_menu_url();
    }

    $unlogin_url = MerchantMenuService::unloginList();
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
function mer_menu_is_unauth($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = mer_menu_url();
    }

    $unauth_url = MerchantMenuService::unauthList();
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
function mer_menu_is_unrate($menu_url = '')
{
    if (empty($menu_url)) {
        $menu_url = mer_menu_url();
    }

    $unrate_url = MerchantMenuService::unrateList();
    if (in_array($menu_url, $unrate_url)) {
        return true;
    }

    return false;
}
