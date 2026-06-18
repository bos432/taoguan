<?php
namespace app\common\service\trace;
use app\common\model\trace\TraceTacheModel;
use app\common\cache\trace\TraceTacheCache;
/**
 * 环节模板
 */
class TraceTacheService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'describe' => '',
        'is_disable' => '',
        'sort' => '',
        'remark' => '',
        'attributes' => '',
        'is_inspection_type/d'=>0,
        'is_inspection/d'=>0,
    ];
    /**
     * 环节模板列表
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
        $model = new TraceTacheModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,describe,is_disable,create_time,update_time,sort,remark';
        }
        if (empty($order)) {
            $order = ['sort'=>'asc',$pk => 'desc'];
        }
        $with     = [];
        if (strpos($field, 'id') !== false) {
            $with[] = 'attributes';
        }

        if ($page == 0 || $limit == 0) {
            return $model->with($with)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $key=>$val){
            $list[$key]['attributes_titles'] = "";
            if(isset($val['attributes'])){
                $list[$key]['attributes_titles'] =implode('、',array_column($val['attributes'],'label'));
            }
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * 环节模板信息
     *
     * @param int  $id   环节模板id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = TraceTacheCache::get($id);
        if (empty($info)) {
            $model = new TraceTacheModel();
            $mer_id = mer_id();
            $info = $model->with(['attributes'])->when($mer_id>0,function($query)use($mer_id){
                $query->where('merchant_id',$mer_id);
            })->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('环节模板不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            TraceTacheCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 环节模板添加
     *
     * @param array $param 环节模板信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new TraceTacheModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = mer_user_id();
        $param['create_time'] = datetime();
        if(isset($param['is_inspection_type']) && $param['is_inspection_type']>0){
            $param['is_inspection']=1;
        }
        // 启动事务
        $model->startTrans();
        try {
            $model->save($param);
            // 添加属性
            if (isset($param['attributes'])) {
                $model->attributes()->saveAll($param['attributes']);
            }
            $id = $model->$pk;
            if (empty($id)) {
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
        $param[$pk] = $id;
        return $param;
    }
     /**
     * 环节模板修改
     *
     * @param int|array $ids   环节模板id
     * @param array     $param 环节模板信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new TraceTacheModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = mer_user_id();
        $param['update_time'] = datetime();
        if(isset($param['is_inspection_type']) && $param['is_inspection_type']>0){
            $param['is_inspection']=1;
        }
        $mer_id = mer_id();
        // 启动事务
        $model->startTrans();
        try {
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                        $query->where('merchant_id',$mer_id);
                    })->where($pk, 'in', $ids)->update($param);
            if (empty($res)) {
                exception();
            }
            // 添加属性
            if (isset($param['attributes'])) {
                foreach ($ids as $id) {
                    $info = $model->when($mer_id>0,function($query)use($mer_id){
                        $query->where('merchant_id',$mer_id);
                    })->find($id);
                    // 获取当前关联的所有 attributes 的 ID
                    $existingAttributes = $info->attributes()->column('id');

                    // 提取出要更新的 attributes 的 ID
                    $newAttributeIds = array_column($param['attributes'], 'id');

                    // 找出需要删除的 IDs（存在于旧数据中，但不在新数据中）
                    $toDelete = array_diff($existingAttributes, $newAttributeIds);

                    // 删除多余的 attributes
                    if (!empty($toDelete)) {
                        $info->attributes()->whereIn('id', $toDelete)->delete();
                    }

                    // 使用 saveAll 更新或添加新属性
                    $info->attributes()->saveAll($param['attributes']);
                }
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
        TraceTacheCache::del($ids);
        return $param;
    }
    /**
     * 环节模板删除
     *
     * @param array $ids  环节模板id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new TraceTacheModel();
        $pk = $model->getPk();
        $mer_id = mer_id();
        if ($real) {
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                        $query->where('merchant_id',$mer_id);
                    })->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                        $query->where('merchant_id',$mer_id);
                    })->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        TraceTacheCache::del($ids);
        return $update;
    }
}
