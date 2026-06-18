<?php
namespace app\common\cache\trace;
use think\facade\Cache;
/**
 * 环节模板缓存
 */
class TraceTacheCache
{
    // 缓存标签
    public static $tag = 'trace_tache';
    // 缓存前缀
    protected static $prefix = 'trace_tache:';
    /**
     * 缓存键名
     *
     * @param int $id 环节模板id
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
     * @param int   $id   环节模板id
     * @param array $info 环节模板信息
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
     * @param int $id 环节模板id
     *
     * @return array 环节模板信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 环节模板id
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
