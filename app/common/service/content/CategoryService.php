<?php


namespace app\common\service\content;

use app\common\cache\content\CategoryCache;
use app\common\cache\content\ContentCache;
use app\common\model\content\ContentModel;
use app\common\model\content\CategoryModel;
use app\common\model\content\AttributesModel;

/**
 * 内容分类
 */
class CategoryService
{
    private static $broken_name_fragments = [
        '??',
        '锟',
        '�',
    ];

    /**
     * 添加修改字段
     * @var array
     */
    public static $edit_field = [
        'category_id/d'     => '',
        'category_pid/d'    => 0,
        'category_name/s'   => '',
        'category_unique/s' => '',
        'image_id/d'        => 0,
        'title/s'           => '',
        'keywords/s'        => '',
        'description/s'     => '',
        'sort/d'            => 250,
        'remark/s'          => '',
        'images/a'          => [],
    ];

    /**
     * 内容分类列表
     * 
     * @param string $type  tree树形，list列表
     * @param array  $where 条件
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array
     */
    public static function list($type = 'tree', $where = [], $order = [], $field = '')
    {
        $model = new CategoryModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',category_pid,category_name,category_unique,image_id,sort,is_disable,create_time,update_time';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = CategoryCache::get($key);
        if (empty($data)) {
            $with = $append = $hidden = $field_no = [];
            if (strpos($field, 'image_id') !== false) {
                $with[]   = $hidden[] = 'image';
                $append[] = 'image_url';
            }
            if (strpos($field, 'images') !== false) {
                $with[]   = $hidden[]   = 'files';
                $append[] = $field_no[] = 'images';
            } elseif (strpos($field, 'image_urls') !== false) {
                $with[]   = $hidden[]   = 'files';
                $append[] = $field_no[] = 'image_urls';
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
                $data = array_to_tree($data, $pk, 'category_pid');
            }

            $data = self::sanitizeCategoryCollection($data);

            CategoryCache::set($key, $data);
        }

        $data = self::sanitizeCategoryCollection($data);

        return $data;
    }

    /**
     * 内容分类信息
     * 
     * @param int|string $id   分类id、标识
     * @param bool       $exce 不存在是否抛出异常
     * 
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = CategoryCache::get($id);
        if (empty($info)) {
            $model = new CategoryModel();
            $pk = $model->getPk();

            if (is_numeric($id)) {
                $where[] = [$pk, '=', $id];
            } else {
                $where[] = ['category_unique', '=', $id];
                $where[] = where_delete();
            }

            $info = $model->where($where)->find();
            if (empty($info)) {
                if ($exce) {
                    exception('内容分类不存在：' . $id);
                }
                return [];
            }
            $info = $info->append(['image_url', 'images', 'image_urls'])->hidden(['image', 'files'])->toArray();

            CategoryCache::set($id, $info);
        }

        $info = self::sanitizeCategoryItem($info);

        return $info;
    }

    public static function normalizeCategoryName($name = '', $categoryId = 0, $fallback = '')
    {
        $name = trim((string) $name);
        if ($fallback === '') {
            $fallback = intval($categoryId) > 0 ? '分类#' . intval($categoryId) : '未命名分类';
        }

        if ($name === '' || self::isBrokenCategoryName($name)) {
            return $fallback;
        }

        return $name;
    }

    private static function sanitizeCategoryCollection($data = [])
    {
        foreach ($data as $index => $item) {
            $data[$index] = self::sanitizeCategoryItem($item);
        }

        return $data;
    }

    private static function sanitizeCategoryItem($item = [])
    {
        if (!is_array($item)) {
            return $item;
        }

        if (array_key_exists('category_name', $item)) {
            $item['category_name'] = self::normalizeCategoryName(
                $item['category_name'] ?? '',
                intval($item['category_id'] ?? 0)
            );
        }

        if (!empty($item['children']) && is_array($item['children'])) {
            $item['children'] = self::sanitizeCategoryCollection($item['children']);
        }

        return $item;
    }

    private static function isBrokenCategoryName($name = '')
    {
        $name = trim((string) $name);
        if ($name === '') {
            return true;
        }

        $normalized = strip_tags($name);
        $normalized = preg_replace('/[\s\/\\\\|,，。；;:：\-_#（）()\[\]【】]+/u', '', $normalized);
        if ($normalized !== '' && preg_match('/^[\?？]+$/u', $normalized) === 1) {
            return true;
        }

        foreach (self::$broken_name_fragments as $fragment) {
            if (mb_strpos($name, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 内容分类添加
     *
     * @param array $param 分类信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new CategoryModel();
        $pk = $model->getPk();

        unset($param[$pk]);

        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();
        if (empty($param['category_unique'] ?? '')) {
            $param['category_unique'] = uniqids();
        }

        // 启动事务
        $model->startTrans();
        try {
            // 添加
            $model->save($param);
            // 添加图片
            if (isset($param['images'])) {
                $file_ids = file_ids($param['images']);
                $model->files()->saveAll($file_ids);
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

        $param[$pk] = $model->$pk;

        CategoryCache::clear();

        return $param;
    }

    /**
     * 内容分类修改 
     *     
     * @param int|array $ids   分类id
     * @param array     $param 分类信息
     *     
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new CategoryModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();

        // 启动事务
        $model->startTrans();
        try {
            if (is_numeric($ids)) {
                $ids = [$ids];
            }
            // 修改
            $model->where($pk, 'in', $ids)->update($param);
            if (var_isset($param, ['images'])) {
                foreach ($ids as $id) {
                    $info = $model->find($id);
                    // 修改图片
                    if (isset($param['images'])) {
                        $info = $info->append(['image_ids']);
                        relation_update($info, $info['image_ids'], file_ids($param['images']), 'files');
                    }
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

        CategoryCache::clear();

        return $param;
    }

    /**
     * 内容分类删除
     * 
     * @param int|array $ids  分类id
     * @param bool      $real 是否真实删除
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new CategoryModel();
        $pk = $model->getPk();

        // 启动事务
        $model->startTrans();
        try {
            if (is_numeric($ids)) {
                $ids = [$ids];
            }
            if ($real) {
                foreach ($ids as $id) {
                    $info = $model->find($id);
                    // 删除图片
                    $info->files()->detach();
                }
                $model->where($pk, 'in', $ids)->delete();
            } else {
                $update = delete_update();
                $model->where($pk, 'in', $ids)->update($update);
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

        $update['ids'] = $ids;

        CategoryCache::clear();

        return $update;
    }

    /**
     * 内容分类内容
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function content($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        return ContentService::list($where, $page, $limit, $order, $field);
    }

    /**
     * 内容分类内容解除
     *
     * @param array $category_id 分类id
     * @param array $content_ids 内容id
     *
     * @return int
     */
    public static function contentRemove($category_id, $content_ids = [])
    {
        $where[] = ['category_id', 'in', $category_id];
        if (empty($content_ids)) {
            $content_ids = AttributesModel::where($where)->column('content_id');
        }
        $where[] = ['content_id', 'in', $content_ids];

        $res = AttributesModel::where($where)->delete();

        $model = new ContentModel();
        $pk = $model->getPk();
        $unique = $model->where($pk, 'in', $content_ids)->column('unique');

        ContentCache::del($content_ids);
        ContentCache::del($unique);

        return $res;
    }
}
