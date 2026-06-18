<?php
namespace app\common\service\merchant;

use app\common\cache\merchant\MerchantUserCache;
use app\common\model\merchant\MerchantModel;
use app\common\model\merchant\MerchantUserModel;
use think\facade\Config;
use think\facade\Validate;
use app\common\model\merchant\MerchantMenuModel;
use app\common\service\system\SettingService;
use app\common\service\merchant\MerchantUserTokenService;
use app\common\service\utils\Utils;
use hg\apidoc\annotation as Apidoc;

/**
 * 用户管理
 */
class MerchantUserService
{
    /**
     * 添加修改字段
     * @var array
     */
    public static $edit_field = [
        'user_id/d'   => '',
        'avatar_id/d' => 0,
        'number/s'    => '',
        'nickname/s'  => '',
        'username/s'  => '',
        'phone/s'     => '',
        'email/s'     => '',
        'remark/s'    => '',
        'sort/d'      => 250,
    ];

    /**
     * 用户列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();
        $group = 'm.' . $pk;

        if (empty($field)) {
            $field = $group . ',is_admin,avatar_id,number,nickname,username,sort,is_super,is_disable,create_time,update_time,login_time';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $group => 'desc'];
        }

        if (mer_user_hide_where()) {
            $where[] = mer_user_hide_where('m.mer_user_id');
        }

        $model = $model->alias('m');
        foreach ($where as $wk => $wv) {
            if ($wv[0] == 'role_ids' && is_array($wv[2])) {
                $model = $model->join('merchant_user_attributes r', 'm.mer_user_id=r.mer_user_id')->where('r.role_id', $wv[1], $wv[2]);
                unset($where[$wk]);
            }
        }
        $where = array_values($where);
        $where[] = ['mer_id','=',mer_id()];
        $with     = ['roles'];
        $append   = ['role_names'];
        $hidden   = ['roles'];
        $field_no = [];
        if (strpos($field, 'avatar_id') !== false) {
            $with[]   = $hidden[] = 'avatar';
            $append[] = 'avatar_url';
        }
        $fields = explode(',', $field);
        foreach ($fields as $k => $v) {
            if (in_array($v, $field_no)) {
                unset($fields[$k]);
            }
        }
        $field = implode(',', $fields);

        $count = $model->where($where)->group($group)->count();
        $pages = 0;
        if ($page > 0) {
            $model = $model->page($page);
        }
        if ($limit > 0) {
            $model = $model->limit($limit);
            $pages = ceil($count / $limit);
        }
        $list = $model->field($field)->where($where)
            ->with($with)->append($append)->hidden($hidden)
            ->order($order)->group($group)->select()->toArray();

        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    /**
     * 用户信息
     *
     * @param int  $id   用户id
     * @param bool $exce 不存在是否抛出异常
     * @param bool $role 是否返回角色信息
     * 
     * @return array|Exception
     */
    public static function info($id, $exce = true, $role = false)
    {
        $info = MerchantUserCache::get($id);
        if (empty($info)) {
            $model = new MerchantUserModel();

            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('用户不存在：' . $id);
                }
                return [];
            }
            $info = $info
                ->append(['avatar_url', 'role_ids','mer_title'])
                ->hidden(['avatar', 'roles','merchant'])
                ->toArray();

            $MerchantMenuModel = new MerchantMenuModel();
            $MenuPk = $MerchantMenuModel->getPk();

            if (user_is_super($id)) {
                $menu      = $MerchantMenuModel->field($MenuPk . ',menu_url')->where([where_delete()])->select()->toArray();
                $menu_ids  = array_column($menu, 'menu_id');
                $menu_urls = array_filter(array_column($menu, 'menu_url'));
            } elseif ($info['is_super'] == 1) {
                $menu      = $MerchantMenuModel->field($MenuPk . ',menu_url')->where(where_disdel())->select()->toArray();
                $menu_ids  = array_column($menu, 'menu_id');
                $menu_urls = array_filter(array_column($menu, 'menu_url'));
            } else {
                $role_menu_ids   = MerchantRoleService::menu_ids($info['role_ids'], where_disdel());
                $unauth_menu_ids = MerchantMenuService::unauthList('id');
                $menu_ids        = array_merge($role_menu_ids, $unauth_menu_ids);
                $menu_urls       = $MerchantMenuModel->where('menu_id', 'in', $menu_ids)->where(where_disdel())->column('menu_url');
                $menu_urls       = array_filter($menu_urls);
            }

