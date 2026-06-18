<?php


namespace app\common\service\system;

use think\facade\Config;
use app\common\cache\system\MenuCache;
use app\common\cache\system\RoleCache;
use app\common\cache\system\UserCache;
use app\common\model\system\MenuModel;
use app\common\model\system\UserModel;
use app\common\model\system\RoleMenusModel;

/**
 * 菜单管理
 */
class MenuService
{
    private static $safe_menu_name_fallback = [
        'member' => "\u{4F1A}\u{5458}\u{7BA1}\u{7406}",
        'Member' => "\u{4F1A}\u{5458}\u{5217}\u{8868}",
        'MemberTag' => "\u{4F1A}\u{5458}\u{6807}\u{7B7E}",
        'MemberGroup' => "\u{4F1A}\u{5458}\u{5206}\u{7EC4}",
        'MemberApi' => "\u{4F1A}\u{5458}\u{63A5}\u{53E3}",
        'MemberStatistic' => "\u{4F1A}\u{5458}\u{7EDF}\u{8BA1}",
        'MemberLog' => "\u{4F1A}\u{5458}\u{65E5}\u{5FD7}",
        'MemberThird' => "\u{7B2C}\u{4E09}\u{65B9}\u{8D26}\u{53F7}",
        'content' => "\u{5185}\u{5BB9}\u{8D44}\u{8BAF}",
        'ContentContent' => "\u{5185}\u{5BB9}\u{7BA1}\u{7406}",
        'ContentCategory' => "\u{5185}\u{5BB9}\u{5206}\u{7C7B}",
        'file' => "\u{6587}\u{4EF6}\u{7BA1}\u{7406}",
        'FileFile' => "\u{6587}\u{4EF6}\u{5217}\u{8868}",
        'FileGroup' => "\u{6587}\u{4EF6}\u{5206}\u{7EC4}",
        'FileTag' => "\u{6587}\u{4EF6}\u{6807}\u{7B7E}",
        'setting' => "\u{4E1A}\u{52A1}\u{8BBE}\u{7F6E}",
        'MemberSetting' => "\u{4F1A}\u{5458}\u{8BBE}\u{7F6E}",
        'ContentSetting' => "\u{5185}\u{5BB9}\u{8BBE}\u{7F6E}",
        'FileSetting' => "\u{6587}\u{4EF6}\u{8BBE}\u{7F6E}",
        'SettingCarousel' => "\u{8F6E}\u{64AD}\u{56FE}\u{7BA1}\u{7406}",
        'SettingNotice' => "\u{901A}\u{77E5}\u{516C}\u{544A}",
        'SettingAccord' => "\u{534F}\u{8BAE}\u{7BA1}\u{7406}",
        'SettingFeedback' => "\u{610F}\u{89C1}\u{53CD}\u{9988}",
        'SettingLink' => "\u{53CB}\u{60C5}\u{94FE}\u{63A5}",
        'SettingRegion' => "\u{5730}\u{533A}\u{7BA1}\u{7406}",
        'Delivery' => "\u{914D}\u{9001}\u{8BBE}\u{7F6E}",
        'SettingSetting' => "\u{7CFB}\u{7EDF}\u{8BBE}\u{7F6E}",
        'system' => "\u{7CFB}\u{7EDF}\u{7BA1}\u{7406}",
        'SystemMenu' => "\u{83DC}\u{5355}\u{7BA1}\u{7406}",
        'MerMenu' => "\u{5546}\u{5BB6}\u{83DC}\u{5355}",
        'SystemRole' => "\u{89D2}\u{8272}\u{7BA1}\u{7406}",
        'SystemDept' => "\u{90E8}\u{95E8}\u{7BA1}\u{7406}",
        'SystemPost' => "\u{5C97}\u{4F4D}\u{7BA1}\u{7406}",
        'SystemUser' => "\u{7528}\u{6237}\u{7BA1}\u{7406}",
        'SystemUserLog' => "\u{64CD}\u{4F5C}\u{65E5}\u{5FD7}",
        'SystemNotice' => "\u{7CFB}\u{7EDF}\u{901A}\u{77E5}",
        'SystemUserCenter' => "\u{4E2A}\u{4EBA}\u{4E2D}\u{5FC3}",
        'SystemApidoc' => "\u{63A5}\u{53E3}\u{6587}\u{6863}",
        'SystemSetting' => "\u{7CFB}\u{7EDF}\u{8BBE}\u{7F6E}",
    ];

