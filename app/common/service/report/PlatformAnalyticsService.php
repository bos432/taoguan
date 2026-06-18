<?php

namespace app\common\service\report;

use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\goods\GoodsTypeService;
use app\common\service\merchant\MerchantService;
use think\facade\Db;

class PlatformAnalyticsService
{
    public static function resolveScopeData($params = [])
    {
        return self::buildScope($params);
    }

    public static function createOrderBaseQuery($scope, $params = [])
    {
        return self::buildOrderBaseQuery($scope, $params);
    }

    public static function createPaidCompletedQuery($scope, $params = [])
    {
        return self::buildPaidCompletedQuery($scope, $params);
    }

    public static function createRefundQuery($scope, $params = [])
    {
        return self::buildRefundQuery($scope, $params);
    }

    public static function createGoodsQuery($scope)
    {
        return self::buildGoodsQuery($scope);
    }

    private static function applyDateRange($query, string $field, string $startTime, string $endTime)
    {
        return $query
            ->where($field, '>=', $startTime)
            ->where($field, '<=', $endTime);
    }

    private static function paidAmountExpression(string $fieldPrefix = ''): string
    {
        $prefix = $fieldPrefix !== '' ? $fieldPrefix . '.' : '';
        return "(CASE WHEN {$prefix}pay_price > 0 THEN {$prefix}pay_price ELSE {$prefix}total_price END)";
    }

    private static function queryPaidAmount($query, string $fieldPrefix = ''): float
    {
        $row = (clone $query)
            ->fieldRaw('SUM(' . self::paidAmountExpression($fieldPrefix) . ') as amount_value')
            ->find();

        return self::toFloat($row['amount_value'] ?? 0);
    }

    public static function getFilters()
    {
        $merchantList = MerchantModel::where('is_delete', 0)
            ->where('is_disable', 0)
            ->field('id,title,auth_state,expire_time,renew_remind_days')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        foreach ($merchantList as $index => $merchant) {
            $merchantList[$index] = MerchantService::appendExpireMeta($merchant);
        }

        return [
            'quick_ranges' => [
                ['value' => 'today', 'label' => '今天'],
                ['value' => 'yesterday', 'label' => '昨天'],
                ['value' => 'last7', 'label' => '近7天'],
                ['value' => 'last15', 'label' => '近15天'],
                ['value' => 'last30', 'label' => '近30天'],
                ['value' => 'this_month', 'label' => '本月'],
                ['value' => 'last_month', 'label' => '上月'],
            ],
            'merchant_statuses' => MerchantModel::AUTH_STATE,
            'expire_statuses' => MerchantService::getExpireStatusOptions(),
            'goods_types' => GoodsTypeService::list('tree', where_disdel(), [], 'id,pid,title,id as value,title as label'),
            'order_statuses' => MemberOrderModel::STATUS,
            'pay_statuses' => [
                ['value' => -1, 'label' => '全部'],
                ['value' => 0, 'label' => '未支付'],
                ['value' => 1, 'label' => '已支付'],
            ],
            'sources' => [
                ['value' => -1, 'label' => '全部'],
                ['value' => 0, 'label' => '平台商品'],
                ['value' => 1, 'label' => '商家发布'],
            ],
            'granularity_options' => [
                ['value' => 'day', 'label' => '按天'],
                ['value' => 'month', 'label' => '按月'],
            ],
            'merchants' => array_map(function ($merchant) {
                return [
                    'id' => intval($merchant['id']),
                    'title' => $merchant['title'],
                    'auth_state' => intval($merchant['auth_state']),
                    'expire_status' => $merchant['expire_status'] ?? 'unset',
                    'expire_status_title' => $merchant['expire_status_title'] ?? '未设置期限',
                ];
            }, $merchantList),
        ];
    }

    public static function summary($params = [])
    {
        $scope = self::buildScope($params);
        $range = $scope['range'];
        $refundStatus = MemberOrderModel::getStatus('refund', 1);

        $transactionQuery = self::applyDateRange(
            self::buildPaidCompletedQuery($scope, $params),
            'pay_time',
            $range['start_time'],
            $range['end_time']
        );
        $goodsQuery = self::buildGoodsQuery($scope);
        $refundAmount = self::toFloat(
            self::applyDateRange(
                (clone self::buildOrderBaseQuery($scope, $params))
                    ->where('pay_status', 1)
                    ->where('status', $refundStatus),
                'pay_time',
                $range['start_time'],
                $range['end_time']
            )
                ->sum('refund_price')
        );

        $gmv = self::queryPaidAmount($transactionQuery);
        $paidOrderCount = intval((clone $transactionQuery)->count());
        $paidBuyerCount = intval((clone $transactionQuery)->distinct(true)->count('member_id'));
        $activeMerchantCount = intval((clone $transactionQuery)->distinct(true)->count('merchant_id'));

        $newMerchantCount = 0;
        foreach ($scope['merchant_list'] as $merchant) {
            $createTime = $merchant['create_time'] ?? '';
            if ($createTime >= $range['start_time'] && $createTime <= $range['end_time']) {
                $newMerchantCount++;
            }
        }

        $expiringMerchantCount = 0;
        $expiredMerchantCount = 0;
        foreach ($scope['merchant_list'] as $merchant) {
            if (($merchant['expire_status'] ?? '') === 'remind') {
                $expiringMerchantCount++;
            }
            if (($merchant['expire_status'] ?? '') === 'expired') {
                $expiredMerchantCount++;
            }
        }

        $onSaleGoodsCount = intval((clone $goodsQuery)->where('status', 1)->where('stock', '>', 0)->count());
        $soldOutGoodsCount = intval((clone $goodsQuery)->where('status', 1)->where('stock', '<=', 0)->count());

        return [
            'range' => $range,
            'cards' => [
                'gmv' => $gmv,
                'paid_order_count' => $paidOrderCount,
                'paid_buyer_count' => $paidBuyerCount,
                'average_order_amount' => $paidOrderCount > 0 ? round($gmv / $paidOrderCount, 2) : 0,
                'refund_amount' => $refundAmount,
                'refund_rate' => $gmv > 0 ? round(($refundAmount / $gmv) * 100, 2) : 0,
                'new_merchant_count' => $newMerchantCount,
                'active_merchant_count' => $activeMerchantCount,
                'expiring_merchant_count' => $expiringMerchantCount,
                'expired_merchant_count' => $expiredMerchantCount,
                'on_sale_goods_count' => $onSaleGoodsCount,
                'sold_out_goods_count' => $soldOutGoodsCount,
            ],
        ];
    }

