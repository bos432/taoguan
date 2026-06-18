<?php

namespace app\common\service\goods;
use app\common\model\goods\GoodsTypeModel;
use app\common\cache\goods\GoodsTypeCache;
/**
 * 商品分类
 */
class GoodsTypeService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'pid/d'=>0,
        'code/s' => '',
        'title/s' => '',
        'is_disable/d' => 0,
        'image_id/d' => null,
        'sort/d' => 250,
    ];
    /**
     * 商品分类列表
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
        $model = new GoodsTypeModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',code,title,is_disable,create_time,update_time,image_id,sort,pid';
        }
        if (empty($order)) {
            $order = ['sort' => 'asc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = GoodsTypeCache::get($key);
        if (empty($data)) {
            $with = $append = $hidden = $field_no = [];
            if (strpos($field, 'image_id') !== false) {
                $with[]   = $hidden[] = 'image';
                $append[] = 'image_url';
            }
            $fields = explode(',', $field);
            foreach ($fields as $k => $v) {
                if (in_array($v, $field_no)) {
                    unset($fields[$k]);
                }
            }
            $field = implode(',', $fields);

            $data = $model->field($field)->where($where)
                ->with($with)->append($append)->hidden($hidden)
                ->order($order)->select()->toArray();

            if ($type == 'tree') {
                $data = array_to_tree($data, $pk, 'pid');
            }

            GoodsTypeCache::set($key, $data);
        }

        return $data;
    }
    /**
     * 商品分类信息
     *
     * @param int  $id   商品分类id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = GoodsTypeCache::get($id);
        if (empty($info)) {
            $model = new GoodsTypeModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('商品分类不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            GoodsTypeCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 商品分类添加
     *
     * @param array $param 商品分类信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new GoodsTypeModel();
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
        GoodsTypeCache::clear();
        return $param;
    }
     /**
     * 商品分类修改
     *
     * @param int|array $ids   商品分类id
     * @param array     $param 商品分类信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new GoodsTypeModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        GoodsTypeCache::clear();
        return $param;
    }
    /**
     * 商品分类删除
     *
     * @param array $ids  商品分类id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new GoodsTypeModel();
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
        GoodsTypeCache::clear();
        return $update;
    }

    /**
     * @title:需要查询的id
     * @author：易军辉
     * @date：2024/12/11
     * @param $id 商品分类id
     * @param $is_contain 是否包含查询id
     * @return array
     */
    public static function getSubIds($id,$is_contain=false)
    {
        $model = new GoodsTypeModel();
        $subIds= $model->getSubIds($id);
        if($is_contain){
            array_push($subIds,$id);
        }
        return $subIds;
    }
}
