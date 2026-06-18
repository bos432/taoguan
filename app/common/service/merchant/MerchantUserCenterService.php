<?php


namespace app\common\service\merchant;

use app\common\service\merchant\MerchantSettingService;

/**
 * 个人中心
 */
class MerchantUserCenterService
{
    /**
     * 我的信息
     *
     * @param int $user_id 用户id
     * 
     * @return array
     */
    public static function info($user_id)
    {
        $data = MerchantUserService::info($user_id);

        unset($data['password'], $data['role_ids'], $data['menu_ids']);

        return $data;
    }

    /**
     * 修改信息
     *
     * @param int   $id    用户id
     * @param array $param 用户信息
     * 
     * @return array
     */
    public static function edit($id, $param)
    {
        return MerchantUserService::edit($id, $param);
    }

    /**
     * 修改密码
     *
     * @param int   $id    用户id
     * @param array $param 用户密码
     * 
     * @return array
     */
    public static function pwd($id, $param)
    {
        $param['password'] = $param['password_new'];
        return MerchantUserService::edit($id, $param);
    }

    /**
     * 我的日志
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function log($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        return MerchantUserLogService::list($where, $page, $limit, $order, $field);
    }

    /**
     * 鎴戠殑璁剧疆
     *
     * @param int $mer_user_id
     *
     * @return array
     */
    public static function setting($mer_user_id)
    {
        return MerchantBootstrapService::bootstrap($mer_user_id, 'ui');
    }
}
