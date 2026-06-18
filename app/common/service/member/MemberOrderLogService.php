<?php
namespace app\common\service\member;
use app\common\cache\member\MemberOrderLogCache;
use app\common\model\order\MemberOrderLogModel;

/**
 * 订单日志
 */
class MemberOrderLogService
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
        'member_order_id' => '',
        'role_type' => '',
    ];
    /**
     * 订单日志列表
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
        $model = new MemberOrderLogModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,content,is_disable,create_time,update_time,member_order_id,role_type';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
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
     * 订单日志信息
     *
     * @param int  $id   订单日志id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MemberOrderLogCache::get($id);
        if (empty($info)) {
            $model = new MemberOrderLogModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('订单日志不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MemberOrderLogCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 订单日志添加
     *
     * @param array $param 订单日志信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MemberOrderLogModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        if(!isset($param['create_uid'])){
            $param['create_uid']  = operate_user_id();
        }
        $param['create_time'] = datetime();
        $model->save($param);
        $id = $model->$pk;
        if (empty($id)) {
            exception();
        }
        $param[$pk] = $id;
        return $param;
    }
     /**
     * 订单日志修改
     *
     * @param int|array $ids   订单日志id
     * @param array     $param 订单日志信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MemberOrderLogModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MemberOrderLogCache::del($ids);
        return $param;
    }
    /**
     * 订单日志删除
     *
     * @param array $ids  订单日志id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MemberOrderLogModel();
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
        MemberOrderLogCache::del($ids);
        return $update;
    }
}
