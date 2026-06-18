<?php
namespace app\common\service\member;
use app\common\cache\member\MemberBillCache;
use app\common\model\member\MemberBillModel;

/**
 * 会员账单
 */
class MemberBillService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'is_disable' => '',
        'member_id' => '',
        'title' => '',
        'in_out' => '',
        'money' => '',
        'bill_type_id' => '',
        'order_id' => '',
        'trans_id' => '',
        'remark' => '',
    ];
    /**
     * 会员账单列表
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
        $model = new MemberBillModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,member_id,title,in_out,money,bill_type_id,order_id,trans_id,remark';
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
     * 会员账单信息
     *
     * @param int  $id   会员账单id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MemberBillCache::get($id);
        if (empty($info)) {
            $model = new MemberBillModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('会员账单不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MemberBillCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 会员账单添加
     *
     * @param array $param 会员账单信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MemberBillModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = operate_user_id();
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
     * 会员账单修改
     *
     * @param int|array $ids   会员账单id
     * @param array     $param 会员账单信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MemberBillModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MemberBillCache::del($ids);
        return $param;
    }
    /**
     * 会员账单删除
     *
     * @param array $ids  会员账单id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MemberBillModel();
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
        MemberBillCache::del($ids);
        return $update;
    }
}
