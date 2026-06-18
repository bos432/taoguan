<?php
namespace app\common\cache\trace;
use think\facade\Cache;
/**
 * 批次环节录入缓存
 */
class TraceBatchTacheCache
{
    // 缓存标签
    public static $tag = 'trace_batch_tache';
    // 缓存前缀
    protected static $prefix = 'trace_batch_tache:';
    /**
     * 缓存键名
     *
     * @param int $id 批次环节录入id
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
     * @param int   $id   批次环节录入id
     * @param array $info 批次环节录入信息
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
     * @param int $id 批次环节录入id
     *
     * @return array 批次环节录入信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 批次环节录入id
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