    public static function trend($params = [])
    {
        $scope = self::buildScope($params);
        $range = $scope['range'];
        $buckets = self::buildBuckets($range);

        $tradeRows = self::applyDateRange(
            self::buildPaidCompletedQuery($scope, $params),
            'pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field(self::periodField('pay_time', $range['granularity']) . ' as period, COUNT(id) as order_count, COUNT(DISTINCT member_id) as buyer_count, SUM(' . self::paidAmountExpression() . ') as amount')
            ->group('period')
            ->select()
            ->toArray();
        $tradeMap = self::rowsToMap($tradeRows, 'period');

        $refundRows = self::applyDateRange(
            self::buildRefundQuery($scope, $params),
            'pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field(self::periodField('pay_time', $range['granularity']) . ' as period, COUNT(id) as refund_count, SUM(refund_price) as refund_amount')
            ->group('period')
            ->select()
            ->toArray();
        $refundMap = self::rowsToMap($refundRows, 'period');

        $merchantRows = [];
        foreach ($scope['merchant_list'] as $merchant) {
            $period = self::formatPeriod($merchant['create_time'] ?? '', $range['granularity']);
            if ($period === '') {
                continue;
            }
            if (!isset($merchantRows[$period])) {
                $merchantRows[$period] = ['new_merchant_count' => 0];
            }
            $merchantRows[$period]['new_merchant_count']++;
        }

        $renewQuery = self::applyDateRange(
            Db::name('merchant_renew_record')
                ->where('is_delete', 0)
                ->where('is_disable', 0),
            'create_time',
            $range['start_time'],
            $range['end_time']
        );
        if ($scope['merchant_force_empty']) {
            $renewQuery->where('merchant_id', 0);
        } elseif (!empty($scope['merchant_ids'])) {
            $renewQuery->whereIn('merchant_id', $scope['merchant_ids']);
        }
        $renewRows = $renewQuery
            ->field(self::periodField('create_time', $range['granularity']) . ' as period, COUNT(id) as renew_count, SUM(amount) as renew_amount')
            ->group('period')
            ->select()
            ->toArray();
        $renewMap = self::rowsToMap($renewRows, 'period');

        $merchantTotalBefore = 0;
        foreach ($scope['merchant_list'] as $merchant) {
            $createTime = $merchant['create_time'] ?? '';
            if ($createTime !== '' && $createTime < $range['start_time']) {
                $merchantTotalBefore++;
            }
        }

        $tradeTrend = [];
        $refundTrend = [];
        $merchantTrend = [];
        $renewTrend = [];
        $cumulativeMerchantCount = $merchantTotalBefore;

        foreach ($buckets as $bucket) {
            $period = $bucket['period'];
            $trade = $tradeMap[$period] ?? [];
            $refund = $refundMap[$period] ?? [];
            $merchantAdd = intval($merchantRows[$period]['new_merchant_count'] ?? 0);
            $renew = $renewMap[$period] ?? [];

            $cumulativeMerchantCount += $merchantAdd;

            $tradeTrend[] = [
                'period' => $period,
                'label' => $bucket['label'],
                'amount' => self::toFloat($trade['amount'] ?? 0),
                'order_count' => intval($trade['order_count'] ?? 0),
                'buyer_count' => intval($trade['buyer_count'] ?? 0),
            ];
            $refundTrend[] = [
                'period' => $period,
                'label' => $bucket['label'],
                'refund_amount' => self::toFloat($refund['refund_amount'] ?? 0),
                'refund_count' => intval($refund['refund_count'] ?? 0),
            ];
            $merchantTrend[] = [
                'period' => $period,
                'label' => $bucket['label'],
                'new_merchant_count' => $merchantAdd,
                'cumulative_merchant_count' => $cumulativeMerchantCount,
            ];
            $renewTrend[] = [
                'period' => $period,
                'label' => $bucket['label'],
                'renew_amount' => self::toFloat($renew['renew_amount'] ?? 0),
                'renew_count' => intval($renew['renew_count'] ?? 0),
            ];
        }

        return [
            'range' => $range,
            'trade_trend' => $tradeTrend,
            'refund_trend' => $refundTrend,
            'merchant_growth_trend' => $merchantTrend,
            'renew_trend' => $renewTrend,
        ];
    }

    public static function ranking($params = [])
    {
        $scope = self::buildScope($params);
        $range = $scope['range'];

        $selectedPayStatus = self::normalizeInt($params['pay_status'] ?? null);
        if (in_array($selectedPayStatus, [0], true)) {
            return [
                'range' => $range,
                'top_goods' => [],
                'top_merchants' => [],
            ];
        }

        $topGoodsQuery = Db::name('member_order_detailed')
            ->alias('detail')
            ->join('member_order order_info', 'order_info.id = detail.member_order_id')
            ->join('goods goods_info', 'goods_info.id = detail.goods_id')
            ->where('order_info.is_delete', 0)
            ->where('order_info.is_disable', 0)
            ->where('order_info.pay_status', 1)
            ->where('order_info.status', '<>', MemberOrderModel::getStatus('refund', 1))
            ->where('goods_info.is_delete', 0)
            ->where('goods_info.is_disable', 0);
        $topGoodsQuery = self::applyDateRange(
            $topGoodsQuery,
            'order_info.pay_time',
            $range['start_time'],
            $range['end_time']
        );

        if ($scope['merchant_force_empty'] || $scope['goods_order_force_empty']) {
            $topGoodsQuery->where('order_info.id', 0);
        } elseif (!empty($scope['merchant_list'])) {
            self::applyMerchantGoodsScope($topGoodsQuery, $scope['merchant_list'], 'goods_info');
        }
        if (!empty($scope['goods_type_ids'])) {
            $topGoodsQuery->whereIn('goods_info.goods_type_id', $scope['goods_type_ids']);
        }
        if ($scope['source'] !== null) {
            $topGoodsQuery->where('goods_info.source', $scope['source']);
        }
        self::applyAmountFilter($topGoodsQuery, $params);

        $topGoodsRows = $topGoodsQuery
            ->field('detail.goods_id, SUM(detail.quantity) as sale_num, SUM(detail.total) as sale_amount, COUNT(DISTINCT detail.member_order_id) as order_count')
            ->group('detail.goods_id')
            ->order('sale_amount desc, sale_num desc')
            ->limit(10)
            ->select()
            ->toArray();

        $goodsIds = array_column($topGoodsRows, 'goods_id');
        $goodsList = [];
        if (!empty($goodsIds)) {
            $goodsList = GoodsModel::whereIn('id', $goodsIds)
                ->with(['image'])
                ->append(['image_url'])
                ->hidden(['image'])
                ->field('id,title,image_id,unit,merchant_id,member_id')
                ->select()
                ->toArray();
        }
        $goodsMap = [];
        foreach ($goodsList as $goods) {
            $goodsMap[intval($goods['id'])] = $goods;
        }
        $merchantIdByMemberId = [];
        foreach ($scope['merchant_list'] as $merchant) {
            $merchantMemberId = intval($merchant['member_id'] ?? 0);
            if ($merchantMemberId > 0) {
                $merchantIdByMemberId[$merchantMemberId] = intval($merchant['id'] ?? 0);
            }
        }

        $topGoods = [];
        foreach ($topGoodsRows as $row) {
            $goods = $goodsMap[intval($row['goods_id'])] ?? [];
            $goodsMerchantId = intval($goods['merchant_id'] ?? 0);
            if ($goodsMerchantId <= 0) {
                $goodsMerchantId = intval($merchantIdByMemberId[intval($goods['member_id'] ?? 0)] ?? 0);
            }
            $topGoods[] = [
                'goods_id' => intval($row['goods_id']),
                'title' => $goods['title'] ?? '已删除商品',
                'image_url' => $goods['image_url'] ?? '',
                'unit' => $goods['unit'] ?? '',
                'merchant_id' => $goodsMerchantId,
                'sale_num' => intval($row['sale_num'] ?? 0),
                'sale_amount' => self::toFloat($row['sale_amount'] ?? 0),
                'order_count' => intval($row['order_count'] ?? 0),
            ];
        }

        $topMerchantOwnerExpression = self::ownerMerchantIdExpression('goods_info', 'owner_merchant');
        $topMerchantQuery = self::applyDateRange(
            self::buildMerchantOwnedOrderDetailQuery($scope, $params)
                ->where('order_info.pay_status', 1)
                ->where('order_info.status', '<>', MemberOrderModel::getStatus('refund', 1))
                ->whereRaw($topMerchantOwnerExpression . ' > 0'),
            'order_info.pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field($topMerchantOwnerExpression . ' as merchant_id, COUNT(DISTINCT order_info.id) as paid_order_count, COUNT(DISTINCT order_info.member_id) as buyer_count, SUM(detail.total) as paid_amount')
            ->group($topMerchantOwnerExpression)
            ->order('paid_amount desc, paid_order_count desc')
            ->limit(10);

        $topMerchantRows = $topMerchantQuery->select()->toArray();
        $merchantIds = array_column($topMerchantRows, 'merchant_id');
        $merchantList = [];
        if (!empty($merchantIds)) {
            $merchantList = MerchantModel::whereIn('id', $merchantIds)
                ->where('is_delete', 0)
                ->field('id,title,auth_state,expire_time,renew_remind_days')
                ->select()
                ->toArray();
        }
        $merchantMap = [];
        foreach ($merchantList as $merchant) {
            $merchant = MerchantService::appendExpireMeta($merchant);
            $merchantMap[intval($merchant['id'])] = $merchant;
        }

        $goodsCountRows = [];
        if (!empty($merchantIds)) {
            $goodsOwnerExpression = self::ownerMerchantIdExpression('goods_info', 'owner_merchant');
            $goodsCountRows = self::buildMerchantOwnedGoodsQuery($scope)
                ->whereRaw($goodsOwnerExpression . ' > 0')
                ->field($goodsOwnerExpression . ' as merchant_id, COUNT(goods_info.id) as goods_count')
                ->group($goodsOwnerExpression)
                ->select()
                ->toArray();
        }
        $goodsCountMap = [];
        foreach ($goodsCountRows as $row) {
            $goodsCountMap[intval($row['merchant_id'])] = intval($row['goods_count']);
        }

        $topMerchants = [];
        foreach ($topMerchantRows as $row) {
            $merchant = $merchantMap[intval($row['merchant_id'])] ?? [];
            $authState = intval($merchant['auth_state'] ?? 0);
            $topMerchants[] = [
                'merchant_id' => intval($row['merchant_id']),
                'title' => $merchant['title'] ?? '未知商家',
                'auth_state' => $authState,
                'auth_state_title' => MerchantModel::getAuthState($authState, 2),
                'expire_status' => $merchant['expire_status'] ?? 'unset',
                'expire_status_title' => $merchant['expire_status_title'] ?? '未设置期限',
                'paid_amount' => self::toFloat($row['paid_amount'] ?? 0),
                'paid_order_count' => intval($row['paid_order_count'] ?? 0),
                'buyer_count' => intval($row['buyer_count'] ?? 0),
                'goods_count' => intval($goodsCountMap[intval($row['merchant_id'])] ?? 0),
            ];
        }

        return [
            'range' => $range,
            'top_goods' => $topGoods,
            'top_merchants' => $topMerchants,
        ];
    }

    public static function alerts($params = [])
    {
        $scope = self::buildScope($params);
        $range = $scope['range'];
        $alerts = [];

        foreach ($scope['merchant_list'] as $merchant) {
            if (($merchant['expire_status'] ?? '') === 'expired') {
                $alerts[] = [
                    'type' => 'merchant_expired',
                    'level' => 'danger',
                    'merchant_id' => intval($merchant['id']),
                    'merchant_title' => $merchant['title'],
                    'message' => '商家服务已到期',
                    'value' => $merchant['expire_time'] ?? '',
                ];
            } elseif (($merchant['expire_status'] ?? '') === 'remind') {
                $alerts[] = [
                    'type' => 'merchant_expiring',
                    'level' => 'warning',
                    'merchant_id' => intval($merchant['id']),
                    'merchant_title' => $merchant['title'],
                    'message' => '商家服务即将到期',
                    'value' => ($merchant['days_left'] ?? 0) . ' 天',
                ];
            }
        }

        $refundRows = self::applyDateRange(
            self::buildRefundQuery($scope, $params),
            'pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field('merchant_id, SUM(refund_price) as refund_amount, COUNT(id) as refund_count')
            ->group('merchant_id')
            ->select()
            ->toArray();
        $refundMap = self::rowsToMap($refundRows, 'merchant_id');

        $ownerMerchantExpression = self::ownerMerchantIdExpression('goods_info', 'owner_merchant');

        $paidRows = self::applyDateRange(
            self::buildMerchantOwnedOrderDetailQuery($scope, $params)
                ->where('order_info.pay_status', 1)
                ->where('order_info.status', '<>', MemberOrderModel::getStatus('refund', 1))
                ->whereRaw($ownerMerchantExpression . ' > 0'),
            'order_info.pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field($ownerMerchantExpression . ' as merchant_id, SUM(detail.total) as paid_amount, COUNT(DISTINCT order_info.id) as paid_order_count')
            ->group($ownerMerchantExpression)
            ->select()
            ->toArray();
        $paidMap = self::rowsToMap($paidRows, 'merchant_id');

        $activeGoodsRows = self::buildMerchantOwnedGoodsQuery($scope)
            ->where('goods_info.status', 1)
            ->where('goods_info.stock', '>', 0)
            ->whereRaw($ownerMerchantExpression . ' > 0')
            ->field($ownerMerchantExpression . ' as merchant_id, COUNT(goods_info.id) as on_sale_goods_count')
            ->group($ownerMerchantExpression)
            ->select()
            ->toArray();
        $activeGoodsMap = self::rowsToMap($activeGoodsRows, 'merchant_id');

        $voucherQuery = self::applyDateRange(
            self::buildMerchantOwnedOrderDetailQuery($scope, $params)
                ->where('order_info.pay_type', 2)
                ->where('order_info.pay_status', 0)
                ->where('order_info.pay_voucher_imgs', '<>', '')
                ->whereRaw($ownerMerchantExpression . ' > 0'),
            'order_info.create_time',
            $range['start_time'],
            $range['end_time']
        );
        $voucherRows = $voucherQuery
            ->field($ownerMerchantExpression . ' as merchant_id, COUNT(DISTINCT order_info.id) as pending_voucher_count')
            ->group($ownerMerchantExpression)
            ->select()
            ->toArray();
        $voucherMap = self::rowsToMap($voucherRows, 'merchant_id');

        foreach ($scope['merchant_list'] as $merchant) {
            $merchantId = intval($merchant['id']);
            $merchantTitle = $merchant['title'];
            $paidAmount = self::toFloat($paidMap[$merchantId]['paid_amount'] ?? 0);
            $refundAmount = self::toFloat($refundMap[$merchantId]['refund_amount'] ?? 0);
            $refundRate = $paidAmount > 0 ? round($refundAmount / $paidAmount, 2) : 0;

            if ($paidAmount <= 0 && intval($activeGoodsMap[$merchantId]['on_sale_goods_count'] ?? 0) > 0) {
                $alerts[] = [
                    'type' => 'merchant_low_activity',
                    'level' => 'info',
                    'merchant_id' => $merchantId,
                    'merchant_title' => $merchantTitle,
                    'message' => '近筛选周期内有在售商品但无成交',
                    'value' => intval($activeGoodsMap[$merchantId]['on_sale_goods_count'] ?? 0) . ' 个在售商品',
                ];
            }

            if ($refundAmount > 0 && $refundRate >= 0.3) {
                $alerts[] = [
                    'type' => 'merchant_high_refund',
                    'level' => 'warning',
                    'merchant_id' => $merchantId,
                    'merchant_title' => $merchantTitle,
                    'message' => '退款率偏高',
                    'value' => round($refundRate * 100, 2) . '%',
                ];
            }

            $pendingVoucherCount = intval($voucherMap[$merchantId]['pending_voucher_count'] ?? 0);
            if ($pendingVoucherCount > 0) {
                $alerts[] = [
                    'type' => 'merchant_pending_voucher',
                    'level' => 'warning',
                    'merchant_id' => $merchantId,
                    'merchant_title' => $merchantTitle,
                    'message' => '存在待审核支付凭证订单',
                    'value' => $pendingVoucherCount . ' 笔',
                ];
            }
        }

        $levelWeight = ['danger' => 3, 'warning' => 2, 'info' => 1];
        usort($alerts, function ($left, $right) use ($levelWeight) {
            $leftWeight = $levelWeight[$left['level']] ?? 0;
            $rightWeight = $levelWeight[$right['level']] ?? 0;
            if ($leftWeight === $rightWeight) {
                return strcmp((string) $left['merchant_title'], (string) $right['merchant_title']);
            }
            return $rightWeight <=> $leftWeight;
        });

        return [
            'range' => $range,
            'count' => count($alerts),
            'list' => array_slice($alerts, 0, 50),
        ];
    }

    public static function merchantDetail($params = [])
    {
        $scope = self::buildScope($params);
        $range = $scope['range'];
        $merchantId = intval($params['merchant_id'] ?? 0);
        $refundStatus = MemberOrderModel::getStatus('refund', 1);

        if ($merchantId <= 0) {
            exception('缺少商家ID');
        }

        $merchant = [];
        foreach ($scope['merchant_list'] as $item) {
            if (intval($item['id'] ?? 0) === $merchantId) {
                $merchant = $item;
                break;
            }
        }
        if (empty($merchant)) {
            exception('商家不存在或当前筛选条件下不可见');
        }

        $merchantMemberId = intval($merchant['member_id'] ?? 0);
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $orderQuery = self::buildOrderBaseQuery($scope, $params);
        $paidOrderQuery = self::buildPaidCompletedQuery($scope, $params);
        $goodsQuery = self::buildGoodsQuery($scope);
        $purchasePaidQuery = Db::name('member_order')
            ->where('member_id', $merchantMemberId)
            ->where('merchant_id', '<>', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('pay_status', 1)
            ->where('status', '<>', $refundStatus);
        self::applyAmountFilter($purchasePaidQuery, $params);
        $rangeOrderQuery = self::applyDateRange((clone $orderQuery), 'create_time', $range['start_time'], $range['end_time']);
        $rangePaidOrderQuery = self::applyDateRange((clone $paidOrderQuery), 'pay_time', $range['start_time'], $range['end_time']);

        $overview = [
            'order_count' => intval((clone $rangeOrderQuery)->count()),
            'paid_order_count' => intval((clone $rangePaidOrderQuery)->count()),
            'paid_amount' => self::queryPaidAmount($rangePaidOrderQuery),
            'today_paid_amount' => self::queryPaidAmount(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $todayStart, $todayEnd)),
            'today_buyer_count' => intval(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $todayStart, $todayEnd)->distinct(true)->count('member_id')),
            'goods_count' => intval((clone $goodsQuery)->count()),
            'on_sale_goods_count' => intval((clone $goodsQuery)->where('status', 1)->where('stock', '>', 0)->count()),
            'sold_out_goods_count' => intval((clone $goodsQuery)->where('status', 1)->where('stock', '<=', 0)->count()),
            'pending_goods_count' => intval((clone $goodsQuery)->where('status', 0)->count()),
        ];
        $overview['average_order_amount'] = $overview['paid_order_count'] > 0
            ? round($overview['paid_amount'] / $overview['paid_order_count'], 2)
            : 0;

        $buckets = self::buildBuckets($range);
        $orderRows = self::applyDateRange(
            (clone $orderQuery),
            'create_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field(self::periodField('create_time', $range['granularity']) . ' as period, COUNT(id) as order_count')
            ->group('period')
            ->select()
            ->toArray();
        $paidRows = self::applyDateRange(
            (clone $paidOrderQuery),
            'pay_time',
            $range['start_time'],
            $range['end_time']
        )
            ->field(self::periodField('pay_time', $range['granularity']) . ' as period, COUNT(id) as paid_order_count, SUM(' . self::paidAmountExpression() . ') as paid_amount')
            ->group('period')
            ->select()
            ->toArray();
        $purchaseRows = $merchantMemberId > 0
            ? self::applyDateRange(
                (clone $purchasePaidQuery),
                'pay_time',
                $range['start_time'],
                $range['end_time']
            )
                ->field(self::periodField('pay_time', $range['granularity']) . ' as period, COUNT(id) as buy_order_count, SUM(' . self::paidAmountExpression() . ') as buy_amount')
                ->group('period')
                ->select()
                ->toArray()
            : [];

        $orderMap = self::rowsToMap($orderRows, 'period');
        $paidMap = self::rowsToMap($paidRows, 'period');
        $purchaseMap = self::rowsToMap($purchaseRows, 'period');

        $trend = [];
        foreach ($buckets as $bucket) {
            $period = $bucket['period'];
            $trend[] = [
                'period' => $period,
                'label' => $bucket['label'],
                'order_count' => intval($orderMap[$period]['order_count'] ?? 0),
                'paid_order_count' => intval($paidMap[$period]['paid_order_count'] ?? 0),
                'paid_amount' => self::toFloat($paidMap[$period]['paid_amount'] ?? 0),
                'buy_order_count' => intval($purchaseMap[$period]['buy_order_count'] ?? 0),
                'buy_amount' => self::toFloat($purchaseMap[$period]['buy_amount'] ?? 0),
            ];
        }

        $selectedOrderStatus = self::normalizeInt($params['order_status'] ?? null);
        $selectedPayStatus = self::normalizeInt($params['pay_status'] ?? null);
        $topGoodsQuery = Db::name('member_order_detailed')
            ->alias('detail')
            ->join('member_order order_info', 'order_info.id = detail.member_order_id')
            ->join('goods goods_info', 'goods_info.id = detail.goods_id')
            ->where('order_info.is_delete', 0)
            ->where('order_info.is_disable', 0)
            ->where('order_info.pay_status', 1)
            ->where('order_info.status', '<>', $refundStatus)
            ->where('goods_info.is_delete', 0)
            ->where('goods_info.is_disable', 0);
        self::applyMerchantGoodsScope($topGoodsQuery, [$merchant], 'goods_info');
        $topGoodsQuery = self::applyDateRange(
            $topGoodsQuery,
            'order_info.pay_time',
            $range['start_time'],
            $range['end_time']
        );
        if ($selectedOrderStatus !== null && $selectedOrderStatus >= 0) {
            $topGoodsQuery->where('order_info.status', $selectedOrderStatus);
        }
        if (in_array($selectedPayStatus, [0], true)) {
            $topGoodsQuery->where('order_info.id', 0);
        }
        if (!empty($scope['goods_type_ids'])) {
            $topGoodsQuery->whereIn('goods_info.goods_type_id', $scope['goods_type_ids']);
        }
        if ($scope['source'] !== null) {
            $topGoodsQuery->where('goods_info.source', $scope['source']);
        }
        self::applyAmountFilter($topGoodsQuery, $params);
        $topGoodsRows = $topGoodsQuery
            ->field('detail.goods_id, SUM(detail.quantity) as sale_num, SUM(detail.total) as sale_amount, COUNT(DISTINCT detail.member_order_id) as order_count')
            ->group('detail.goods_id')
            ->order('sale_amount desc, sale_num desc')
            ->limit(5)
            ->select()
            ->toArray();

        $goodsIds = array_column($topGoodsRows, 'goods_id');
        $goodsMap = [];
        if (!empty($goodsIds)) {
            $goodsList = GoodsModel::whereIn('id', $goodsIds)
                ->with(['image'])
                ->append(['image_url'])
                ->hidden(['image'])
                ->field('id,title,image_id,source,unit')
                ->select()
                ->toArray();
            foreach ($goodsList as $goods) {
                $goodsMap[intval($goods['id'])] = $goods;
            }
        }

        $topGoods = [];
        foreach ($topGoodsRows as $row) {
            $goodsId = intval($row['goods_id']);
            $goods = $goodsMap[$goodsId] ?? [];
            $topGoods[] = [
                'goods_id' => $goodsId,
                'title' => $goods['title'] ?? '商品已删除',
                'image_url' => $goods['image_url'] ?? '',
                'unit' => $goods['unit'] ?? '',
                'sale_num' => intval($row['sale_num'] ?? 0),
                'sale_amount' => self::toFloat($row['sale_amount'] ?? 0),
                'order_count' => intval($row['order_count'] ?? 0),
            ];
        }

        $rangePaidAmount = self::queryPaidAmount(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $range['start_time'], $range['end_time']));
        $rangePaidOrderCount = intval(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $range['start_time'], $range['end_time'])->count());
        $rangePurchasePaidAmount = $merchantMemberId > 0
            ? self::queryPaidAmount(self::applyDateRange((clone $purchasePaidQuery), 'pay_time', $range['start_time'], $range['end_time']))
            : 0;
        $rangePurchasePaidOrderCount = $merchantMemberId > 0
            ? intval(self::applyDateRange((clone $purchasePaidQuery), 'pay_time', $range['start_time'], $range['end_time'])->count())
            : 0;
        $todayPaidAmount = self::queryPaidAmount(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $todayStart, $todayEnd));
        $todayPaidOrderCount = intval(self::applyDateRange((clone $paidOrderQuery), 'pay_time', $todayStart, $todayEnd)->count());
        $todayPurchasePaidAmount = $merchantMemberId > 0
            ? self::queryPaidAmount(self::applyDateRange((clone $purchasePaidQuery), 'pay_time', $todayStart, $todayEnd))
            : 0;
        $todayPurchasePaidOrderCount = $merchantMemberId > 0
            ? intval(self::applyDateRange((clone $purchasePaidQuery), 'pay_time', $todayStart, $todayEnd)->count())
            : 0;

        $tradeComparison = [
            'range_buy_amount' => $rangePurchasePaidAmount,
            'range_buy_order_count' => $rangePurchasePaidOrderCount,
            'range_sale_amount' => $rangePaidAmount,
            'range_sale_order_count' => $rangePaidOrderCount,
            'range_diff_amount' => round($rangePaidAmount - $rangePurchasePaidAmount, 2),
            'range_diff_order_count' => intval($rangePaidOrderCount - $rangePurchasePaidOrderCount),
            'today_buy_amount' => $todayPurchasePaidAmount,
            'today_buy_order_count' => $todayPurchasePaidOrderCount,
            'today_sale_amount' => $todayPaidAmount,
            'today_sale_order_count' => $todayPaidOrderCount,
            'today_diff_amount' => round($todayPaidAmount - $todayPurchasePaidAmount, 2),
            'today_diff_order_count' => intval($todayPaidOrderCount - $todayPurchasePaidOrderCount),
        ];

        return [
            'merchant' => $merchant,
            'overview' => $overview,
            'trend' => $trend,
            'top_goods' => $topGoods,
            'trade_comparison' => $tradeComparison,
        ];
    }

    private static function buildScope($params = [])
    {
        $range = self::resolveRange($params);
        $merchantScope = self::buildMerchantScope($params);
        $goodsTypeId = self::normalizeInt($params['goods_type_id'] ?? null);
        $goodsTypeIds = [];
        if ($goodsTypeId !== null && $goodsTypeId > 0) {
            $goodsTypeIds = GoodsTypeService::getSubIds($goodsTypeId, true);
        }

        $source = self::normalizeInt($params['source'] ?? null);
        if ($source !== null && !in_array($source, [0, 1], true)) {
            $source = null;
        }

        [$orderIdsByGoods, $goodsOrderForceEmpty, $orderScopeRequired] = self::resolveOrderIdsByGoodsFilters(
            $merchantScope['scope_list'],
            $goodsTypeIds,
            $source
        );

        return [
            'range' => $range,
            'merchant_list' => $merchantScope['list'],
            'merchant_ids' => $merchantScope['ids'],
            'merchant_scope_list' => $merchantScope['scope_list'],
            'merchant_scope_ids' => $merchantScope['scope_ids'],
            'merchant_scope_active' => $merchantScope['scope_active'],
            'merchant_force_empty' => $merchantScope['force_empty'],
            'goods_type_ids' => $goodsTypeIds,
            'source' => $source,
            'order_ids_by_goods' => $orderIdsByGoods,
            'order_scope_required' => $orderScopeRequired,
            'goods_order_force_empty' => $goodsOrderForceEmpty,
        ];
    }

    private static function extractMerchantMemberIds(array $merchantList = []): array
    {
        return array_values(array_unique(array_filter(array_map(function ($merchant) {
            return intval($merchant['member_id'] ?? 0);
        }, $merchantList))));
    }

    private static function applyMerchantGoodsScope($query, array $merchantList = [], string $goodsAlias = '')
    {
        if (empty($merchantList)) {
            return $query;
        }

        $merchantIds = array_values(array_unique(array_filter(array_map(function ($merchant) {
            return intval($merchant['id'] ?? 0);
        }, $merchantList))));
        $merchantMemberIds = self::extractMerchantMemberIds($merchantList);
        $prefix = $goodsAlias !== '' ? $goodsAlias . '.' : '';
        $merchantField = $prefix . 'merchant_id';
        $memberField = $prefix . 'member_id';

        if (empty($merchantIds) && empty($merchantMemberIds)) {
            $query->where('id', 0);
            return $query;
        }

        $query->where(function ($scopeQuery) use ($merchantIds, $merchantMemberIds, $merchantField, $memberField) {
            if (!empty($merchantIds)) {
                $scopeQuery->whereIn($merchantField, $merchantIds);
            }

            if (!empty($merchantMemberIds)) {
                $method = !empty($merchantIds) ? 'whereOr' : 'where';
                $scopeQuery->{$method}(function ($platformQuery) use ($merchantField, $memberField, $merchantMemberIds) {
                    $platformQuery->where($merchantField, 0)
                        ->whereIn($memberField, $merchantMemberIds);
                });
            }
        });

        return $query;
    }

    private static function buildMerchantScope($params = [])
    {
        $merchantId = self::normalizeInt($params['merchant_id'] ?? null);
        $authState = self::normalizeInt($params['auth_state'] ?? null);
        $expireStatus = trim((string) ($params['expire_status'] ?? ''));

        $query = MerchantModel::where('is_delete', 0)->where('is_disable', 0);
        if ($merchantId !== null && $merchantId > 0) {
            $query->where('id', $merchantId);
        }
        if ($authState !== null && $authState >= 0) {
            $query->where('auth_state', $authState);
        }

        $list = $query
            ->field('id,title,name,phone,auth_state,create_time,member_id,expire_time,renew_remind_days,is_disable,is_delete')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        foreach ($list as $index => $merchant) {
            $list[$index] = MerchantService::appendExpireMeta($merchant);
        }

        if ($expireStatus !== '' && $expireStatus !== '-1') {
            $list = array_values(array_filter($list, function ($merchant) use ($expireStatus) {
                return ($merchant['expire_status'] ?? '') === $expireStatus;
            }));
        }

        $hasFilters = ($merchantId !== null && $merchantId > 0)
            || ($authState !== null && $authState >= 0)
            || ($expireStatus !== '' && $expireStatus !== '-1');
        $scopeList = $hasFilters ? $list : [];
        return [
            'list' => $list,
            'ids' => array_values(array_map('intval', array_column($list, 'id'))),
            'scope_list' => $scopeList,
            'scope_ids' => array_values(array_map('intval', array_column($scopeList, 'id'))),
            'scope_active' => $hasFilters,
            'force_empty' => $hasFilters && empty($list),
        ];
    }

    private static function resolveOrderIdsByGoodsFilters($merchantList = [], $goodsTypeIds = [], $source = null)
    {
        $orderScopeRequired = !empty($merchantList) || !empty($goodsTypeIds) || $source !== null;
        if (!$orderScopeRequired) {
            return [[], false, false];
        }

        $query = Db::name('member_order_detailed')
            ->alias('detail')
            ->join('goods goods_info', 'goods_info.id = detail.goods_id')
            ->join('member_order order_info', 'order_info.id = detail.member_order_id')
            ->where('goods_info.is_delete', 0)
            ->where('goods_info.is_disable', 0)
            ->where('order_info.is_delete', 0)
            ->where('order_info.is_disable', 0);

        if (!empty($merchantList)) {
            self::applyMerchantGoodsScope($query, $merchantList, 'goods_info');
        }
        if (!empty($goodsTypeIds)) {
            $query->whereIn('goods_info.goods_type_id', $goodsTypeIds);
        }
        if ($source !== null) {
            $query->where('goods_info.source', $source);
        }

        $orderIds = $query->group('detail.member_order_id')->column('detail.member_order_id');
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));

        return [$orderIds, empty($orderIds), true];
    }

