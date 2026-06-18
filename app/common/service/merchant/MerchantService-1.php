<?php

namespace app\common\service\merchant;
use app\common\model\finance\MerchantBillModel;
use app\common\model\merchant\MerchantModel;
use app\common\cache\merchant\MerchantCache;
use think\facade\Db;

/**
 * 商家管理
 */
class MerchantService
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
        'name' => '',
        'image_id' => '',
        'member_id' => '',
    ];
    /**
     * @title:查询参数
     * @author：易军辉
     * @date：2025/2/12
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return array
     */
    public static function getParams($source=1)
    {
        $auth_states = MerchantModel::AUTH_STATE;
        return compact('auth_states');
    }
    /**
     * 商家管理列表
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
        $model = new MerchantModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark,name,image_id,member_id';
        }
        if (empty($order)) {
            $order = ['sort'=>'asc',$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        if (strpos($field, 'member_id') !== false) {
            $with = ['member'=>function($query){
                $query->with(['avatar'])->append(['avatar_url'])->hidden(['avatar'])->field('member_id,nickname,avatar_id');
            }];
        }
        if (strpos($field, 'image_id') !== false) {
            $with[]   = $hidden[]   = 'image';
            $append[] = 'image_url';
        }
        if ($page == 0 || $limit == 0) {
            return $model->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['auth_state_title'] = MerchantModel::getAuthState($v['auth_state'],2);
        }
        $status_nums =self::getStatusNum(0);
        return compact('count', 'pages', 'page', 'limit', 'list','status_nums');
    }

    /**
     * @title: 前端查询商家
     * @author：易军辉
     * @date：2025/9/14
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getList($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,member_id';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        if (strpos($field, 'member_id') !== false) {
            $with = ['member'=>function($query){
                $query->with(['avatar'])->append(['avatar_url'])->hidden(['avatar'])->field('member_id,avatar_id');
            }];
        }
        if (strpos($field, 'id') !== false) {
            $with[] ='sellGoods';
        }
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['sellGoodsNum'] = count($v['sellGoods']);
            unset($list[$k]['sellGoods']);
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * @title:查询审核数量
     * @author：易军辉
     * @date：2024/12/15
     * @param $member_id
     * @return array
     */
    public static function getStatusNum($member_id=0){
        $where = " where is_delete=0 and is_disable=0";
        //根据状态查询数量
        $auth_state_num = Db::query("SELECT auth_state,count(id) as num from ya_merchant ".$where." GROUP BY auth_state");
        $auth_state_nums = array();
        //查询全部订单数量
        $auth_state_nums['all'] = Db::name('merchant')
            ->where('is_delete',0)
            ->where('is_disable',0)
            ->count();
        foreach (MerchantModel::AUTH_STATE as $k => $v) {
            $auth_state_nums[$v['code']] =0;
            foreach ($auth_state_num as $k1 => $v1) {
                if($v1['auth_state'] == $v['value']) {
                    $auth_state_nums[$v['code']] = $v1['num'];
                    break;
                }
            }
        }
        return $auth_state_nums;
    }
    /**
     * 商家管理信息
     *
     * @param int  $id   商家管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MerchantCache::get($id);
        if (empty($info)) {
            $model = new MerchantModel();
            $info = $model->with(['image', 'files'])
                ->append(['image_url', 'images'])
                ->hidden(['image', 'files'])
                ->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('商家信息不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MerchantCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 商家管理信息
     *
     * @param int  $id   商家管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function getInfoByMemberID()
    {
        $model = new MerchantModel();
        $info = $model->where('member_id',member_id())
            ->append(['image_url', 'images'])
            ->hidden(['image', 'files'])
            ->field('id,title,username,phone,auth_state,auth_msg,name,image_id')
            ->find();
        if($info){
            $info = $info->toArray();
            $info['auth_state_title'] = MerchantModel::getAuthState($info['auth_state'],2);
            $info['mer_host'] = 'https://'.$_SERVER['SERVER_NAME']."/merchant";
            $info['pwd'] ="123456";
        }
        return $info;
    }
    /**
     * 商家管理添加
     *
     * @param array $param 商家管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantModel();
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
            $menu_list = Db::name('merchant_menu')->where('is_delete','<>',1)->field('menu_id')->select()->toArray();
            $role_data = array(
                'mer_id'=>$id,//商家ID
                'role_name'=>'超级管理员',//角色名称
                'role_desc'=>'超级管理员',//角色描述
                'remark'=>'系统默认',//备注
                'sort'=>1,//排序
                'is_admin'=>1,//是否为系统角色：1、不允许操作 0、否
                'menu_ids'=>array_column($menu_list,'menu_id')
            );
            $role = MerchantRoleService::add($role_data);
            /******************创建用户******************************/
            $user_data = array(
                'mer_id'=>$id,//商家id
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
            $role = MerchantUserService::add($user_data);
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
     * @title:审核
     * @author：易军辉
     * @date：2024/12/20
     */
    public static function auth($ids,$param)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $list = $model
            ->where('is_delete',0)
            ->where('auth_state',0)
            ->where($pk, 'in', $ids)
            ->select();
        if(count($list)<=0){
            exception("未有符合条件的商家需要审核");
        }
        // 启动事务
        $model->startTrans();
        try {
            foreach ($list as $k=>$v){
                $v->auth_time=datetime();
                $v->auth_uid=user_id();
                switch (intval($param['auth_state'])){
                    case 0://待审核
                        $v->auth_state=0;
                        break;
                    case 1://审核通过
                        $v->auth_state=1;
                        /******************创建角色******************************/
                        $menu_list = Db::name('merchant_menu')->where('is_delete','<>',1)->field('menu_id')->select()->toArray();
                        $role_data = array(
                            'mer_id'=>$v['id'],//商家ID
                            'role_name'=>'超级管理员',//角色名称
                            'role_desc'=>'超级管理员',//角色描述
                            'remark'=>'系统默认',//备注
                            'sort'=>1,//排序
                            'is_admin'=>1,//是否为系统角色：1、不允许操作 0、否
                            'menu_ids'=>array_column($menu_list,'menu_id')
                        );
                        $role = MerchantRoleService::add($role_data);
                        /******************创建用户******************************/
                        $user_data = array(
                            'mer_id'=>$v['id'],//商家id
                            'number'=>'001',//编号
                            'nickname'=>isset($v['username'])?$v['username']:$v['title'],//用户昵称
                            'username'=>$v['username'],//用户账号
                            'phone'=>$v['phone'],//手机
                            'remark'=>'系统默认',//备注
                            'sort'=>1,//排序
                            'is_super'=>1,//是否超管，1是0否
                            'is_admin'=>1,//是否为系统角色：1、不允许操作 0、否
                            'role_ids'=>[$role['role_id']],
                            'password'=>123456,
                        );
                        $role = MerchantUserService::add($user_data);
                        break;
                    case 2://审核失败
                        $v->auth_state=2;
                        $v->auth_msg=$param['auth_msg'];
                        break;
                }
                $v->save();
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
        MerchantCache::clear();
        return $param;
    }
    /**
     * @title: 前端用户提交商家申请
     * @author：易军辉
     * @date：2025/9/14
     * @param $param
     * @return mixed
     */
    public static function userAdd($param)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        $param['create_uid']  = member_id();
        $param['create_time'] = datetime();
        $param['member_id'] = member_id();
        $param['username'] =$param['phone'];
        // 密码
        $param['password'] = password_hash(123456, PASSWORD_BCRYPT);
//        // 启动事务
        $model->startTrans();
        try {
            if(isset($param['id']) && $param['id']){
                $model = $model->append(['image_ids'])->find($param['id']);
                if($model){
                  relation_update($model, $model['image_ids'], file_ids($param['images']), 'files', ['file_type' => 'image']);
                  $param['auth_state'] = MerchantModel::getAuthState('wait',1);
                  $edit_res=  $model->where('id',$param['id'])->update($param);
                }
            }else{
                $model->save($param);
                // 添加文件
                if (isset($param['images']) && $param['images']) {
                    $model->files()->saveAll(file_ids($param['images']), ['file_type' => 'image'], true);
                }
            }
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
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
        return $param;
    }
     /**
     * 商家管理修改
     *
     * @param int|array $ids   商家管理id
     * @param array     $param 商家管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantModel();
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
                $edit_pwd = Db::name('merchant_user')
                    ->where('is_delete',0)
                    ->where('is_disable',0)
                    ->where('is_admin',1)
                    ->whereIn('mer_id',$ids)
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
        if (isset($errmsg)) {
            exception($errmsg);
        }
        $param['ids'] = $ids;
        MerchantCache::del($ids);
        return $param;
    }
    /**
     * 商家管理删除
     *
     * @param array $ids  商家管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        if ($real) {
            $res = $model->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $update['auth_state']=2;
            $update['auth_msg']="您已被管理员取消商家身份";
            $res = $model->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        MerchantCache::del($ids);
        return $update;
    }

    /**
     * 商家收入
     * @Author: 易军辉
     * @DateTime:2025-02-20 15:42
     * @param $mer_id 商家ID
     * @param $money 收入金额
     * @param $title 收入名称
     * @param $data_id 关联表ID
     * @param $remark 备注
     */
    public static function recharge($mer_id=null,$money=0,$title="",$data_id=null,$remark=null)
    {
        //商家ID
        if(!$mer_id){
            $mer_id = mer_id(true);
        }
        //创建用户ID
        $create_uid = operate_user_id();
        $merModel = new MerchantModel();
        $mer_money = $merModel->where('id',$mer_id)->field('id,mer_money')->find();

        $model = new MerchantBillModel();
        $pk = $model->getPk();
        $param = array(
            'create_uid'=>$create_uid,
            'create_time'=>datetime(),
            'mer_id'=>$mer_id,
            'money'=>$money,
            'type'=>MerchantBillModel::getType('RECHARGE',1),
            'data_id'=>$data_id,
            'title'=>$title,
            'remark'=>$remark,
        );
        // 启动事务
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',$mer_id)->update([
                'mer_money'=>bcadd($mer_money['mer_money'],$money,2)
            ]);
            if (empty($res)) {
                exception('充值失败');
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

        return $param;
    }

    /**
     * 商家提现
     * @Author: 易军辉
     * @DateTime:2025-02-20 15:42
     * @param $money 提现金额
     * @param $title 提现名称
     * @param $data_id 关联表ID
     * @param $remark 备注
     */
    public static function withdrawal($money=0,$title="",$data_id=null,$remark=null)
    {
        $merModel = new MerchantModel();
        $mer_money = $merModel->where('id',mer_id())->field('id,mer_money')->find();
        if($mer_money['mer_money']<$money){
            exception("您的余额".$mer_money['mer_money']."不足，无法提现");
        }
        if($money <=0){
            exception("金额有误，提现失败，请稍后再试！");
        }
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        $param = array(
            'create_uid'=>operate_user_id(),
            'create_time'=>datetime(),
            'mer_id'=>mer_id(true),
            'money'=>-$money,//操作金额
            'type'=>MerchantBillModel::getType('WITHDRAWAL',1),
            'data_id'=>$data_id,
            'title'=>$title,
            'remark'=>$remark,
        );
        // 启动事务
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',mer_id())->update(['mer_money'=>bcsub($mer_money['mer_money'],$money,2)]);
            if (empty($res)) {
                exception('提现失败');
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

        return $param;
    }


    /**
     * 商家支出
     * @Author: 易军辉
     * @DateTime:2025-02-20 18:42
     * @param $money 消费金额
     * @param $title 消费名称
     * @param $data_id 关联表ID
     * @param $remark 备注
     * @param $mer_id 商家id
     * @param $exce 异常是否报错
     */
    public static function consumption($money=0,$title="",$data_id=null,$remark=null,$mer_id=null,$exce = true)
    {
        //商家ID
        if(!$mer_id){
            $mer_id = mer_id(true);
        }
        //创建用户ID
        $create_uid = operate_user_id();
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        // 启动事务
        $model->startTrans();
        try {
            $merModel = new MerchantModel();
            $mer_money = $merModel->where('id',$mer_id)->field('id,mer_money')->find();
            if($mer_money['mer_money']<$money && $exce){
                exception("您的余额".$mer_money['mer_money']."不足，请充值");
            }
            if($money<=0 && $exce){
                exception("金额有误，支付失败，请稍后再试！");
            }
            $param = array(
                'create_uid'=>$create_uid,
                'create_time'=>datetime(),
                'mer_id'=>$mer_id,
                'money'=>-$money,//操作金额
                'type'=>MerchantBillModel::getType('CONSUMPTION',1),
                'data_id'=>$data_id,
                'title'=>$title,
                'remark'=>$remark,
            );
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',$mer_id)->update(['mer_money'=>bcsub($mer_money['mer_money'],$money,2)]);
            if (empty($res) && $exce) {
                exception('支付失败');
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

        return $param;
    }
}
