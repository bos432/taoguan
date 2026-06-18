<?php


namespace app\common\service\merchant;

/**
 * 登录退出
 */
class MerchantLoginService
{
    /**
     * 登录
     *
     * @param array $param 登录信息
     * 
     * @return array
     */
    public static function login($param)
    {
        return MerchantUserService::login($param);
    }

    /**
     * 退出
     *
     * @param int $user_id 用户id
     * 
     * @return array
     */
    public static function logout($user_id)
    {
        return MerchantUserService::logout($user_id);
    }
}
