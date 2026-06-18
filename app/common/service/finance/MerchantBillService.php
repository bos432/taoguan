<?php
namespace app\common\service\finance;
use app\common\cache\finance\MerchantBillCache;
use app\common\model\finance\MerchantBillModel;
use app\common\model\merchant\MerchantModel;

/**
 * 账单明细
 */
class MerchantBillService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'is_disable' => '',
        'mer_id' => '',
        'money' => '',
        'type' => '',
        'data_id' => '',
        'title' => '',
        'remark' => '',
    ];
    /**
     * 账单明细列表
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
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,mer_id,money,type,data_id,title,remark';
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
        foreach ($list as $key=>$val) {
            $list[$key]['type_str'] = MerchantBillModel::getType($val['type'],2);
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    /**
     * @title:查询参数
     * @author：易军辉
     * @date：2025/2/20
     */
    public static function getParams(){
        return ['bill_types'=>MerchantBillModel::TYPE];
    }
    /**
     * 账单明细信息
     *
     * @param int  $id   账单明细id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MerchantBillCache::get($id);
        if (empty($info)) {
            $model = new MerchantBillModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('账单明细不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MerchantBillCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 账单明细添加
     *
     * @param array $param 账单明细信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = user_id();
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
     * 账单明细修改
     *
     * @param int|array $ids   账单明细id
     * @param array     $param 账单明细信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MerchantBillCache::del($ids);
        return $param;
    }
    /**
     * 账单明细删除
     *
     * @param array $ids  账单明细id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantBillModel();
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
        MerchantBillCache::del($ids);
        return $update;
    }


}