    private static $menu_name_fallback = [
        'goods' => '商品管理',
        'GoodsType' => '商品分类',
        'GoodsLabel' => '商品标签',
        'Goods' => '商品列表',
        'order' => '订单管理',
        'merchant' => '商家管理',
        'Merchant' => '商家列表',
        'member' => '会员管理',
        'Member' => '会员列表',
        'MemberTag' => '会员标签',
        'MemberGroup' => '会员分组',
        'MemberApi' => '会员接口',
        'MemberStatistic' => '会员统计',
        'MemberLog' => '会员日志',
        'MemberThird' => '第三方账号',
        'content' => '内容资讯',
        'ContentContent' => '内容管理',
        'ContentCategory' => '内容分类',
        'file' => '文件管理',
        'FileFile' => '文件列表',
        'FileGroup' => '文件分组',
        'FileTag' => '文件标签',
        'setting' => '业务设置',
        'MemberSetting' => '会员设置',
        'ContentSetting' => '内容设置',
        'FileSetting' => '文件设置',
        'SettingCarousel' => '轮播图管理',
        'SettingNotice' => '通知公告',
        'SettingAccord' => '协议管理',
        'SettingFeedback' => '意见反馈',
        'SettingLink' => '友情链接',
        'SettingRegion' => '地区管理',
        'Delivery' => '配送设置',
        'SettingSetting' => '系统设置',
        'system' => '系统管理',
        'SystemMenu' => '菜单管理',
        'MerMenu' => '商家菜单',
        'SystemRole' => '角色管理',
        'SystemDept' => '部门管理',
        'SystemPost' => '岗位管理',
        'SystemUser' => '用户管理',
        'SystemUserLog' => '操作日志',
        'SystemNotice' => '系统通知',
        'SystemUserCenter' => '个人中心',
        'SystemApidoc' => '接口文档',
        'SystemSetting' => '系统设置',
        'trace' => '溯源管理',
        'TraceBatch' => '溯源批次',
        'platform' => '平台运营',
        'PlatformAnalytics' => '超级管理员数据中心',
        'PlatformExport' => '导出中心',
        'MerchantPurchaseLedger' => '商家采购对账',
        'analytics' => '超级管理员数据中心',
        'exports' => '导出中心',
        'merchant-purchase-ledger' => '商家采购对账',
    ];

