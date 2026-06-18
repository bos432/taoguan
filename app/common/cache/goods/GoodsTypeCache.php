<?php

namespace app\common\cache\goods;
use think\facade\Cache;
/**
 * 商品分类缓存
 */
class GoodsTypeCache
{
    // 缓存标签
    public static $tag = 'goods_type';
    // 缓存前缀
    protected static $prefix = 'goods_type:';
    /**
     * 缓存键名
     *
     * @param int $id 商品分类id
     *
     * @return string
     */
    public static function key($id)
    {
        return self::$prefix . $id;
    }
    /**
     * 缓存设置
     *
     * @param int   $id   商品分类id
     * @param array $info 商品分类信息
     * @param int   $ttl  有效时间（秒，0永久）
     *
     * @return bool
     */
    public static function set($id, $info, $ttl = 43200)
    {
        return Cache::tag(self::$tag)->set(self::key($id), $info, $ttl);
    }
    /**
     * 缓存获取
     *
     * @param int $id 商品分类id
     *
     * @return array 商品分类信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 商品分类id
     *
     * @return bool
     */
    public static function del($id)
    {
        $ids = var_to_array($id);
        foreach ($ids as $v) {
            Cache::delete(self::key($v));
        }
        return true;
    }
    /**
     * 缓存清除
     *
     * @return bool
     */
    public static function clear()
    {
        return Cache::tag(self::$tag)->clear();
    }
}
