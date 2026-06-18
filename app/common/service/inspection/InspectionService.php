<?php

namespace app\common\service\inspection;
use app\common\model\inspection\InspectionModel;
use app\common\cache\inspection\InspectionCache;
use think\facade\Db;

/**
 * 检测机构管理
 */
class InspectionService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'content' => '',
        'is_disable' => '',
        'username' => '',
        'password' => '',
        'region_id' => '',
        'address' => '',
        'phone' => '',
        'auth_state' => '',
        'auth_msg' => '',
        'sort' => '',
        'remark' => '',
    ];
    /**
     * 检测机构管理列表
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
        $model = new InspectionModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark';
        }
        if (empty($order)) {
            $order = ['sort'=>'asc',$pk => 'desc'];
        }
        if ($page == 0 || $limit == 0) {
            return $model->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * 检测机构管理信息
     *
     * @param int  $id   检测机构管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = InspectionCache::get($id);
        if (empty($info)) {
            $model = new InspectionModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('检测机构管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            InspectionCache::set($id, $info);
        }
        return $info;
    }


    /**
     * 检测机构管理添加
     *
     * @param array $param 检测机构管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new InspectionModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();
        // 密码
        $password = null;
        if (isset($param['password'])) {
            $password = $param['password'];
            $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
        }
        // 启动事务
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            /******************创建角色******************************/
            $menu_list = Db::name('inspection_menu')->where('is_delete','<>',1)->field('menu_id')->select()->toArray();
            $role_data = array(
                'ins_id'=>$id,//检测机构ID
                'role_name'=>'超级管理员',//角色名称
                'role_desc'=>'超级管理员',//角色描述
                'remark'=>'系统默认',//备注
                'sort'=>1,//排序
                'is_admin'=>1,//是否为系统角色：1、不允许操作 0、否
                'menu_ids'=>array_column($menu_list,'menu_id')
            );
            $role = InspectionRoleService::add($role_data);
            /******************创建用户******************************/
            $user_data = array(
                'ins_id'=>$id,//检测机构id
                'number'=>'001',//编号
                'nickname'=>isset($param['username'])?$param['username']:$param['title'],//用户昵称
                'username'=>$param['username'],//用户账号
                'phone'=>$param['phone'],//手机
                'remark'=>'系统默认',//备注
                'sort'=>1,//排序
                'is_super'=>1,//是否超管，1是0否
                'is_admin'=>1,//是否为系统角色：1、不允许操作 0、否
                'role_ids'=>[$role['role_id']]
            );
            if($password){
                $user_data['password'] = $password;//密码
            }
            $role = InspectionUserService::add($user_data);
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }
        return $param;
    }
     /**
     * 检测机构管理修改
     *
     * @param int|array $ids   检测机构管理id
     * @param array     $param 检测机构管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new InspectionModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        // 启动事务
        $model->startTrans();
        try {
            $user_edit = [];
            // 密码
            if (isset($param['password'])) {
                $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
                $user_edit['password'] = $param['password'];
            }
            if (isset($param['username'])) {
                $user_edit['username'] = $param['username'];
                $user_edit['nickname'] = isset($param['username'])?$param['username']:$param['title'];
            }
            if (isset($param['phone'])) {
                $user_edit['phone'] = $param['phone'];
            }
            if (isset($param['phone'])) {
                $user_edit['phone'] = $param['phone'];
            }
            if(count($user_edit)>0){
                $edit_pwd = Db::name('inspection_user')
                    ->where('is_delete',0)
                    ->where('is_disable',0)
                    ->where('is_admin',1)
                    ->whereIn('ins_id',$ids)
                    ->update($user_edit);
            }
            $res = $model->where($pk, 'in', $ids)->update($param);
            if (empty($res)) {
                exception();
            }
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }
        $param['ids'] = $ids;
        InspectionCache::del($ids);
        return $param;
    }
    /**
     * 检测机构管理删除
     *
     * @param array $ids  检测机构管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new InspectionModel();
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
        InspectionCache::del($ids);
        return $update;
    }
}
