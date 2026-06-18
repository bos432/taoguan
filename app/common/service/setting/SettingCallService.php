<?php
namespace app\common\service\setting;
use app\common\cache\setting\SettingCallCache;
use app\common\model\setting\SettingCallModel;

/**
 * 称管理
 */
class SettingCallService
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
        'setting_hall_id/d' => null,
        'address' => '',
    ];
    /**
     * 称管理列表
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
        $model = new SettingCallModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',code,title,is_disable,create_time,update_time,sort,pid,setting_hall_id,address';
        }
        if (empty($order)) {
            $order = ['sort' => 'asc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = SettingCallCache::get($key);
        if (empty($data)) {
            $with     = [];
            $append   = [];
            $hidden   = [];
            if (strpos($field, 'setting_hall_id') !== false) {
                $with[]   = $hidden[]   = 'hall';
                $append[] = 'hall_title';
            }
            $data = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)
                ->order($order)->select()->toArray();

            if ($type == 'tree') {
                $data = array_to_tree($data, $pk, 'pid');
            }

            SettingCallCache::set($key, $data);
        }

        return $data;
    }
    /**
     * 称管理信息
     *
     * @param int  $id   称管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = SettingCallCache::get($id);
        if (empty($info)) {
            $model = new SettingCallModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('称管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            SettingCallCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 称管理添加
     *
     * @param array $param 称管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new SettingCallModel();
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
        SettingCallCache::clear();
        return $param;
    }
     /**
     * 称管理修改
     *
     * @param int|array $ids   称管理id
     * @param array     $param 称管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new SettingCallModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        SettingCallCache::clear();
        return $param;
    }
    /**
     * 称管理删除
     *
     * @param array $ids  称管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new SettingCallModel();
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
        SettingCallCache::clear();
        return $update;
    }
}
