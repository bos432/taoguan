<?php


namespace app\common\service\file;

use app\common\cache\file\InspectionGroupCache;
use app\common\model\file\InspectionGroupModel;
use app\common\cache\file\InspectionFileCache;
use app\common\model\file\InspectionFileModel;

/**
 * 文件分组
 */
class InspectionGroupService
{
    /**
     * 添加修改字段
     * @var array
     */
    public static $edit_field = [
        'group_id/d'     => '',
        'group_unique/s' => '',
        'group_name/s'   => '',
        'group_desc/s'   => '',
        'remark/s'       => '',
        'sort/d'         => 250,
    ];

    /**
     * 文件分组列表
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
        $model = new InspectionGroupModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',group_unique,group_name,group_desc,remark,sort,is_disable,create_time,update_time';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'desc'];
        }
        $where[] = ['ins_id', '=', ins_id()];
        $count = $model->where($where)->count();
        $pages = 0;
        if ($page > 0) {
            $model = $model->page($page);
        }
        if ($limit > 0) {
            $model = $model->limit($limit);
            $pages = ceil($count / $limit);
        }
        $list = $model->field($field)->where($where)->order($order)->select()->toArray();

        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    /**
     * 文件分组信息
     *
     * @param int|string $id   分组id
     * @param bool       $exce 不存在是否抛出异常
     * 
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = InspectionGroupCache::get($id);
        if (empty($info)) {
            $model = new InspectionGroupModel();
            $pk = $model->getPk();

            if (is_numeric($id)) {
                $where[] = [$pk, '=', $id];
            } else {
                $where[] = ['group_unique', '=', $id];
                $where[] = where_delete();
            }

            $info = $model->where('ins_id',ins_id())->where($where)->find();
            if (empty($info)) {
                if ($exce) {
                    exception('文件分组不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();

            InspectionGroupCache::set($id, $info);
        }

        return $info;
    }

    /**
     * 文件分组添加
     *
     * @param array $param 分组信息
     * 
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new InspectionGroupModel();
        $pk = $model->getPk();

        unset($param[$pk]);
        $param['ins_id']  = ins_id();
        $param['create_uid']  = ins_user_id();
        $param['create_time'] = datetime();
        if (empty($param['group_unique'] ?? '')) {
            $param['group_unique'] = uniqids();
        }

        $model->save($param);
        $id = $model->$pk;
        if (empty($id)) {
            exception();
        }

        $param[$pk] = $id;

        return $param;
    }

    /**
     * 文件分组修改
     *
     * @param int|array $ids   分组id
     * @param array     $param 分组信息
     * 
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new InspectionGroupModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = ins_user_id();
        $param['update_time'] = datetime();

        $unique = $model->where('ins_id',ins_id())->where($pk, 'in', $ids)->column('group_unique');

        $res = $model->where('ins_id',ins_id())->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        InspectionGroupCache::del($ids);
        InspectionGroupCache::del($unique);

        return $param;
    }

    /**
     * 文件分组删除
     *
     * @param array $ids  分组id
     * @param bool  $real 是否真实删除
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new InspectionGroupModel();
        $pk = $model->getPk();
        $ins_id = ins_id();
        $unique = $model->where('ins_id',$ins_id)->where($pk, 'in', $ids)->column('group_unique');

        if ($real) {
            $res = $model->where('ins_id',$ins_id)->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->where('ins_id',$ins_id)->where($pk, 'in', $ids)->update($update);
        }

        if (empty($res)) {
            exception();
        }

        $update['ids'] = $ids;

        InspectionGroupCache::del($ids);
        InspectionGroupCache::del($unique);

        return $update;
    }

    /**
     * 文件分组文件
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function file($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        return InspectionFileService::list($where, $page, $limit, $order, $field);
    }

    /**
     * 文件分组文件解除
     *
     * @param array $group_id 分组id
     * @param array $file_ids 文件id
     *
     * @return int
     */
    public static function fileRemove($group_id, $file_ids = [])
    {
        $where[] = ['group_id', 'in', $group_id];
        $where[] = ['ins_id', '=', ins_id()];
        if (empty($file_ids)) {
            $file_ids = InspectionFileModel::where($where)->column('file_id');
        }
        $where[] = ['file_id', 'in', $file_ids];

        $res = InspectionFileModel::where($where)->update(['group_id' => 0, 'update_uid' => user_id(), 'update_time' => datetime()]);

        InspectionFileCache::del($file_ids);

        return $res;
    }
}