    private static $broken_menu_fragments = [
        '骞冲彴',
        '鍟嗗',
        '绯荤粺',
        '鍗忚',
        '璁㈠崟',
        '鏁版嵁',
        '鐢ㄦ埛',
        '浼氬憳',
        '鍐呭',
        '鏂囦欢',
        '绠＄悊',
    ];

/*
    private static $action_name_fallback = [
        'index' => '棣栭〉',
        'info' => '璇︽儏',
        'add' => '鏂板',
        'edit' => '缂栬緫',
        'dele' => '鍒犻櫎',
        'disable' => '绂佺敤',
        'select' => '閫夋嫨',
        'setting' => '璁剧疆',
        'pwd' => '瀵嗙爜',
        'log' => '鏃ュ織',
        'count' => '缁熻',
        'member' => '鎴愬憳',
        'content' => '鍐呭',
        'file' => '鏂囦欢',
        'notice' => '閫氱煡',
        'captcha' => '楠岃瘉鐮?,
        'logout' => '閫€鍑?,
        'login' => '鐧诲綍',
        'datetime' => '鏃堕棿',
        'getParams' => '鍙傛暟',
        'getBatchTache' => '宸ュ簭',
        'getCode' => '缂栫爜',
        'auth' => '瀹℃牳',
        'download' => '涓嬭浇',
        'orderPayAuth' => '鏀粯鏍￠獙',
        'editmenu' => '鑿滃崟鏉冮檺',
        'editgroup' => '鍒嗙粍',
        'edittag' => '鏍囩',
        'edittype' => '绫诲瀷',
        'editdomain' => '鍩熷悕',
        'editrole' => '瑙掕壊',
        'editdept' => '閮ㄩ棬',
        'editpost' => '宀椾綅',
        'repwd' => '閲嶇疆瀵嗙爜',
        'super' => '瓒呯',
        'clean' => '娓呯悊',
        'clear' => '娓呯┖',
        'logInfo' => '鏃ュ織璇︽儏',
        'logDele' => '鏃ュ織鍒犻櫎',
        'user' => '鐢ㄦ埛',
        'userRemove' => '鐢ㄦ埛瑙ｇ粦',
        'role' => '瑙掕壊',
        'roleRemove' => '瑙掕壊瑙ｇ粦',
        'unauth' => '鍏嶆潈',
        'unlogin' => '鍏嶇櫥',
        'unrate' => '鍏嶉檺',
        'hidden' => '闅愯棌',
        'editpid' => '涓婄骇',
        'editsort' => '鎺掑簭',
        'fileRemove' => '鏂囦欢瑙ｇ粦',
        'group' => '鍒嗙粍',
        'groupRemove' => '鍒嗙粍瑙ｇ粦',
    ];
*/
    private static $action_name_fallback = [
        'index' => 'Home',
        'info' => 'Details',
        'add' => 'Add',
        'edit' => 'Edit',
        'dele' => 'Delete',
        'disable' => 'Disable',
        'select' => 'Select',
        'setting' => 'Settings',
        'pwd' => 'Password',
        'log' => 'Log',
        'count' => 'Stats',
        'member' => 'Member',
        'content' => 'Content',
        'file' => 'File',
        'notice' => 'Notice',
        'captcha' => 'Captcha',
        'logout' => 'Logout',
        'login' => 'Login',
        'datetime' => 'Time',
        'getParams' => 'Params',
        'getBatchTache' => 'Steps',
        'getCode' => 'Code',
        'auth' => 'Auth',
        'download' => 'Download',
        'orderPayAuth' => 'PayAuth',
        'editmenu' => 'MenuPerm',
        'editgroup' => 'Group',
        'edittag' => 'Tag',
        'edittype' => 'Type',
        'editdomain' => 'Domain',
        'editrole' => 'Role',
        'editdept' => 'Dept',
        'editpost' => 'Post',
        'repwd' => 'ResetPwd',
        'super' => 'Super',
        'clean' => 'Clean',
        'clear' => 'Clear',
        'logInfo' => 'LogDetails',
        'logDele' => 'LogDelete',
        'user' => 'User',
        'userRemove' => 'UserUnbind',
        'role' => 'Role',
        'roleRemove' => 'RoleUnbind',
        'unauth' => 'NoAuth',
        'unlogin' => 'NoLogin',
        'unrate' => 'NoRate',
        'hidden' => 'Hidden',
        'editpid' => 'Parent',
        'editsort' => 'Sort',
        'fileRemove' => 'FileUnbind',
        'group' => 'Group',
        'groupRemove' => 'GroupUnbind',
        'filters' => 'Filters',
        'summary' => 'Summary',
        'trend' => 'Trend',
        'ranking' => 'Ranking',
        'alerts' => 'Alerts',
        'merchants' => 'Merchants',
        'orders' => 'Orders',
        'renewRecords' => 'Renewals',
        'analytics' => 'Analytics',
        'delivery' => 'Delivery',
        'logistics' => 'Logistics',
        'takeDelivery' => 'Receipt',
        'serviceOrder' => 'AfterSale',
    ];
    /**
     * 添加修改字段
     * @var array
     */
    public static $edit_field = [
        'menu_id/d'      => '',
        'menu_pid/d'     => 0,
        'menu_type/d'    => SettingService::MENU_TYPE_CATALOGUE,
        'meta_icon/s'    => '',
        'menu_name/s'    => '',
        'menu_url/s'     => '',
        'path/s'         => '',
        'component/s'    => '',
        'name/s'         => '',
        'meta_query/s'   => '',
        'hidden/d'       => 0,
        'keep_alive/d'   => 1,
        'always_show/d'  => 0,
        'sort/d'         => 250,
        'add_info/b'     => false,
        'add_add/b'      => false,
        'add_edit/b'     => false,
        'add_dele/b'     => false,
        'add_disable/b'  => false,
        'edit_info/b'    => false,
        'edit_add/b'     => false,
        'edit_edit/b'    => false,
        'edit_dele/b'    => false,
        'edit_disable/b' => false,
    ];

