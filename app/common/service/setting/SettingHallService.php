<?php
namespace app\common\service\setting;
use app\common\cache\setting\SettingHallCache;
use app\common\model\setting\SettingHallModel;

/**
 * 大厅管理
 */
class SettingHallService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'pid' => '',
        'code' => '',
        'is_disable' => '',
        'title' => '',
        'sort' => '',
        'remark' => '',
    ];
    /**
     * 大厅管理列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     *
     * @return array
     */
    public static function list($type = 'tree', $where = [], $order = [], $field = '')
    {
        $model = new SettingHallModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',code,title,is_disable,create_time,update_time,sort,pid';
        }
        if (empty($order)) {
            $order = ['sort' => 'asc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = SettingHallCache::get($key);
        if (empty($data)) {

            $data = $model->field($field)->where($where)
                ->order($order)->select()->toArray();
            foreach ($data as $k => $v) {
                if(array_key_exists('disable', $v)){
                    $data[$k]['disable'] = $v['disable']==1?true:false;
                }
            }
            if ($type == 'tree') {
                $data = array_to_tree($data, $pk, 'pid');
            }

            SettingHallCache::set($key, $data);
        }

        return $data;
    }
    /**
     * 大厅管理信息
     *
     * @param int  $id   大厅管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = SettingHallCache::get($id);
        if (empty($info)) {
            $model = new SettingHallModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('大厅管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            SettingHallCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 大厅管理添加
     *
     * @param array $param 大厅管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new SettingHallModel();
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
        SettingHallCache::clear();
        return $param;
    }
     /**
     * 大厅管理修改
     *
     * @param int|array $ids   大厅管理id
     * @param array     $param 大厅管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new SettingHallModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        SettingHallCache::clear();
        return $param;
    }
    /**
     * 大厅管理删除
     *
     * @param array $ids  大厅管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new SettingHallModel();
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
        SettingHallCache::clear();
        return $update;
    }
}
