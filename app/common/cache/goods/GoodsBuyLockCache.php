<?php
namespace app\common\cache\goods;

use think\facade\Cache;

class GoodsBuyLockCache
{
    public static $tag = 'goods_buy_lock';
    protected static $prefix = 'goods_buy_lock:';

    public static function key($goodsId)
    {
        return self::$prefix . intval($goodsId);
    }

    public static function set($goodsId, $info, $ttl = 300)
    {
        return Cache::tag(self::$tag)->set(self::key($goodsId), $info, intval($ttl));
    }

    public static function get($goodsId)
    {
        return Cache::get(self::key($goodsId));
    }

    public static function del($goodsId)
    {
        return Cache::delete(self::key($goodsId));
    }
}
