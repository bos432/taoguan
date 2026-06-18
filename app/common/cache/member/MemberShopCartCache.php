<?php
namespace app\common\cache\member;
use think\facade\Cache;
/**
 * 购物车缓存
 */
class MemberShopCartCache
{
    // 缓存标签
    public static $tag = 'member_shop_cart';
    // 缓存前缀
    protected static $prefix = 'member_shop_cart:';
    /**
     * 缓存键名
     *
     * @param int $id 购物车id
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
     * @param int   $id   购物车id
     * @param array $info 购物车信息
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
     * @param int $id 购物车id
     *
     * @return array 购物车信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 购物车id
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
