<?php
namespace app\common\service\goods;

use app\common\cache\goods\GoodsBuyLockCache;
use app\common\model\goods\GoodsModel;

class GoodsBuyLockService
{
    public static function lockForBuy(int $goodsId = 0, int $memberId = 0, int $ttl = 300): array
    {
        if ($memberId <= 0) {
            exception('请先登录');
        }
        if ($goodsId <= 0) {
            exception('商品参数错误');
        }

        $goods = GoodsModel::where('id', $goodsId)
            ->where('is_disable', 0)
            ->where('is_delete', 0)
            ->where('status', 1)
            ->field('id,stock')
            ->find();
        if (!$goods || intval($goods['stock']) <= 0) {
            exception('商品已售罄');
        }

        $lock = self::getLock($goodsId);
        if (!empty($lock) && intval($lock['member_id'] ?? 0) !== $memberId) {
            exception('该商品正被其他用户下单，请稍后再试');
        }

        $ttl = $ttl > 0 ? $ttl : 300;
        $payload = [
            'goods_id' => $goodsId,
            'member_id' => $memberId,
            'expire_at' => time() + $ttl,
        ];
        GoodsBuyLockCache::set($goodsId, $payload, $ttl);

        return self::getViewState($goodsId, $memberId);
    }

    public static function assertCanCheckout(int $goodsId = 0, int $memberId = 0): void
    {
        if ($goodsId <= 0 || $memberId <= 0) {
            exception('下单参数错误');
        }

        $lock = self::getLock($goodsId);
        if (empty($lock)) {
            exception('该商品下单已超时，请返回商品详情重新点击立即购买');
        }

        if (intval($lock['member_id'] ?? 0) !== $memberId) {
            exception('该商品已被其他用户抢先下单');
        }
    }

    public static function releaseByMember(array $goodsIds = [], int $memberId = 0): void
    {
        if ($memberId <= 0 || empty($goodsIds)) {
            return;
        }

        $goodsIds = array_values(array_unique(array_filter(array_map('intval', $goodsIds))));
        foreach ($goodsIds as $goodsId) {
            $lock = self::getLock($goodsId);
            if (!empty($lock) && intval($lock['member_id'] ?? 0) === $memberId) {
                GoodsBuyLockCache::del($goodsId);
            }
        }
    }

    public static function getViewState(int $goodsId = 0, int $memberId = 0): array
    {
        $state = [
            'buy_lock_enabled' => 1,
            'buy_lock_by_self' => 0,
            'buy_lock_by_other' => 0,
            'buy_lock_remain_seconds' => 0,
            'buy_lock_text' => '',
        ];

        if ($goodsId <= 0) {
            return $state;
        }

        $lock = self::getLock($goodsId);
        if (empty($lock)) {
            return $state;
        }

        $remain = max(0, intval($lock['expire_at'] ?? 0) - time());
        $state['buy_lock_remain_seconds'] = $remain;

        if ($memberId > 0 && intval($lock['member_id'] ?? 0) === $memberId) {
            $state['buy_lock_by_self'] = 1;
            $state['buy_lock_text'] = '去下单';
            return $state;
        }

        $state['buy_lock_by_other'] = 1;
        $state['buy_lock_text'] = '已被抢购';
        return $state;
    }

    private static function getLock(int $goodsId = 0): array
    {
        if ($goodsId <= 0) {
            return [];
        }

        $lock = GoodsBuyLockCache::get($goodsId);
        if (empty($lock) || !is_array($lock)) {
            return [];
        }

        $expireAt = intval($lock['expire_at'] ?? 0);
        if ($expireAt <= time()) {
            GoodsBuyLockCache::del($goodsId);
            return [];
        }

        return $lock;
    }
}
