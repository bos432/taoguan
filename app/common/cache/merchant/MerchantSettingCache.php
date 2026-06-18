<?php


namespace app\common\cache\merchant;

use think\facade\Cache;

/**
 * 系统设置缓存
 */
class MerchantSettingCache
{
    // 缓存标签
    public static $tag = 'merchant_setting';
    // 缓存前缀
    protected static $prefix = 'merchant_setting:';

    /**
     * 缓存键名
     *
     * @param int $id 设置id
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
     * @param int   $id   设置id
     * @param array $info 设置信息
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
     * @param int $id 设置id
     * 
     * @return array 设置信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }

    /**
     * 缓存删除
     *
     * @param int $id 设置id
     * 
     * @return bool
     */
    public static function del($id)
    {
        return Cache::delete(self::key($id));
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
