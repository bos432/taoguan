<?php
namespace app\common\cache\finance;
use think\facade\Cache;
/**
 * 商家银行卡管理缓存
 */
class MerchantAccountCache
{
    // 缓存标签
    public static $tag = 'merchant_account';
    // 缓存前缀
    protected static $prefix = 'merchant_account:';
    /**
     * 缓存键名
     *
     * @param int $id 商家银行卡管理id
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
     * @param int   $id   商家银行卡管理id
     * @param array $info 商家银行卡管理信息
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
     * @param int $id 商家银行卡管理id
     *
     * @return array 商家银行卡管理信息
     */
    public static function get($id)
    {
        return Cache::get(self::key($id));
    }
    /**
     * 缓存删除
     *
     * @param mixed $id 商家银行卡管理id
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
