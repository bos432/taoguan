<?php


namespace app\common\service\inspection;

/**
 * 登录退出
 */
class InspectionLoginService
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
        return InspectionUserService::login($param);
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
        return InspectionUserService::logout($user_id);
    }
}