    private static function buildOrderBaseQuery($scope, $params = [])
    {
        $query = Db::name('member_order')
            ->where('is_delete', 0)
            ->where('is_disable', 0);

        if ($scope['merchant_force_empty'] || $scope['goods_order_force_empty']) {
            $query->where('id', 0);
            return $query;
        }

        if (!empty($scope['order_ids_by_goods'])) {
            $query->whereIn('id', $scope['order_ids_by_goods']);
        } elseif (!empty($scope['order_scope_required'])) {
            $query->where('id', 0);
        }

        $orderStatus = self::normalizeInt($params['order_status'] ?? null);
        if ($orderStatus !== null && $orderStatus >= 0) {
            $query->where('status', $orderStatus);
        }

        $payStatus = self::normalizeInt($params['pay_status'] ?? null);
        if ($payStatus !== null && in_array($payStatus, [0, 1], true)) {
            $query->where('pay_status', $payStatus);
        }

        self::applyAmountFilter($query, $params);

        return $query;
    }

    private static function buildPaidCompletedQuery($scope, $params = [])
    {
        $query = self::buildOrderBaseQuery($scope, $params);
        $payStatus = self::normalizeInt($params['pay_status'] ?? null);
        if (in_array($payStatus, [0], true)) {
            $query->where('id', 0);
            return $query;
        }

        $query->where('pay_status', 1)
            ->where('status', '<>', MemberOrderModel::getStatus('refund', 1));

        return $query;
    }