            if (empty($menu_ids)) {
                $menu_ids = [];
            } else {
                foreach ($menu_ids as $k => $v) {
                    $menu_ids[$k] = (int) $v;
                }
            }

            $menu_is_unlogin = Config::get('merchant.menu_is_unlogin', []);
            $menu_is_unauth  = Config::get('merchant.menu_is_unauth', []);
            $unlogin_unauth  = array_merge($menu_is_unlogin, $menu_is_unauth);
            $menu_urls       = array_unique(array_merge($menu_urls, $unlogin_unauth));

            $info['roles']    = array_values($menu_urls);
            $info['menus']    = MerchantMenuService::menus($menu_ids);
            $info['menu_ids'] = $menu_ids;

            MerchantUserCache::set($id, $info);
        }

        if ($role) {
            $user_menu_ids = $info['menu_ids'] ?? [];
            $role_menu_ids = MerchantRoleService::menu_ids($info['role_ids'], where_disdel());

            $menu_list = MerchantMenuService::list('list', [where_delete()], [], 'menu_id,menu_pid,menu_name,menu_url,is_unlogin,is_unauth');
            foreach ($menu_list as &$val) {
                $val['is_check'] = 0;
                $val['is_role'] = 0;
                foreach ($user_menu_ids as $m_menu_id) {
                    if ($val['menu_id'] == $m_menu_id) {
                        $val['is_check'] = 1;
                    }
                }
                foreach ($role_menu_ids as $g_menu_id) {
                    if ($val['menu_id'] == $g_menu_id) {
                        $val['is_role'] = 1;
                    }
                }
            }
            $info['menu_tree'] = list_to_tree($menu_list, 'menu_id', 'menu_pid');
        }

        return $info;
    }

    /**
     * 用户添加
     *
     * @param array $param 用户信息
     * 
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();

        unset($param[$pk]);
        if(!isset($param['mer_id'])){
            $param['mer_id']  = mer_id();
        }
        $param['create_uid']  = mer_user_id();
        $param['create_time'] = datetime();
        // 密码
        if (isset($param['password'])) {
            $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
        }

        // 启动事务
        $model->startTrans();
        try {
            // 添加
            $model->save($param);
            // 添加角色
            if (isset($param['role_ids'])) {
                $model->roles()->saveAll($param['role_ids']);
            }
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        $param[$pk] = $model->$pk;

        return $param;
    }

    /**
     * 用户修改
     *
     * @param int|array $ids   用户id
     * @param array     $param 用户信息
     * 
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);
        $mer_id = mer_id();
        $param['update_uid']  = mer_user_id();
        $param['update_time'] = datetime();
        // 密码
        if (isset($param['password'])) {
            $param['pwd_time'] = datetime();
            $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
        }

        // 启动事务
        $model->startTrans();
        try {
            if (is_numeric($ids)) {
                $ids = [$ids];
            }
            // 修改
            $model->where('is_admin','<>',1)->where('mer_id',$mer_id)->where($pk, 'in', $ids)->update($param);
            if (var_isset($param, ['role_ids'])) {
                foreach ($ids as $id) {
                    $info = $model->where('is_admin','<>',1)->where('mer_id',$mer_id)->find($id);
                    // 修改角色
                    if (isset($param['role_ids'])) {
                        $info = $info->append(['role_ids']);
                        relation_update($info, $info['role_ids'], $param['role_ids'], 'roles');
                    }
                }
            }
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        $param['ids'] = $ids;

        MerchantUserCache::del($ids);

        return $param;
    }

    /**
     * 用户删除
     *
     * @param int|array $ids  用户id
     * @param bool      $real 是否真实删除
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();
        $mer_id = mer_id();
        // 启动事务
        $model->startTrans();
        try {
            if (is_numeric($ids)) {
                $ids = [$ids];
            }
            if ($real) {
                foreach ($ids as $id) {
                    $info = $model->where('mer_id',$mer_id)->find($id);
                    // 删除角色
                    $info->roles()->detach();
                }
                $model->where('mer_id',$mer_id)->where($pk, 'in', $ids)->delete();
            } else {
                $update = delete_update();
                $model->where('mer_id',$mer_id)->where($pk, 'in', $ids)->update($update);
            }
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        $update['ids'] = $ids;

        MerchantUserCache::del($ids);

        return $update;
    }

    /**
     * 用户登录
     *
     * @param array $param 登录信息
     * @Apidoc\Returned("AdminToken", type="string", require=true, desc="AdminToken")
     * @return array|Exception
     */
    public static function login($param)
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();

        $field = $pk . ',mer_id,password,is_disable,login_num';

        if (Validate::rule('username', 'mobile')->check($param)) {
            $where[] = ['phone', '=', $param['username']];
        } else if (Validate::rule('username', 'email')->check($param)) {
            $where[] = ['email', '=', $param['username']];
        } else {
            $where[] = ['username', '=', $param['username']];
        }
        $where[] = where_delete();

        $user = $model->field($field)->where($where)->find();
        if (empty($user)) {
            $user = $model->field($field)->where(where_delete(['username|phone|email', '=', $param['username']]))->find();
        }
        if (empty($user)) {
            exception(lang('system.Account or password error'));
        }

        $user = $user->toArray();
        $password_verify = password_verify($param['password'], $user['password']);
        if (!$password_verify) {
            exception(lang('system.Account or password error'));
        }
        if ($user['is_disable'] == 1) {
            exception(lang('system.The account has been disabled. Please contact the administrator'));
        }
        /********************判断商家是否被禁用或删除********************************/
        $mer = MerchantModel::where('id',$user['mer_id'])->where('is_delete','<>',1)->field('id,is_disable,is_delete')->find();
        if (empty($mer)) {
            exception('账号信息不存在');
        }
        if($mer['is_disable'] ==1){
            exception('您已被禁用');
        }
        if($mer['is_delete'] ==1){
            exception('您已被删除');
        }
        $user_id = $user[$pk];
        $ip_info = Utils::ipInfo();

        $update['login_ip']     = $ip_info['ip'];
        $update['login_region'] = $ip_info['region'];
        $update['login_time']   = datetime();
        $update['login_num']    = $user['login_num'] + 1;
        $model->where($pk, $user_id)->update($update);

        $user_log[$pk]             = $user_id;
        $user_log['response_code'] = 200;
        $user_log['response_msg']  = '登录成功';
        $user_log['mer_id'] = $user['mer_id'];
        MerchantUserLogService::add($user_log, SettingService::LOG_TYPE_LOGIN);

        MerchantUserCache::del($user_id);
        $user = self::info($user_id);
        $data = self::loginField($user);

        return $data;
    }

    /**
     * 用户登录返回字段
     *
     * @param array $user 用户信息
     *
     * @return array
     */
    public static function loginField($user)
    {
        $data = [];
        $setting = MerchantSettingService::info();
        $token_name = $setting['token_name'];
        $data[$token_name] = self::token($user);
        $fields = ['user_id', 'nickname', 'username','mer_title'];
        foreach ($fields as $field) {
            if (isset($user[$field])) {
                $data[$field] = $user[$field];
            }
        }

        return $data;
    }

    /**
     * 用户token
     *
     * @param  array $user 用户信息
     *
     * @return string
     */
    public static function token($user)
    {
        return MerchantUserTokenService::create($user);
    }

    /**
     * 用户退出
     *
     * @param int $id 用户id
     * 
     * @return array
     */
    public static function logout($id)
    {
        $model = new MerchantUserModel();
        $pk = $model->getPk();

        $update['logout_time'] = datetime();

        $model->where($pk, $id)->update($update);

        $update[$pk] = $id;

        MerchantUserCache::del($id);

        return $update;
    }
}
