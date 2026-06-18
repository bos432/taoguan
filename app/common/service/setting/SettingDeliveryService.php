<?php
namespace app\common\service\setting;
use app\common\cache\setting\SettingDeliveryCache;
use app\common\model\setting\SettingDeliveryModel;

/**
 * 快递管理
 */
class SettingDeliveryService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'code' => '',
        'is_disable' => '',
        'sort' => 250,
    ];
    /**
     * 快递管理列表
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
        $model = new SettingDeliveryModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,code,is_disable,create_time,update_time,sort';
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
     * 快递管理信息
     *
     * @param int  $id   快递管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = SettingDeliveryCache::get($id);
        if (empty($info)) {
            $model = new SettingDeliveryModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('快递管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            SettingDeliveryCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 快递管理添加
     *
     * @param array $param 快递管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new SettingDeliveryModel();
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
     * 快递管理修改
     *
     * @param int|array $ids   快递管理id
     * @param array     $param 快递管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new SettingDeliveryModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        SettingDeliveryCache::del($ids);
        return $param;
    }
    /**
     * 快递管理删除
     *
     * @param array $ids  快递管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new SettingDeliveryModel();
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
        SettingDeliveryCache::del($ids);
        return $update;
    }
}