    private static function buildRefundQuery($scope, $params = [])
    {
        $query = self::buildOrderBaseQuery($scope, $params);
        $payStatus = self::normalizeInt($params['pay_status'] ?? null);
        if (in_array($payStatus, [0], true)) {
            $query->where('id', 0);
            return $query;
        }

        $query->where('pay_status', 1)
            ->where('status', MemberOrderModel::getStatus('refund', 1));

        return $query;
    }

    private static function buildGoodsQuery($scope)
    {
        $query = GoodsModel::where('is_delete', 0)
            ->where('is_disable', 0);

        if ($scope['merchant_force_empty']) {
            $query->where('id', 0);
            return $query;
        }

        if (!empty($scope['merchant_scope_list'])) {
            self::applyMerchantGoodsScope($query, $scope['merchant_scope_list']);
        }
        if (!empty($scope['goods_type_ids'])) {
            $query->whereIn('goods_type_id', $scope['goods_type_ids']);
        }
        if ($scope['source'] !== null) {
            $query->where('source', $scope['source']);
        }

        return $query;
    }

    private static function ownerMerchantIdExpression(string $goodsAlias = 'goods_info', string $ownerAlias = 'owner_merchant'): string
    {
        $goodsPrefix = $goodsAlias !== '' ? $goodsAlias . '.' : '';
        $ownerPrefix = $ownerAlias !== '' ? $ownerAlias . '.' : '';
        return "CASE WHEN {$goodsPrefix}merchant_id > 0 THEN {$goodsPrefix}merchant_id ELSE {$ownerPrefix}id END";
    }

