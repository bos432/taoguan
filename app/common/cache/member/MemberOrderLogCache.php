<?php
namespace app\common\cache\member;
use think\facade\Cache;
/**
 * 订单日志缓存
 */
class MemberOrderLogCache
{
    // 缓存标签
    public static $tag = 'member_order_log';
    // 缓存前缀
    protected static $prefix = 'member_order_log:';
    /**
     * 缓存键名
     *
     * @param int $id 订单日志id
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
     * @param int   $id   订单日志id
     * @param array $info 订单日志信息
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
     * @param int $id 订单日志id
     *
     * @return array 订单日志信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 订单日志id
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