    /**
     * 菜单列表
     *
     * @param string $type  tree树形，list列表
     * @param array  $where 条件
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function list($type = 'tree', $where = [], $order = [], $field = '')
    {
        $model = new MenuModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',menu_pid,menu_name,menu_type,meta_icon,menu_url,path,name,component,hidden,sort,is_unlogin,is_unauth,is_unrate,is_disable';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = MenuCache::get($key);
        if (empty($data)) {
            $append = [];
            if (strpos($field, 'menu_type') !== false) {
                $append[] = 'menu_type_name';
            }
            $data = $model->field($field)->append($append)->where($where)->order($order)->select()->toArray();
            $data = self::sanitizeMenuRows($data);
            if ($type == 'tree') {
                $data = array_to_tree($data, $pk, 'menu_pid');
            }
            MenuCache::set($key, $data);
        }

        return $data;
    }

    /**
     * 菜单信息
     *
     * @param int|string $id   菜单id、url
     * @param bool       $exce 不存在是否抛出异常
     * 
     * @return array|Exception
     */
    public static function info($id = '', $exce = true)
    {
        if (empty($id)) {
            $id = menu_url();
        }

        $info = MenuCache::get($id);
        if (empty($info)) {
            $model = new MenuModel();
            $pk = $model->getPk();

            if (is_numeric($id)) {
                $where[] = [$pk, '=', $id];
            } else {
                $where[] = ['menu_url', '=', $id];
                $where[] = where_delete();
            }

            $info = $model->where($where)->find();
            if (empty($info)) {
                if ($exce) {
                    exception('菜单不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            $info['menu_name'] = self::sanitizeMenuName($info);

            MenuCache::set($id, $info);
        }

        return $info;
    }

    /**
     * 菜单添加
     *
     * @param array $param 菜单信息
     * 
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MenuModel();
        $pk = $model->getPk();

        unset($param[$pk]);

        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();

        $add = false;
        $add_arr = ['info' => '信息', 'add' => '添加', 'edit' => '修改', 'dele' => '删除', 'disable' => '是否禁用'];
        foreach ($add_arr as $k => $v) {
            $add_key = '';
            $add_key = 'add_' . $k;
            if ($param[$add_key] ?? '') {
                $add = true;
            }
        }

        // 启动事务
        $model->startTrans();
        try {
            $menu = MenuModel::create($param);
            $id = $menu->$pk;

            if ($add) {
                if (empty($param['menu_url'])) {
                    exception('请输入菜单链接：应用/控制器/操作');
                }
                $menu_url_pre = substr($param['menu_url'], 0, strripos($param['menu_url'], '/'));

                $add_data = [];
                foreach ($add_arr as $k => $v) {
                    $add_key = '';
                    $add_key = 'add_' . $k;
                    if ($param[$add_key] ?? '') {
                        $add_where = [];
                        $add_where[] = where_delete();
                        $add_where[] = ['menu_url', '=', $menu_url_pre . '/' . $k];
                        $add_menu = $model->field($pk)->where($add_where)->find();
                        if (empty($add_menu)) {
                            $add_temp = [];
                            $add_temp['menu_pid']    = $id;
                            $add_temp['menu_type']   = SettingService::MENU_TYPE_BUTTON;
                            $add_temp['menu_name']   = $param['menu_name'] . $v;
                            $add_temp['menu_url']    = $menu_url_pre . '/' . $k;
                            $add_temp['create_time'] = datetime();
                            $add_data[] = $add_temp;
                        }
                    }
                }
                if ($add_data) {
                    $model->insertAll($add_data);
                }
                $param['add_data'] = $add_data;
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

        $param[$pk] = $id;

        MenuCache::clear();

        return $param;
    }

    /**
     * 菜单修改
     *
     * @param int   $id    菜单id
     * @param array $param 菜单信息
     * 
     * @return array|Exception
     */
    public static function edit($id, $param)
    {
        $model = new MenuModel();
        $pk = $model->getPk();

        unset($param[$pk]);

        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();

        $add = $edit = false;
        $add_arr = $edit_arr = ['info' => '信息', 'add' => '添加', 'edit' => '修改', 'dele' => '删除', 'disable' => '是否禁用'];
        foreach ($add_arr as $k => $v) {
            $add_key = '';
            $add_key = 'add_' . $k;
            if ($param[$add_key] ?? '') {
                $add = true;
            }
        }
        foreach ($edit_arr as $k => $v) {
            $edit_key = '';
            $edit_key = 'edit_' . $k;
            if ($param[$edit_key] ?? '') {
                $edit = true;
            }
        }

        // 启动事务
        $model->startTrans();
        try {
            $menu = $model->find($id);
            $menu->save($param);

            if ($add || $edit) {
                if (empty($param['menu_url'])) {
                    exception('请输入菜单链接：应用/控制器/操作');
                }
                $menu_url_pre = substr($param['menu_url'], 0, strripos($param['menu_url'], '/'));

                $add_data = [];
                foreach ($add_arr as $k => $v) {
                    $add_key = '';
                    $add_key = 'add_' . $k;
                    if ($param[$add_key] ?? '') {
                        $add_where = [];
                        $add_where[] = where_delete();
                        $add_where[] = ['menu_pid', '=', $id];
                        $add_where[] = ['menu_url', '=', $menu_url_pre . '/' . $k];
                        $add_menu = $model->field($pk)->where($add_where)->find();
                        if (empty($add_menu)) {
                            $add_temp = [];
                            $add_temp['menu_pid']    = $id;
                            $add_temp['menu_type']   = SettingService::MENU_TYPE_BUTTON;
                            $add_temp['menu_name']   = $param['menu_name'] . $v;
                            $add_temp['menu_url']    = $menu_url_pre . '/' . $k;
                            $add_temp['create_time'] = datetime();
                            $add_data[] = $add_temp;
                        }
                    }
                }
                if ($add_data) {
                    $model->insertAll($add_data);
                }
                $param['add_data'] = $add_data;

                $edit_data = [];
                foreach ($edit_arr as $k => $v) {
                    $edit_key = '';
                    $edit_key = 'edit_' . $k;
                    if ($param[$edit_key] ?? '') {
                        $edit_where = [];
                        $edit_where[] = where_delete();
                        $edit_where[] = ['menu_pid', '=', $id];
                        $edit_where[] = ['menu_url', 'like', '%/' . $k];
                        $edit_menu = $model->field($pk)->where($edit_where)->find();
                        if ($edit_menu) {
                            $edit_temp = [];
                            $edit_temp['menu_type']   = SettingService::MENU_TYPE_BUTTON;
                            $edit_temp['menu_name']   = $param['menu_name'] . $v;
                            $edit_temp['menu_url']    = $menu_url_pre . '/' . $k;
                            $edit_temp['update_time'] = datetime();
                            $edit_data[] = $edit_temp;
                            $edit_menu->save($edit_temp);
                        }
                    }
                }
                $param['edit_data'] = $edit_data;
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

        $param[$pk] = $id;

        MenuCache::clear();

        return $param;
    }

    /**
     * 菜单删除
     *
     * @param array $ids  菜单id
     * @param bool  $real 是否真实删除
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MenuModel();
        $pk = $model->getPk();

        if ($real) {
            $res = $model->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }

        $update['ids'] = $ids;

        MenuCache::clear();

        return $update;
    }

    /**
     * 菜单更新
     *
     * @param array $ids   菜单id
     * @param array $param 菜单信息
     * 
     * @return array|Exception
     */
    public static function update($ids, $param = [])
    {
        $model = new MenuModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();

        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        MenuCache::clear();

        return $param;
    }

    /**
     * 菜单角色
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function role($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        return RoleService::list($where, $page, $limit, $order, $field);
    }

    /**
     * 菜单角色解除
     *
     * @param array $menu_id  菜单id
     * @param array $role_ids 角色id
     *
     * @return int
     */
    public static function roleRemove($menu_id, $role_ids = [])
    {
        $where[] = ['menu_id', 'in', $menu_id];
        if (empty($role_ids)) {
            $role_ids = RoleMenusModel::where($where)->column('role_id');
        }
        $where[] = ['role_id', 'in', $role_ids];

        $res = RoleMenusModel::where($where)->delete();
        $user_ids = UserModel::select()->column('user_id');

        RoleCache::del($role_ids);
        UserCache::del($user_ids);

        return $res;
    }

    /**
     * 菜单列表
     * 
     * @param string $type url菜单url，id菜单id
     *
     * @return array 
     */
    public static function menuList($type = 'url')
    {
        $key = $type;
        $list = MenuCache::get($key);
        if (empty($list)) {
            $model = new MenuModel();

            $column = 'menu_url';
            if ($type == 'id') {
                $column = $model->getPk();
            }

            $list = $model->where([where_delete()])->column($column);
            $list = array_filter($list);
            $list = array_values($list);
            sort($list);

            MenuCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 菜单免登列表
     * 
     * @param string $type url菜单url，id菜单id
     *
     * @return array
     */
    public static function unloginList($type = 'url')
    {
        $key = 'unlogin-' . $type;
        $list = MenuCache::get($key);
        if (empty($list)) {
            $model = new MenuModel();

            $column = 'menu_url';
            $menu_is_unlogin = Config::get('admin.menu_is_unlogin', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($menu_is_unlogin) {
                    $menu_is_unlogin = $model->where('menu_url', 'in', $menu_is_unlogin)->column($column);
                }
            }

            $list = $model->where(where_delete(['is_unlogin', '=', 1]))->column($column);
            $list = array_merge($list, $menu_is_unlogin);
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            MenuCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 菜单免权列表
     * 
     * @param string $type url菜单url，id菜单id
     *
     * @return array
     */
    public static function unauthList($type = 'url')
    {
        $key = 'unauth-' . $type;
        $list = MenuCache::get($key);
        if (empty($list)) {
            $model = new MenuModel();

            $column = 'menu_url';
            $menu_is_unauth = Config::get('admin.menu_is_unauth', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($menu_is_unauth) {
                    $menu_is_unauth = $model->where('menu_url', 'in', $menu_is_unauth)->column($column);
                }
            }
            $menu_is_unlogin = self::unloginList($type);

            $list = $model->where(where_delete(['is_unauth', '=', 1]))->column($column);
            $list = array_merge($list, $menu_is_unlogin, $menu_is_unauth);
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            MenuCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 菜单免限列表
     * 
     * @param string $type url菜单url，id菜单id
     *
     * @return array
     */
    public static function unrateList($type = 'url')
    {
        $key = 'unrate-' . $type;
        $list = MenuCache::get($key);
        if (empty($list)) {
            $model = new MenuModel();

            $column = 'menu_url';
            $menu_is_unrate = Config::get('admin.menu_is_unrate', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($menu_is_unrate) {
                    $menu_is_unrate = $model->where('menu_url', 'in', $menu_is_unrate)->column($column);
                }
            }

            $list = $model->where(where_delete(['is_unrate', '=', 1]))->column($column);
            $list = array_merge($list, $menu_is_unrate);
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            MenuCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 菜单路由
     *
     * @param array $ids 菜单id
     *
     * @return array
     */
    public static function menus($ids = [])
    {
        $where = where_delete(['menu_id', 'in', $ids]);
        $field = 'menu_id,menu_pid,menu_name,menu_type,path,name,component,meta_icon,meta_query,hidden,keep_alive,always_show,is_disable';
        $menu  = self::list('list', $where, [], $field);
        $list  = [];
        foreach ($menu as $v) {
            if ($v['menu_type'] != SettingService::MENU_TYPE_BUTTON) {
                $tmp = [];
                $tmp['menu_id']  = $v['menu_id'];
                $tmp['menu_pid'] = $v['menu_pid'];
                $tmp['path'] = $v['path'];
                $tmp['name'] = $v['name'];
                $tmp['meta']['title'] = self::sanitizeMenuName($v);
                $tmp['meta']['icon']  = $v['meta_icon'];
                $tmp['meta']['hidden'] = $v['hidden'] ? true : false;
                $tmp['meta']['keepAlive'] = $v['keep_alive'] ? true : false;
                $tmp['meta']['alwaysShow'] = $v['always_show'] ? true : false;
                if ($v['menu_type'] == SettingService::MENU_TYPE_CATALOGUE) {
                    $tmp['redirect']  = 'noRedirect';
                    $tmp['component'] = 'Layout';
                    if ($v['menu_pid'] > 0) {
                        $tmp['redirect']  = $v['component'];
                        $tmp['component'] = $v['component'];
                    }
                } elseif ($v['menu_type'] == SettingService::MENU_TYPE_MENU) {
                    $tmp['meta']['query'] = $v['meta_query'] ? json_decode($v['meta_query'], true) : [];
                    $tmp['component'] = $v['component'];
                    // 外链
                    if (strpos($v['path'], 'http') === 0) {
                        unset($tmp['name']);
                    }
                }
                $list[] = $tmp;
            }
        }

        return list_to_tree($list, 'menu_id', 'menu_pid');
    }

    private static function sanitizeMenuName($row = [])
    {
        $menuName = trim((string)($row['menu_name'] ?? ''));
        if ($menuName !== '' && preg_match('/\?{2,}/', $menuName)) {
            $menuName = '';
        }
        $normalized = preg_replace('/[\s\/\\\\|,，。；;：:（）()\[\]\-—_]+/u', '', $menuName);
        $isBroken = false;
        foreach (self::$broken_menu_fragments as $fragment) {
            if ($menuName !== '' && mb_strpos($menuName, $fragment) !== false) {
                $isBroken = true;
                break;
            }
        }

        $keys = [
            $row['name'] ?? '',
            ltrim((string)($row['path'] ?? ''), '/'),
            $row['component'] ?? '',
            $row['menu_url'] ?? '',
        ];
        foreach ($keys as $key) {
            $key = trim((string)$key);
            if ($key !== '' && isset(self::$safe_menu_name_fallback[$key])) {
                return self::$safe_menu_name_fallback[$key];
            }
            if ($key !== '' && isset(self::$menu_name_fallback[$key])) {
                return self::$menu_name_fallback[$key];
            }
        }

        if ($menuName !== '' && !($normalized !== '' && preg_match('/^\?+$/', $normalized) === 1) && !$isBroken) {
            return $menuName;
        }

        if (!empty($row['component'])) {
            return (string)$row['component'];
        }

        if (!empty($row['path'])) {
            return ltrim((string)$row['path'], '/');
        }

        return '功能菜单';
    }
    private static function sanitizeMenuRows($rows = [])
    {
        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row['menu_id']] = $row;
        }

        $memo = [];
        foreach ($rows as $index => $row) {
            $rows[$index]['menu_name'] = self::sanitizeMenuNameWithLookup($row, $lookup, $memo);
        }

        return $rows;
    }

    private static function sanitizeMenuNameWithLookup($row, $lookup, &$memo)
    {
        $menuId = (int)($row['menu_id'] ?? 0);
        if ($menuId > 0 && isset($memo[$menuId])) {
            return $memo[$menuId];
        }

        $title = self::sanitizeMenuName($row);
        if ($title !== '功能菜单') {
            if ($menuId > 0) {
                $memo[$menuId] = $title;
            }
            return $title;
        }

        $menuUrl = trim((string)($row['menu_url'] ?? ''));
        $action = '';
        if ($menuUrl !== '' && strpos($menuUrl, '/') !== false) {
            $action = substr($menuUrl, strrpos($menuUrl, '/') + 1);
        }

        $parentTitle = '';
        $parentId = (int)($row['menu_pid'] ?? 0);
        if ($parentId > 0 && isset($lookup[$parentId])) {
            $parentTitle = self::sanitizeMenuNameWithLookup($lookup[$parentId], $lookup, $memo);
        }

        if ($parentTitle !== '' && $parentTitle !== '功能菜单' && $action !== '' && isset(self::$action_name_fallback[$action])) {
            $title = $parentTitle . self::$action_name_fallback[$action];
        }

        if ($title === '功能菜单' && $action !== '' && isset(self::$action_name_fallback[$action])) {
            $title = self::$action_name_fallback[$action];
        }

        if ($menuId > 0) {
            $memo[$menuId] = $title;
        }

        return $title;
    }
}