    private static function buildMerchantOwnedOrderDetailQuery($scope, $params = [])
    {
        $query = Db::name('member_order_detailed')
            ->alias('detail')
            ->join('member_order order_info', 'order_info.id = detail.member_order_id')
            ->join('goods goods_info', 'goods_info.id = detail.goods_id')
            ->leftJoin('merchant owner_merchant', 'owner_merchant.member_id = goods_info.member_id AND owner_merchant.is_delete = 0 AND owner_merchant.is_disable = 0')
            ->where('order_info.is_delete', 0)
            ->where('order_info.is_disable', 0)
            ->where('goods_info.is_delete', 0)
            ->where('goods_info.is_disable', 0);

        if ($scope['merchant_force_empty'] || $scope['goods_order_force_empty']) {
            $query->where('order_info.id', 0);
            return $query;
        }

        if (!empty($scope['merchant_scope_list'])) {
            self::applyMerchantGoodsScope($query, $scope['merchant_scope_list'], 'goods_info');
        }
        if (!empty($scope['goods_type_ids'])) {
            $query->whereIn('goods_info.goods_type_id', $scope['goods_type_ids']);
        }
        if ($scope['source'] !== null) {
            $query->where('goods_info.source', $scope['source']);
        }

        self::applyAmountFilter($query, $params, 'order_info');

        return $query;
    }

