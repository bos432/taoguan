<?php
namespace app\common\service\setting;
use app\common\cache\setting\SettingWarehouseCache;
use app\common\model\setting\SettingWarehouseModel;

/**
 * 仓库管理
 */
class SettingWarehouseService
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
        'setting_hall_id/d'=>null,
        'address' => '',
    ];
    /**
     * 仓库管理列表
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
        $model = new SettingWarehouseModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',code,title,is_disable,create_time,update_time,sort,pid,setting_hall_id,address';
        }
        if (empty($order)) {
            $order = ['sort' => 'asc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = SettingWarehouseCache::get($key);
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

            SettingWarehouseCache::set($key, $data);
        }

        return $data;
    }
    /**
     * 仓库管理信息
     *
     * @param int  $id   仓库管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = SettingWarehouseCache::get($id);
        if (empty($info)) {
            $model = new SettingWarehouseModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('仓库管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            SettingWarehouseCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 仓库管理添加
     *
     * @param array $param 仓库管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new SettingWarehouseModel();
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
        SettingWarehouseCache::clear();
        return $param;
    }
     /**
     * 仓库管理修改
     *
     * @param int|array $ids   仓库管理id
     * @param array     $param 仓库管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new SettingWarehouseModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        SettingWarehouseCache::clear();
        return $param;
    }
    /**
     * 仓库管理删除
     *
     * @param array $ids  仓库管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new SettingWarehouseModel();
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
        SettingWarehouseCache::clear();
        return $update;
    }
}
