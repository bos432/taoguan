<?php
namespace app\common\cache\order;
use think\facade\Cache;
/**
 * 检测订单缓存
 */
class InspectionOrderCache
{
    // 缓存标签
    public static $tag = 'inspection_order';
    // 缓存前缀
    protected static $prefix = 'inspection_order:';
    /**
     * 缓存键名
     *
     * @param int $id 检测订单id
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
     * @param int   $id   检测订单id
     * @param array $info 检测订单信息
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
     * @param int $id 检测订单id
     *
     * @return array 检测订单信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 检测订单id
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