    private static function buildMerchantOwnedGoodsQuery($scope)
    {
        $query = Db::name('goods')
            ->alias('goods_info')
            ->leftJoin('merchant owner_merchant', 'owner_merchant.member_id = goods_info.member_id AND owner_merchant.is_delete = 0 AND owner_merchant.is_disable = 0')
            ->where('goods_info.is_delete', 0)
            ->where('goods_info.is_disable', 0);

        if ($scope['merchant_force_empty']) {
            $query->where('goods_info.id', 0);
            return $query;
        }

        if (!empty($scope['merchant_scope_list'])) {
            self::applyMerchantGoodsScope($query, $scope['merchant_scope_list'], 'goods_info');
        }
        if (!empty($scope['goods_type_ids'])) {
            $query->whereIn('goods_info.goods_type_id', $scope['goods_type_ids']);
        }
        if ($scope['source'] !== null) {
            $query->where('goods_info.source', $scope['source']);
        }

        return $query;
    }

    private static function resolveRange($params = [])
    {
        $quickDate = trim((string) ($params['quick_date'] ?? ''));
        $month = trim((string) ($params['month'] ?? ''));
        $startDate = trim((string) ($params['start_date'] ?? ''));
        $endDate = trim((string) ($params['end_date'] ?? ''));

        $startTime = '';
        $endTime = '';
        $label = '';
        $mode = 'quick';

        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $startTime = date('Y-m-01 00:00:00', strtotime($month . '-01'));
            $endTime = date('Y-m-t 23:59:59', strtotime($month . '-01'));
            $label = $month;
            $mode = 'month';
        } elseif ($startDate !== '' && $endDate !== '') {
            $startTime = $startDate . ' 00:00:00';
            $endTime = $endDate . ' 23:59:59';
            $label = $startDate . ' 至 ' . $endDate;
            $mode = 'custom';
        } else {
            switch ($quickDate) {
                case 'today':
                    $startTime = date('Y-m-d 00:00:00');
                    $endTime = date('Y-m-d 23:59:59');
                    $label = '今天';
                    break;
                case 'yesterday':
                    $startTime = date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $endTime = date('Y-m-d 23:59:59', strtotime('-1 day'));
                    $label = '昨天';
                    break;
                case 'last15':
                    $startTime = date('Y-m-d 00:00:00', strtotime('-14 days'));
                    $endTime = date('Y-m-d 23:59:59');
                    $label = '近15天';
                    break;
                case 'last30':
                    $startTime = date('Y-m-d 00:00:00', strtotime('-29 days'));
                    $endTime = date('Y-m-d 23:59:59');
                    $label = '近30天';
                    break;
                case 'this_month':
                    $startTime = date('Y-m-01 00:00:00');
                    $endTime = date('Y-m-d 23:59:59');
                    $label = '本月';
                    break;
                case 'last_month':
                    $startTime = date('Y-m-01 00:00:00', strtotime('first day of last month'));
                    $endTime = date('Y-m-t 23:59:59', strtotime('last day of last month'));
                    $label = '上月';
                    break;
                case 'last7':
                default:
                    $startTime = date('Y-m-d 00:00:00', strtotime('-6 days'));
                    $endTime = date('Y-m-d 23:59:59');
                    $label = '近7天';
                    $quickDate = 'last7';
                    break;
            }
        }

        if ($startTime === '' || $endTime === '' || strtotime($startTime) === false || strtotime($endTime) === false || strtotime($startTime) > strtotime($endTime)) {
            $startTime = date('Y-m-d 00:00:00', strtotime('-6 days'));
            $endTime = date('Y-m-d 23:59:59');
            $label = '近7天';
            $quickDate = 'last7';
            $mode = 'quick';
        }

        $diffDays = intval(floor((strtotime($endTime) - strtotime($startTime)) / 86400)) + 1;
        $granularity = trim((string) ($params['granularity'] ?? ''));
        if (!in_array($granularity, ['day', 'month'], true)) {
            $granularity = $diffDays > 45 ? 'month' : 'day';
        }

        return [
            'mode' => $mode,
            'quick_date' => $quickDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_date' => date('Y-m-d', strtotime($startTime)),
            'end_date' => date('Y-m-d', strtotime($endTime)),
            'month' => $month,
            'label' => $label,
            'days' => $diffDays,
            'granularity' => $granularity,
        ];
    }

    private static function buildBuckets($range)
    {
        $buckets = [];
        $start = strtotime($range['start_time']);
        $end = strtotime($range['end_time']);

        if ($range['granularity'] === 'month') {
            $cursor = strtotime(date('Y-m-01 00:00:00', $start));
            $last = strtotime(date('Y-m-01 00:00:00', $end));
            while ($cursor <= $last) {
                $period = date('Y-m', $cursor);
                $buckets[] = [
                    'period' => $period,
                    'label' => $period,
                ];
                $cursor = strtotime('+1 month', $cursor);
            }
            return $buckets;
        }

        $cursor = strtotime(date('Y-m-d 00:00:00', $start));
        $last = strtotime(date('Y-m-d 00:00:00', $end));
        while ($cursor <= $last) {
            $period = date('Y-m-d', $cursor);
            $buckets[] = [
                'period' => $period,
                'label' => date('m-d', $cursor),
            ];
            $cursor = strtotime('+1 day', $cursor);
        }

        return $buckets;
    }

    private static function periodField($field, $granularity = 'day')
    {
        if ($granularity === 'month') {
            return "DATE_FORMAT({$field},'%Y-%m')";
        }
        return "DATE_FORMAT({$field},'%Y-%m-%d')";
    }

    private static function formatPeriod($value = '', $granularity = 'day')
    {
        if ($value === '' || strtotime($value) === false) {
            return '';
        }
        return $granularity === 'month' ? date('Y-m', strtotime($value)) : date('Y-m-d', strtotime($value));
    }

    private static function rowsToMap($rows = [], $key = 'period')
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row[$key] ?? '')] = $row;
        }
        return $map;
    }

    private static function normalizeInt($value)
    {
        if ($value === null || $value === '' || $value === 'null' || $value === 'undefined' || $value === '-1') {
            return null;
        }
        if (is_numeric($value)) {
            return intval($value);
        }
        return null;
    }

    private static function normalizeAmount($value)
    {
        if ($value === null || $value === '' || $value === 'null' || $value === 'undefined') {
            return null;
        }
        if (is_numeric($value)) {
            return round(floatval($value), 2);
        }
        return null;
    }

    private static function resolveAmountExpression($params = [], string $fieldPrefix = '')
    {
        $prefix = $fieldPrefix !== '' ? $fieldPrefix . '.' : '';
        $payStatus = self::normalizeInt($params['pay_status'] ?? null);
        if ($payStatus === 0) {
            return $prefix . 'total_price';
        }
        if ($payStatus === 1) {
            return $prefix . 'pay_price';
        }
        return '(CASE WHEN ' . $prefix . 'pay_status = 1 THEN ' . $prefix . 'pay_price ELSE ' . $prefix . 'total_price END)';
    }

    private static function applyAmountFilter($query, $params = [], string $fieldPrefix = '')
    {
        $amountMin = self::normalizeAmount($params['amount_min'] ?? ($params['min_amount'] ?? null));
        $amountMax = self::normalizeAmount($params['amount_max'] ?? ($params['max_amount'] ?? null));

        if ($amountMin === null && $amountMax === null) {
            return;
        }

        if ($amountMin !== null && $amountMax !== null && $amountMin > $amountMax) {
            [$amountMin, $amountMax] = [$amountMax, $amountMin];
        }

        $amountExpression = self::resolveAmountExpression($params, $fieldPrefix);
        if ($amountMin !== null) {
            $query->whereRaw($amountExpression . ' >= :amount_min', ['amount_min' => $amountMin]);
        }
        if ($amountMax !== null) {
            $query->whereRaw($amountExpression . ' <= :amount_max', ['amount_max' => $amountMax]);
        }
    }

    private static function toFloat($value)
    {
        return round(floatval($value), 2);
    }
}
