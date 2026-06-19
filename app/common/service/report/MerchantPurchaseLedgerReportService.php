<?php
namespace app\common\service\report;

use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use think\facade\Db;

class MerchantPurchaseLedgerReportService
{
    public static function filters(): array
    {
        $buyerRows = Db::name('merchant_purchase_ledger')
            ->where('is_delete', 0)
            ->field('buyer_merchant_id,buyer_merchant_title')
            ->group('buyer_merchant_id,buyer_merchant_title')
            ->order('buyer_merchant_id', 'desc')
            ->select()
            ->toArray();

        $sourceRows = Db::name('merchant_purchase_ledger')
            ->where('is_delete', 0)
            ->where('source_merchant_id', '>', 0)
            ->field('source_merchant_id,source_merchant_title')
            ->group('source_merchant_id,source_merchant_title')
            ->order('source_merchant_id', 'desc')
            ->select()
            ->toArray();

        return [
            'quick_dates' => [
                ['value' => 'all', 'label' => '全部时间'],
                ['value' => 'today', 'label' => '今天'],
                ['value' => 'yesterday', 'label' => '昨天'],
                ['value' => 'last7', 'label' => '近7天'],
                ['value' => 'last30', 'label' => '近30天'],
            ],
            'source_types' => [
                ['value' => '', 'label' => '全部来源'],
                ['value' => 'platform', 'label' => '平台商品'],
                ['value' => 'merchant', 'label' => '其他商家商品'],
            ],
            'buyer_merchants' => array_map(function ($row) {
                return [
                    'value' => intval($row['buyer_merchant_id'] ?? 0),
                    'label' => (string) ($row['buyer_merchant_title'] ?? ''),
                ];
            }, $buyerRows),
            'source_merchants' => array_merge([
                ['value' => 0, 'label' => '平台自营'],
            ], array_map(function ($row) {
                return [
                    'value' => intval($row['source_merchant_id'] ?? 0),
                    'label' => (string) ($row['source_merchant_title'] ?? ''),
                ];
            }, $sourceRows)),
        ];
    }

    public static function summary(array $params = []): array
    {
        $query = self::baseQuery($params);

        $totalRows = (clone $query)
            ->field('source_type,COUNT(DISTINCT member_order_id) as order_count,SUM(quantity) as quantity,SUM(total) as amount')
            ->group('source_type')
            ->select()
            ->toArray();

        $cards = [
            'total_amount' => 0,
            'platform_amount' => 0,
            'merchant_amount' => 0,
            'order_count' => 0,
            'quantity' => 0,
        ];
        foreach ($totalRows as $row) {
            $amount = self::toFloat($row['amount'] ?? 0);
            $cards['total_amount'] += $amount;
            $cards['order_count'] += intval($row['order_count'] ?? 0);
            $cards['quantity'] += intval($row['quantity'] ?? 0);
            if (($row['source_type'] ?? '') === 'platform') {
                $cards['platform_amount'] = $amount;
            }
            if (($row['source_type'] ?? '') === 'merchant') {
                $cards['merchant_amount'] = $amount;
            }
        }
        $cards['total_amount'] = self::toFloat($cards['total_amount']);

        $buyerRows = (clone $query)
            ->field('buyer_merchant_id,buyer_merchant_title,SUM(CASE WHEN source_type = "platform" THEN total ELSE 0 END) as platform_amount,SUM(CASE WHEN source_type = "merchant" THEN total ELSE 0 END) as merchant_amount,SUM(total) as total_amount,COUNT(DISTINCT member_order_id) as order_count,SUM(quantity) as quantity')
            ->group('buyer_merchant_id,buyer_merchant_title')
            ->order('total_amount desc,buyer_merchant_id desc')
            ->limit(20)
            ->select()
            ->toArray();

        foreach ($buyerRows as &$row) {
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['platform_amount'] = self::toFloat($row['platform_amount'] ?? 0);
            $row['merchant_amount'] = self::toFloat($row['merchant_amount'] ?? 0);
            $row['total_amount'] = self::toFloat($row['total_amount'] ?? 0);
            $row['order_count'] = intval($row['order_count'] ?? 0);
            $row['quantity'] = intval($row['quantity'] ?? 0);
        }

        $sourceRows = (clone $query)
            ->field('source_type,source_merchant_id,source_merchant_title,SUM(total) as amount,COUNT(DISTINCT member_order_id) as order_count,SUM(quantity) as quantity')
            ->group('source_type,source_merchant_id,source_merchant_title')
            ->order('amount desc,source_merchant_id desc')
            ->limit(20)
            ->select()
            ->toArray();

        foreach ($sourceRows as &$row) {
            $row['source_merchant_id'] = intval($row['source_merchant_id'] ?? 0);
            $row['source_type_title'] = ($row['source_type'] ?? '') === 'platform' ? '平台商品' : '商家商品';
            $row['amount'] = self::toFloat($row['amount'] ?? 0);
            $row['order_count'] = intval($row['order_count'] ?? 0);
            $row['quantity'] = intval($row['quantity'] ?? 0);
        }

        return [
            'cards' => $cards,
            'buyer_rank' => $buyerRows,
            'source_rank' => $sourceRows,
            'merchant_trade_compare' => self::buildMerchantTradeCompare($params),
            'reconciliation' => self::reconciliation($params),
            'query_label' => self::buildQueryLabel($params),
        ];
    }

    public static function reconciliation(array $params = []): array
    {
        $rows = self::appendReconciliationState(self::buildOrderReconciliationRows($params));
        $exceptionRows = self::filterRowsByReconciliationStatus($rows, self::normalizeReconciliationStatus($params['reconciliation_status'] ?? ''));
        $cards = [
            'order_count' => count($rows),
            'normal_count' => 0,
            'exception_count' => 0,
            'missing_bill_count' => 0,
            'missing_bill_amount' => 0,
            'ledger_mismatch_count' => 0,
            'ledger_mismatch_amount' => 0,
            'bill_mismatch_count' => 0,
            'bill_mismatch_amount' => 0,
            'amount_mismatch_count' => 0,
            'amount_mismatch_amount' => 0,
            'exception_amount' => 0,
            'voucher_exception_count' => 0,
            'wechat_exception_count' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['reconcile_status'] ?? '';
            $diffAmount = abs(floatval($row['reconcile_diff_amount'] ?? 0));
            if ($status === 'normal') {
                $cards['normal_count']++;
            } else {
                $cards['exception_count']++;
                $cards['exception_amount'] = self::toFloat($cards['exception_amount'] + $diffAmount);
                if (intval($row['pay_type'] ?? 0) === MemberOrderModel::getPayType('voucher', 1)) {
                    $cards['voucher_exception_count']++;
                } elseif (intval($row['pay_type'] ?? 0) === MemberOrderModel::getPayType('weChat', 1)) {
                    $cards['wechat_exception_count']++;
                }
                if ($status === 'missing_bill') {
                    $cards['missing_bill_count']++;
                    $cards['missing_bill_amount'] = self::toFloat($cards['missing_bill_amount'] + $diffAmount);
                } elseif ($status === 'ledger_mismatch') {
                    $cards['ledger_mismatch_count']++;
                    $cards['ledger_mismatch_amount'] = self::toFloat($cards['ledger_mismatch_amount'] + $diffAmount);
                } elseif ($status === 'bill_mismatch') {
                    $cards['bill_mismatch_count']++;
                    $cards['bill_mismatch_amount'] = self::toFloat($cards['bill_mismatch_amount'] + $diffAmount);
                } else {
                    $cards['amount_mismatch_count']++;
                    $cards['amount_mismatch_amount'] = self::toFloat($cards['amount_mismatch_amount'] + $diffAmount);
                }
            }
        }

        return [
            'cards' => $cards,
            'merchant_list' => self::buildMerchantReconciliationRows($exceptionRows),
            'exception_list' => array_values(array_slice($exceptionRows, 0, 50)),
            'selected_status' => self::normalizeReconciliationStatus($params['reconciliation_status'] ?? ''),
        ];
    }

    public static function list(array $params = []): array
    {
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, intval($params['limit'] ?? 20));
        $query = self::baseQuery($params);
        $reconciliationStatus = self::normalizeReconciliationStatus($params['reconciliation_status'] ?? '');
        if ($reconciliationStatus !== '') {
            $orderIds = self::buildFilteredReconciliationOrderIds($params, $reconciliationStatus);
            if (empty($orderIds)) {
                $query->where('member_order_id', 0);
            } else {
                $query->whereIn('member_order_id', $orderIds);
            }
        }
        $count = intval((clone $query)->count('id'));
        $pages = intval(ceil($count / $limit));
        $list = $query
            ->field('id,member_order_id,member_order_detailed_id,order_no,buyer_member_id,buyer_merchant_id,buyer_merchant_title,source_type,source_merchant_id,source_merchant_title,goods_id,goods_title,goods_code,goods_spec,goods_unit,quantity,price,total,order_pay_price,pay_type,pay_time,create_time')
            ->order('pay_time', 'desc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$row) {
            $row['id'] = intval($row['id'] ?? 0);
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['source_merchant_id'] = intval($row['source_merchant_id'] ?? 0);
            $row['source_type_title'] = ($row['source_type'] ?? '') === 'platform' ? '平台商品' : '商家商品';
            $row['quantity'] = intval($row['quantity'] ?? 0);
            $row['price'] = self::toFloat($row['price'] ?? 0);
            $row['total'] = self::toFloat($row['total'] ?? 0);
            $row['order_pay_price'] = self::toFloat($row['order_pay_price'] ?? 0);
        }
        self::attachListReconciliation($list);

        return [
            'count' => $count,
            'pages' => $pages,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
        ];
    }

    public static function export(array $params = []): array
    {
        $params['page'] = 1;
        $params['limit'] = 100000;
        $list = self::list($params)['list'] ?? [];
        $rows = [
            ['订单号', '支付时间', '支付方式', '支付状态', '订单状态', '核算结果', '核算说明', '核算差额', '买方商家ID', '买方商家', '来源类型', '来源商家ID', '来源名称', '商品ID', '商品名称', '商品编码', '规格', '单位', '数量', '单价', '明细金额', '核算实付', '核算口径', '账单金额'],
        ];

        foreach ($list as $item) {
            $rows[] = [
                $item['order_no'] ?? '',
                $item['pay_time'] ?? '',
                $item['pay_type_title'] ?? '',
                $item['pay_status_title'] ?? '',
                $item['order_status_title'] ?? '',
                $item['reconcile_status_title'] ?? '',
                $item['reconcile_message'] ?? '',
                self::toFloat($item['reconcile_diff_amount'] ?? 0),
                intval($item['buyer_merchant_id'] ?? 0),
                $item['buyer_merchant_title'] ?? '',
                $item['source_type_title'] ?? '',
                intval($item['source_merchant_id'] ?? 0),
                $item['source_merchant_title'] ?? '',
                intval($item['goods_id'] ?? 0),
                $item['goods_title'] ?? '',
                $item['goods_code'] ?? '',
                $item['goods_spec'] ?? '',
                $item['goods_unit'] ?? '',
                intval($item['quantity'] ?? 0),
                self::toFloat($item['price'] ?? 0),
                self::toFloat($item['total'] ?? 0),
                self::toFloat($item['order_current_pay_price'] ?? $item['order_pay_price'] ?? 0),
                $item['order_pay_price_source_title'] ?? '',
                self::toFloat($item['bill_amount'] ?? 0),
            ];
        }

        return [
            'filename' => 'merchant_purchase_ledger_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
        ];
    }

    public static function tradeDiffOrders(array $params = []): array
    {
        $merchantId = intval($params['merchant_id'] ?? 0);
        $direction = trim((string) ($params['direction'] ?? ''));
        $targetAmount = abs(self::toFloat($params['target_amount'] ?? 0));
        if ($merchantId < 0 || !in_array($direction, ['buy', 'sell'], true) || $targetAmount <= 0) {
            return self::emptyDiffOrdersResult($merchantId, $direction, $targetAmount, '差额参数不完整，暂时无法定位订单');
        }

        $queryParams = $params;
        unset($queryParams['merchant_id'], $queryParams['direction'], $queryParams['target_amount']);
        $queryParams['buyer_merchant_id'] = $direction === 'buy' ? $merchantId : 0;
        $queryParams['source_merchant_id'] = $direction === 'sell' ? $merchantId : -1;
        if ($direction === 'sell' && $merchantId === 0) {
            $queryParams['source_type'] = 'platform';
        }

        $orders = self::buildTradeDiffOrderRows($queryParams);
        $goodsGapResult = self::buildTradeDiffGoodsGaps($params, $merchantId, $direction, $targetAmount);
        $goodsGaps = $goodsGapResult['rows'] ?? [];
        $balanceOrders = self::buildGoodsGapDiffOrderRows($goodsGaps);
        if (!empty($balanceOrders)) {
            self::attachTradeDiffOrderState($balanceOrders);
            return [
                'merchant_id' => $merchantId,
                'direction' => $direction,
                'target_amount' => $targetAmount,
                'match_type' => 'balance',
                'message' => $direction === 'sell'
                    ? '系统按商品买入/卖出流水配平后，找到未配平卖出订单，优先核对是否超卖或来源未登记。'
                    : '系统按商品买入/卖出流水配平后，找到未配平买入订单，优先核对是否还未卖出或未核销。',
                'orders' => $balanceOrders,
                'candidate_orders' => [],
                'goods_gaps' => $goodsGaps,
                'goods_gap_match_type' => $goodsGapResult['match_type'] ?? 'none',
                'goods_gap_message' => $goodsGapResult['message'] ?? '',
            ];
        }
        if (empty($orders)) {
            return self::emptyDiffOrdersResult($merchantId, $direction, $targetAmount, '当前筛选范围内没有找到可匹配的订单', $goodsGaps, $goodsGapResult);
        }

        $exactSingle = [];
        foreach ($orders as $order) {
            if (abs(floatval($order['amount'] ?? 0) - $targetAmount) <= 0.01) {
                $exactSingle[] = $order;
            }
        }

        if (!empty($exactSingle)) {
            return [
                'merchant_id' => $merchantId,
                'direction' => $direction,
                'target_amount' => $targetAmount,
                'match_type' => 'single',
                'message' => '系统找到单笔金额刚好等于差额的订单，优先核对这些订单。',
                'orders' => array_slice($exactSingle, 0, 20),
                'candidate_orders' => [],
                'goods_gaps' => $goodsGaps,
                'goods_gap_match_type' => $goodsGapResult['match_type'] ?? 'none',
                'goods_gap_message' => $goodsGapResult['message'] ?? '',
            ];
        }

        $combination = self::findAmountCombination($orders, $targetAmount, 4);
        if (!empty($combination)) {
            return [
                'merchant_id' => $merchantId,
                'direction' => $direction,
                'target_amount' => $targetAmount,
                'match_type' => 'combination',
                'message' => '系统找到几笔订单合计刚好等于差额，建议按这组订单逐笔核对。',
                'orders' => $combination,
                'candidate_orders' => [],
                'goods_gaps' => $goodsGaps,
                'goods_gap_match_type' => $goodsGapResult['match_type'] ?? 'none',
                'goods_gap_message' => $goodsGapResult['message'] ?? '',
            ];
        }

        return [
            'merchant_id' => $merchantId,
            'direction' => $direction,
            'target_amount' => $targetAmount,
            'match_type' => 'near',
            'message' => '没有找到完全相等的订单，下面先列出最接近差额的订单，方便缩小范围。',
            'orders' => [],
            'candidate_orders' => array_slice(self::buildNearAmountCandidates($orders, $targetAmount), 0, 20),
            'goods_gaps' => $goodsGaps,
            'goods_gap_match_type' => $goodsGapResult['match_type'] ?? 'none',
            'goods_gap_message' => $goodsGapResult['message'] ?? '',
        ];
    }

    private static function buildGoodsGapDiffOrderRows(array $goodsGaps = []): array
    {
        $orderMap = [];
        foreach ($goodsGaps as $goodsGap) {
            if (!in_array($goodsGap['diff_order_match_type'] ?? '', ['unbalanced_buy', 'unbalanced_sell'], true)) {
                continue;
            }
            foreach (($goodsGap['diff_orders'] ?? []) as $order) {
                $orderNo = trim((string) ($order['order_no'] ?? ''));
                if ($orderNo === '') {
                    continue;
                }
                $orderKey = intval($order['member_order_id'] ?? 0) > 0
                    ? 'id:' . intval($order['member_order_id'] ?? 0)
                    : 'no:' . $orderNo;
                if (!isset($orderMap[$orderKey])) {
                    $orderMap[$orderKey] = $order;
                    $orderMap[$orderKey]['amount'] = 0;
                    $orderMap[$orderKey]['quantity'] = 0;
                    $orderMap[$orderKey]['goods_titles'] = [];
                }
                $orderMap[$orderKey]['amount'] = self::toFloat($orderMap[$orderKey]['amount'] + self::toFloat($order['amount'] ?? 0));
                $orderMap[$orderKey]['quantity'] += intval($order['quantity'] ?? 0);
                if (!empty($goodsGap['goods_title'])) {
                    $orderMap[$orderKey]['goods_titles'][(string) $goodsGap['goods_title']] = (string) $goodsGap['goods_title'];
                }
                $orderMap[$orderKey]['pay_time'] = max(
                    (string) ($orderMap[$orderKey]['pay_time'] ?? ''),
                    (string) ($order['pay_time'] ?? '')
                );
            }
        }

        $orders = array_values($orderMap);
        foreach ($orders as &$order) {
            $order['goods_title'] = self::joinOrderNos(array_values($order['goods_titles'] ?? []));
            unset($order['goods_titles']);
        }
        unset($order);

        usort($orders, function ($left, $right) {
            return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
        });

        return array_slice($orders, 0, 20);
    }

    private static function baseQuery(array $params = [])
    {
        $normalized = self::normalizeParams($params);
        $query = Db::name('merchant_purchase_ledger')->where('is_delete', 0);

        if ($normalized['start_time'] !== '') {
            $query->where('pay_time', '>=', $normalized['start_time']);
        }
        if ($normalized['end_time'] !== '') {
            $query->where('pay_time', '<=', $normalized['end_time']);
        }
        if ($normalized['buyer_merchant_id'] > 0) {
            $query->where('buyer_merchant_id', $normalized['buyer_merchant_id']);
        }
        if ($normalized['source_type'] !== '') {
            $query->where('source_type', $normalized['source_type']);
        }
        if ($normalized['source_merchant_id'] >= 0) {
            $query->where('source_merchant_id', $normalized['source_merchant_id']);
        }
        if ($normalized['order_no'] !== '') {
            $query->whereLike('order_no', '%' . $normalized['order_no'] . '%');
        }
        if ($normalized['keyword'] !== '') {
            $keyword = '%' . $normalized['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('order_no', $keyword)
                    ->whereOr('goods_title', 'like', $keyword)
                    ->whereOr('goods_code', 'like', $keyword)
                    ->whereOr('buyer_merchant_title', 'like', $keyword)
                    ->whereOr('source_merchant_title', 'like', $keyword);
            });
        }

        return $query;
    }

    private static function normalizeParams(array $params = []): array
    {
        $quickDate = trim((string) ($params['quick_date'] ?? ''));
        $startDate = trim((string) ($params['start_date'] ?? ''));
        $endDate = trim((string) ($params['end_date'] ?? ''));
        if ($quickDate === '') {
            $quickDate = 'all';
        }
        [$startTime, $endTime] = self::resolveDateRange($quickDate, $startDate, $endDate);

        return [
            'quick_date' => $quickDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'buyer_merchant_id' => intval($params['buyer_merchant_id'] ?? 0),
            'source_type' => trim((string) ($params['source_type'] ?? '')),
            'source_merchant_id' => isset($params['source_merchant_id']) && intval($params['source_merchant_id']) >= 0 ? intval($params['source_merchant_id']) : -1,
            'order_no' => trim((string) ($params['order_no'] ?? '')),
            'keyword' => trim((string) ($params['keyword'] ?? '')),
        ];
    }

    private static function normalizeReconciliationStatus(string $status = ''): string
    {
        $status = trim($status);
        $allowed = ['missing_bill', 'ledger_mismatch', 'bill_mismatch', 'amount_mismatch'];
        return in_array($status, $allowed, true) ? $status : '';
    }

    private static function buildFilteredReconciliationOrderIds(array $params = [], string $status = ''): array
    {
        $rows = self::appendReconciliationState(self::buildOrderReconciliationRows($params));
        $rows = self::filterRowsByReconciliationStatus($rows, $status);
        return array_values(array_unique(array_filter(array_map(function ($row) {
            return intval($row['member_order_id'] ?? 0);
        }, $rows))));
    }

    private static function filterRowsByReconciliationStatus(array $rows = [], string $status = ''): array
    {
        return array_values(array_filter($rows, function ($row) use ($status) {
            $rowStatus = $row['reconcile_status'] ?? '';
            if ($status !== '') {
                return $rowStatus === $status;
            }
            return $rowStatus !== 'normal';
        }));
    }

    private static function buildMerchantReconciliationRows(array $rows = []): array
    {
        $merchantMap = [];
        foreach ($rows as $row) {
            $merchantId = intval($row['buyer_merchant_id'] ?? 0);
            if (!isset($merchantMap[$merchantId])) {
                $merchantMap[$merchantId] = [
                    'buyer_merchant_id' => $merchantId,
                    'buyer_merchant_title' => (string) ($row['buyer_merchant_title'] ?? ''),
                    'exception_order_count' => 0,
                    'detail_count' => 0,
                    'ledger_amount' => 0,
                    'order_pay_price' => 0,
                    'bill_amount' => 0,
                    'diff_amount' => 0,
                ];
            }

            $merchantMap[$merchantId]['exception_order_count']++;
            $merchantMap[$merchantId]['detail_count'] += intval($row['detail_count'] ?? 0);
            $merchantMap[$merchantId]['ledger_amount'] += floatval($row['ledger_amount'] ?? 0);
            $merchantMap[$merchantId]['order_pay_price'] += floatval($row['order_pay_price'] ?? 0);
            $merchantMap[$merchantId]['bill_amount'] += floatval($row['bill_amount'] ?? 0);
            $merchantMap[$merchantId]['diff_amount'] += abs(floatval($row['reconcile_diff_amount'] ?? 0));
        }

        $merchantRows = array_values($merchantMap);
        foreach ($merchantRows as &$row) {
            $row['ledger_amount'] = self::toFloat($row['ledger_amount']);
            $row['order_pay_price'] = self::toFloat($row['order_pay_price']);
            $row['bill_amount'] = self::toFloat($row['bill_amount']);
            $row['diff_amount'] = self::toFloat($row['diff_amount']);
        }
        unset($row);

        usort($merchantRows, function ($left, $right) {
            if ($left['diff_amount'] === $right['diff_amount']) {
                return $right['exception_order_count'] <=> $left['exception_order_count'];
            }
            return $right['diff_amount'] <=> $left['diff_amount'];
        });

        return array_slice($merchantRows, 0, 50);
    }

    private static function buildMerchantTradeCompare(array $params = []): array
    {
        $buyRows = (clone self::baseQuery($params))
            ->where('buyer_merchant_id', '>', 0)
            ->field('buyer_merchant_id as merchant_id,buyer_merchant_title as merchant_title,SUM(total) as buy_amount,COUNT(DISTINCT member_order_id) as buy_order_count,SUM(quantity) as buy_quantity')
            ->group('buyer_merchant_id,buyer_merchant_title')
            ->select()
            ->toArray();

        $sellRows = (clone self::baseQuery($params))
            ->field("source_merchant_id as merchant_id,(CASE WHEN source_merchant_id > 0 THEN source_merchant_title ELSE '平台自营' END) as merchant_title,SUM(total) as sell_amount,COUNT(DISTINCT member_order_id) as sell_order_count,SUM(quantity) as sell_quantity")
            ->group("source_merchant_id,(CASE WHEN source_merchant_id > 0 THEN source_merchant_title ELSE '平台自营' END)")
            ->select()
            ->toArray();

        $merchantMap = [];
        foreach ($buyRows as $row) {
            $merchantId = intval($row['merchant_id'] ?? 0);
            if ($merchantId < 0) {
                continue;
            }
            $merchantMap[$merchantId] = array_merge(self::emptyTradeCompareRow($merchantId, (string) ($row['merchant_title'] ?? '')), [
                'buy_amount' => self::toFloat($row['buy_amount'] ?? 0),
                'buy_order_count' => intval($row['buy_order_count'] ?? 0),
                'buy_quantity' => intval($row['buy_quantity'] ?? 0),
            ]);
        }

        foreach ($sellRows as $row) {
            $merchantId = intval($row['merchant_id'] ?? 0);
            if ($merchantId < 0) {
                continue;
            }
            if (!isset($merchantMap[$merchantId])) {
                $merchantMap[$merchantId] = self::emptyTradeCompareRow($merchantId, (string) ($row['merchant_title'] ?? ''));
            }
            $merchantMap[$merchantId]['sell_amount'] = self::toFloat($row['sell_amount'] ?? 0);
            $merchantMap[$merchantId]['sell_order_count'] = intval($row['sell_order_count'] ?? 0);
            $merchantMap[$merchantId]['sell_quantity'] = intval($row['sell_quantity'] ?? 0);
        }

        $rows = array_values($merchantMap);
        foreach ($rows as &$row) {
            $row['net_amount'] = self::toFloat($row['buy_amount'] - $row['sell_amount']);
            $row['trade_ratio'] = $row['sell_amount'] > 0 ? round($row['buy_amount'] / $row['sell_amount'] * 100, 1) : null;
            $row['trade_judgement'] = self::buildTradeJudgement($row['buy_amount'], $row['sell_amount']);
        }
        unset($row);

        usort($rows, function ($left, $right) {
            $leftScore = max(abs(floatval($left['net_amount'] ?? 0)), floatval($left['buy_amount'] ?? 0), floatval($left['sell_amount'] ?? 0));
            $rightScore = max(abs(floatval($right['net_amount'] ?? 0)), floatval($right['buy_amount'] ?? 0), floatval($right['sell_amount'] ?? 0));
            if ($leftScore === $rightScore) {
                return intval($right['merchant_id'] ?? 0) <=> intval($left['merchant_id'] ?? 0);
            }
            return $rightScore <=> $leftScore;
        });

        return array_slice($rows, 0, 50);
    }

    private static function buildTradeDiffOrderRows(array $params = []): array
    {
        $rows = (clone self::baseQuery($params))
            ->field("member_order_id,order_no,buyer_merchant_id,buyer_merchant_title,GROUP_CONCAT(DISTINCT source_type) as source_types,GROUP_CONCAT(DISTINCT source_merchant_title) as source_titles,SUM(total) as amount,MAX(order_pay_price) as order_pay_price,MAX(pay_type) as pay_type,MAX(pay_time) as pay_time,COUNT(id) as detail_count")
            ->group('member_order_id,order_no,buyer_merchant_id,buyer_merchant_title')
            ->order('pay_time', 'desc')
            ->select()
            ->toArray();

        foreach ($rows as &$row) {
            $row['member_order_id'] = intval($row['member_order_id'] ?? 0);
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['amount'] = self::toFloat($row['amount'] ?? 0);
            $row['order_pay_price'] = self::toFloat($row['order_pay_price'] ?? 0);
            $row['detail_count'] = intval($row['detail_count'] ?? 0);
            $row['source_type_title'] = self::buildSourceTypeTitle($row['source_types'] ?? '');
            $row['source_merchant_title'] = self::buildSourceTitle($row['source_titles'] ?? '');
            $row['pay_type_title'] = self::buildPayTypeTitle(intval($row['pay_type'] ?? 0));
        }
        unset($row);

        self::attachTradeDiffOrderState($rows);
        return $rows;
    }

    private static function attachTradeDiffOrderState(array &$rows = []): void
    {
        $orderIds = array_values(array_unique(array_filter(array_map(function ($row) {
            return intval($row['member_order_id'] ?? 0);
        }, $rows))));
        if (empty($orderIds)) {
            return;
        }

        $orderMap = [];
        $orderRows = Db::name('member_order')
            ->whereIn('id', $orderIds)
            ->field('id,pay_price,total_price,pay_status,pay_type,status,pay_auth_msg')
            ->select()
            ->toArray();
        foreach ($orderRows as $row) {
            $orderMap[intval($row['id'] ?? 0)] = $row;
        }

        foreach ($rows as &$row) {
            $order = $orderMap[intval($row['member_order_id'] ?? 0)] ?? [];
            $payStatus = intval($order['pay_status'] ?? -1);
            $orderStatus = intval($order['status'] ?? -1);
            $payType = intval($order['pay_type'] ?? $row['pay_type'] ?? 0);
            $row['order_pay_price'] = self::toFloat($order['pay_price'] ?? $row['order_pay_price'] ?? 0);
            $row['pay_type'] = $payType;
            $row['pay_type_title'] = self::buildPayTypeTitle($payType);
            $row['pay_status'] = $payStatus;
            $row['pay_status_title'] = self::buildPayStatusTitle($payStatus);
            $row['order_status'] = $orderStatus;
            $row['order_status_title'] = self::buildOrderStatusTitle($orderStatus);
            $row['pay_auth_msg'] = (string) ($order['pay_auth_msg'] ?? '');
        }
        unset($row);
    }

    private static function findAmountCombination(array $orders = [], float $targetAmount = 0, int $maxDepth = 4): array
    {
        $targetCents = self::moneyToCents($targetAmount);
        $candidates = array_values(array_filter($orders, function ($order) use ($targetCents) {
            $amountCents = self::moneyToCents($order['amount'] ?? 0);
            return $amountCents > 0 && $amountCents < $targetCents;
        }));

        usort($candidates, function ($left, $right) use ($targetAmount) {
            $leftDiff = abs(floatval($left['amount'] ?? 0) - $targetAmount);
            $rightDiff = abs(floatval($right['amount'] ?? 0) - $targetAmount);
            if ($leftDiff === $rightDiff) {
                return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
            }
            return $leftDiff <=> $rightDiff;
        });
        $candidates = array_slice($candidates, 0, 80);

        for ($depth = 2; $depth <= $maxDepth; $depth++) {
            $match = self::searchAmountCombination($candidates, $targetCents, $depth);
            if (!empty($match)) {
                return $match;
            }
        }

        return [];
    }

    private static function searchAmountCombination(array $orders, int $targetCents, int $depth, int $start = 0, array $picked = [], int $pickedCents = 0): array
    {
        if (count($picked) === $depth) {
            return $pickedCents === $targetCents ? $picked : [];
        }

        $remainingSlots = $depth - count($picked);
        $count = count($orders);
        for ($index = $start; $index <= $count - $remainingSlots; $index++) {
            $amountCents = self::moneyToCents($orders[$index]['amount'] ?? 0);
            $nextCents = $pickedCents + $amountCents;
            if ($nextCents > $targetCents) {
                continue;
            }
            $nextPicked = $picked;
            $nextPicked[] = $orders[$index];
            $match = self::searchAmountCombination($orders, $targetCents, $depth, $index + 1, $nextPicked, $nextCents);
            if (!empty($match)) {
                return $match;
            }
        }

        return [];
    }

    private static function buildNearAmountCandidates(array $orders = [], float $targetAmount = 0): array
    {
        foreach ($orders as &$order) {
            $order['diff_to_target'] = self::toFloat(abs(floatval($order['amount'] ?? 0) - $targetAmount));
        }
        unset($order);

        usort($orders, function ($left, $right) {
            if ($left['diff_to_target'] === $right['diff_to_target']) {
                return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
            }
            return $left['diff_to_target'] <=> $right['diff_to_target'];
        });

        return $orders;
    }

    private static function buildTradeDiffGoodsGaps(array $params = [], int $merchantId = 0, string $direction = 'buy', float $targetAmount = 0): array
    {
        $buyMap = [];
        if ($merchantId > 0) {
            $buyParams = $params;
            unset($buyParams['merchant_id'], $buyParams['direction'], $buyParams['target_amount']);
            $buyParams['buyer_merchant_id'] = $merchantId;
            $buyParams['source_merchant_id'] = -1;
            $buyMap = self::buildTradeSideGoodsRows($buyParams, 'buy');
        }

        $sellParams = $params;
        unset($sellParams['merchant_id'], $sellParams['direction'], $sellParams['target_amount']);
        $sellParams['buyer_merchant_id'] = 0;
        $sellParams['source_merchant_id'] = $merchantId;
        if ($merchantId === 0) {
            $sellParams['source_type'] = 'platform';
        }

        $sellMap = self::buildTradeSideGoodsRows($sellParams, 'sell');
        $currentGoodsMap = self::buildCurrentMerchantGoodsMap($merchantId);
        $goodsKeys = array_values(array_unique(array_merge(array_keys($buyMap), array_keys($sellMap), array_keys($currentGoodsMap))));
        $rows = [];

        foreach ($goodsKeys as $goodsKey) {
            $buy = $buyMap[$goodsKey] ?? self::emptyGoodsSideRow($goodsKey);
            $sell = $sellMap[$goodsKey] ?? self::emptyGoodsSideRow($goodsKey);
            $current = $currentGoodsMap[$goodsKey] ?? self::emptyCurrentGoodsRow($goodsKey);
            $diffAmount = $direction === 'sell'
                ? self::toFloat($sell['amount'] - $buy['amount'])
                : self::toFloat($buy['amount'] - $sell['amount']);
            $diffQuantity = $direction === 'sell'
                ? intval($sell['quantity'] - $buy['quantity'])
                : intval($buy['quantity'] - $sell['quantity']);
            if ($diffAmount <= 0.01 && $diffQuantity <= 0) {
                continue;
            }

            $identity = self::mergeGoodsIdentity($buy, $sell, $current);
            $diffOrderResult = self::buildTradeBalanceDiffOrders(
                $buy['order_rows'] ?? [],
                $sell['order_rows'] ?? [],
                $direction,
                $diffAmount
            );
            $flowRows = self::buildGoodsTradeFlowRows($buy['order_rows'] ?? [], $sell['order_rows'] ?? []);
            $balancePairRows = self::buildTradeBalancePairRows($buy['order_rows'] ?? [], $sell['order_rows'] ?? []);
            $missingSellLedgerRows = self::buildMissingSellLedgerRows($params, $merchantId, $identity, $current);
            $rows[] = [
                'goods_key' => $goodsKey,
                'goods_id' => intval($identity['goods_id'] ?? 0),
                'goods_ids' => $identity['goods_ids'] ?? '',
                'goods_title' => $identity['goods_title'] ?? '',
                'goods_code' => $identity['goods_code'] ?? '',
                'goods_spec' => $identity['goods_spec'] ?? '',
                'goods_unit' => $identity['goods_unit'] ?? '',
                'buy_amount' => self::toFloat($buy['amount']),
                'sell_amount' => self::toFloat($sell['amount']),
                'diff_amount' => $diffAmount,
                'buy_quantity' => intval($buy['quantity']),
                'sell_quantity' => intval($sell['quantity']),
                'diff_quantity' => $diffQuantity,
                'buy_avg_price' => intval($buy['quantity']) > 0 ? self::toFloat($buy['amount'] / intval($buy['quantity'])) : 0,
                'sell_avg_price' => intval($sell['quantity']) > 0 ? self::toFloat($sell['amount'] / intval($sell['quantity'])) : 0,
                'buy_order_count' => intval($buy['order_count']),
                'sell_order_count' => intval($sell['order_count']),
                'buy_order_nos' => self::joinOrderNos($buy['order_nos'] ?? []),
                'sell_order_nos' => self::joinOrderNos($sell['order_nos'] ?? []),
                'diff_order_match_type' => $diffOrderResult['match_type'],
                'diff_order_message' => $diffOrderResult['message'],
                'diff_order_nos' => $diffOrderResult['order_nos'],
                'diff_orders' => $diffOrderResult['orders'],
                'flow_message' => self::buildGoodsTradeFlowMessage($flowRows),
                'flow_rows' => $flowRows,
                'balance_pair_message' => self::buildTradeBalancePairMessage($balancePairRows),
                'balance_pair_rows' => $balancePairRows,
                'missing_sell_ledger_message' => self::buildMissingSellLedgerMessage($missingSellLedgerRows),
                'missing_sell_ledger_orders' => $missingSellLedgerRows,
                'order_nos' => self::joinOrderNos($direction === 'sell' ? ($sell['order_nos'] ?? []) : ($buy['order_nos'] ?? [])),
                'pay_time' => max((string) ($buy['pay_time'] ?? ''), (string) ($sell['pay_time'] ?? '')),
                'current_goods_count' => intval($current['goods_count'] ?? 0),
                'current_stock' => intval($current['stock'] ?? 0),
                'current_sales_sum' => intval($current['sales_sum'] ?? 0),
                'current_goods_ids' => self::joinOrderNos($current['goods_ids'] ?? []),
                'goods_status_title' => $current['status_title'] ?? '未知',
                'goods_disable_title' => $current['disable_title'] ?? '未知',
            ];
        }

        usort($rows, function ($left, $right) {
            if ($left['diff_amount'] === $right['diff_amount']) {
                return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
            }
            return $right['diff_amount'] <=> $left['diff_amount'];
        });

        if ($targetAmount > 0) {
            $matchedRows = self::findGoodsGapCombination($rows, $targetAmount, 10);
            if (!empty($matchedRows)) {
                return [
                    'rows' => $matchedRows,
                    'match_type' => count($matchedRows) === 1 ? 'single' : 'combination',
                    'message' => count($matchedRows) === 1
                        ? '按采购流水找到单个商品差额项，优先核对这类商品的买入/卖出订单；库存和商品销量仅作参考。'
                        : '按采购流水找到几类商品合计等于差额，优先核对这些商品的买入/卖出订单；库存和商品销量仅作参考。',
                ];
            }

            return [
                'rows' => array_slice(self::buildNearGoodsGapCandidates($rows, $targetAmount), 0, 20),
                'match_type' => empty($rows) ? 'none' : 'near',
                'message' => empty($rows)
                    ? '当前筛选范围内没有找到商品维度差额。'
                    : '没有找到刚好合计等于差额的商品，下面列出最接近差额的采购流水商品缺口；库存和商品销量仅作参考。',
            ];
        }

        return [
            'rows' => array_slice($rows, 0, 20),
            'match_type' => empty($rows) ? 'none' : 'list',
            'message' => empty($rows) ? '当前筛选范围内没有找到商品维度差额。' : '已按采购流水商品维度列出主要差额；库存和商品销量仅作参考。',
        ];
    }

    private static function buildTradeSideGoodsRows(array $params = [], string $side = 'buy'): array
    {
        $rows = (clone self::baseQuery($params))
            ->field('member_order_id,order_no,buyer_merchant_id,buyer_merchant_title,source_type,source_merchant_id,source_merchant_title,goods_id,goods_title,goods_code,goods_spec,goods_unit,quantity,price,total,pay_type,pay_time')
            ->order('pay_time', 'desc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $goodsKey = self::buildGoodsTradeKey($row);
            if (!isset($map[$goodsKey])) {
                $map[$goodsKey] = self::emptyGoodsSideRow($goodsKey);
            }

            self::fillGoodsIdentity($map[$goodsKey], $row);
            $map[$goodsKey]['amount'] = self::toFloat($map[$goodsKey]['amount'] + self::toFloat($row['total'] ?? 0));
            $map[$goodsKey]['quantity'] += intval($row['quantity'] ?? 0);
            $map[$goodsKey]['pay_time'] = max((string) ($map[$goodsKey]['pay_time'] ?? ''), (string) ($row['pay_time'] ?? ''));
            $map[$goodsKey]['side'] = $side;
            $map[$goodsKey]['goods_ids'][intval($row['goods_id'] ?? 0)] = intval($row['goods_id'] ?? 0);
            $orderId = intval($row['member_order_id'] ?? 0);
            if ($orderId > 0) {
                $map[$goodsKey]['order_ids'][$orderId] = $orderId;
            }
            $orderNo = trim((string) ($row['order_no'] ?? ''));
            if ($orderNo !== '') {
                $map[$goodsKey]['order_nos'][$orderNo] = $orderNo;
            }
            $orderKey = $orderId > 0 ? (string) $orderId : $orderNo;
            if ($orderKey !== '') {
                if (!isset($map[$goodsKey]['order_rows'][$orderKey])) {
                    $map[$goodsKey]['order_rows'][$orderKey] = [
                        'member_order_id' => $orderId,
                        'order_no' => $orderNo,
                        'buyer_merchant_id' => intval($row['buyer_merchant_id'] ?? 0),
                        'buyer_merchant_title' => (string) ($row['buyer_merchant_title'] ?? ''),
                        'source_type' => (string) ($row['source_type'] ?? ''),
                        'source_types' => [],
                        'source_merchant_id' => intval($row['source_merchant_id'] ?? 0),
                        'source_merchant_title' => (string) ($row['source_merchant_title'] ?? ''),
                        'source_titles' => [],
                        'pay_type' => intval($row['pay_type'] ?? 0),
                        'amount' => 0,
                        'quantity' => 0,
                        'pay_time' => (string) ($row['pay_time'] ?? ''),
                    ];
                }
                if (!empty($row['source_type'])) {
                    $map[$goodsKey]['order_rows'][$orderKey]['source_types'][(string) $row['source_type']] = (string) $row['source_type'];
                }
                if (!empty($row['source_merchant_title'])) {
                    $map[$goodsKey]['order_rows'][$orderKey]['source_titles'][(string) $row['source_merchant_title']] = (string) $row['source_merchant_title'];
                }
                $map[$goodsKey]['order_rows'][$orderKey]['amount'] = self::toFloat(
                    $map[$goodsKey]['order_rows'][$orderKey]['amount'] + self::toFloat($row['total'] ?? 0)
                );
                $map[$goodsKey]['order_rows'][$orderKey]['quantity'] += intval($row['quantity'] ?? 0);
                $map[$goodsKey]['order_rows'][$orderKey]['pay_time'] = max(
                    (string) ($map[$goodsKey]['order_rows'][$orderKey]['pay_time'] ?? ''),
                    (string) ($row['pay_time'] ?? '')
                );
            }
        }

        foreach ($map as &$item) {
            $item['order_count'] = count($item['order_ids']);
            $item['order_nos'] = array_slice(array_values($item['order_nos']), 0, 12);
            $item['goods_ids'] = array_values(array_filter(array_map('intval', $item['goods_ids'])));
            $item['order_rows'] = self::normalizeSideOrderRows($item['order_rows'] ?? []);
            unset($item['order_ids']);
        }
        unset($item);

        return $map;
    }

    private static function buildCurrentMerchantGoodsMap(int $merchantId = 0): array
    {
        $query = Db::name('goods')->where('is_delete', 0);
        if ($merchantId > 0) {
            $query->where('merchant_id', $merchantId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('merchant_id')->whereOr('merchant_id', 0);
            });
        }

        $rows = $query
            ->field('id,title,code,spec,unit,price,status,is_disable,stock,sales_sum')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $goodsKey = self::buildGoodsTradeKey($row);
            if (!isset($map[$goodsKey])) {
                $map[$goodsKey] = self::emptyCurrentGoodsRow($goodsKey);
            }
            self::fillGoodsIdentity($map[$goodsKey], $row);
            $map[$goodsKey]['goods_count']++;
            $map[$goodsKey]['stock'] += intval($row['stock'] ?? 0);
            $map[$goodsKey]['sales_sum'] += intval($row['sales_sum'] ?? 0);
            $map[$goodsKey]['goods_ids'][intval($row['id'] ?? 0)] = intval($row['id'] ?? 0);
            $map[$goodsKey]['statuses'][intval($row['status'] ?? -1)] = intval($row['status'] ?? -1);
            if (intval($row['is_disable'] ?? 0) === 1) {
                $map[$goodsKey]['disabled_count']++;
            }
        }

        foreach ($map as &$item) {
            $item['goods_ids'] = array_values(array_filter(array_map('intval', $item['goods_ids'])));
            $statuses = array_values(array_filter($item['statuses'], function ($status) {
                return intval($status) >= 0;
            }));
            $item['status_title'] = count($statuses) === 1
                ? (GoodsModel::getStatus(intval($statuses[0]), 2) ?: '未知')
                : (count($statuses) > 1 ? '多状态' : '未知');
            if ($item['goods_count'] <= 0) {
                $item['disable_title'] = '未知';
            } elseif ($item['disabled_count'] <= 0) {
                $item['disable_title'] = '正常';
            } elseif ($item['disabled_count'] >= $item['goods_count']) {
                $item['disable_title'] = '已禁用/下架';
            } else {
                $item['disable_title'] = '部分下架';
            }
            unset($item['statuses']);
        }
        unset($item);

        return $map;
    }

    private static function buildGoodsTradeKey(array $row = []): string
    {
        $code = self::normalizeGoodsKeyPart($row['goods_code'] ?? $row['code'] ?? '');
        $title = self::normalizeGoodsKeyPart($row['goods_title'] ?? $row['title'] ?? '');
        $spec = self::normalizeGoodsKeyPart($row['goods_spec'] ?? $row['spec'] ?? '');
        $unit = self::normalizeGoodsKeyPart($row['goods_unit'] ?? $row['unit'] ?? '');

        if ($code !== '') {
            return 'code:' . $code . '|spec:' . $spec . '|unit:' . $unit;
        }
        if ($title !== '' || $spec !== '' || $unit !== '') {
            return 'title:' . $title . '|spec:' . $spec . '|unit:' . $unit;
        }
        return 'goods:' . intval($row['goods_id'] ?? $row['id'] ?? 0);
    }

    private static function normalizeGoodsKeyPart($value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));
        return strtolower((string) $value);
    }

    private static function fillGoodsIdentity(array &$target, array $row = []): void
    {
        $target['goods_id'] = intval($target['goods_id'] ?: ($row['goods_id'] ?? $row['id'] ?? 0));
        foreach ([
            'goods_title' => ['goods_title', 'title'],
            'goods_code' => ['goods_code', 'code'],
            'goods_spec' => ['goods_spec', 'spec'],
            'goods_unit' => ['goods_unit', 'unit'],
        ] as $targetKey => $sourceKeys) {
            if ($target[$targetKey] !== '') {
                continue;
            }
            foreach ($sourceKeys as $sourceKey) {
                if (!empty($row[$sourceKey])) {
                    $target[$targetKey] = (string) $row[$sourceKey];
                    break;
                }
            }
        }
    }

    private static function mergeGoodsIdentity(array $buy = [], array $sell = [], array $current = []): array
    {
        $identity = self::emptyGoodsSideRow((string) ($buy['goods_key'] ?? $sell['goods_key'] ?? $current['goods_key'] ?? ''));
        foreach ([$current, $sell, $buy] as $row) {
            self::fillGoodsIdentity($identity, $row);
            foreach (($row['goods_ids'] ?? []) as $goodsId) {
                $identity['goods_ids'][intval($goodsId)] = intval($goodsId);
            }
        }
        $identity['goods_ids'] = self::joinOrderNos(array_values(array_filter(array_map('intval', $identity['goods_ids']))));
        return $identity;
    }

    private static function emptyGoodsSideRow(string $goodsKey = ''): array
    {
        return [
            'goods_key' => $goodsKey,
            'goods_id' => 0,
            'goods_ids' => [],
            'goods_title' => '',
            'goods_code' => '',
            'goods_spec' => '',
            'goods_unit' => '',
            'amount' => 0,
            'quantity' => 0,
            'order_count' => 0,
            'order_ids' => [],
            'order_nos' => [],
            'order_rows' => [],
            'pay_time' => '',
        ];
    }

    private static function emptyCurrentGoodsRow(string $goodsKey = ''): array
    {
        return [
            'goods_key' => $goodsKey,
            'goods_id' => 0,
            'goods_ids' => [],
            'goods_title' => '',
            'goods_code' => '',
            'goods_spec' => '',
            'goods_unit' => '',
            'goods_count' => 0,
            'stock' => 0,
            'sales_sum' => 0,
            'statuses' => [],
            'disabled_count' => 0,
            'status_title' => '未知',
            'disable_title' => '未知',
        ];
    }

    private static function findGoodsGapCombination(array $rows = [], float $targetAmount = 0, int $maxItems = 8): array
    {
        $targetCents = self::moneyToCents($targetAmount);
        if ($targetCents <= 0) {
            return [];
        }

        $candidates = array_values(array_filter($rows, function ($row) use ($targetCents) {
            $amountCents = self::moneyToCents($row['diff_amount'] ?? 0);
            return $amountCents > 0 && $amountCents <= $targetCents;
        }));
        usort($candidates, function ($left, $right) use ($targetAmount) {
            $leftDiff = abs(floatval($left['diff_amount'] ?? 0) - $targetAmount);
            $rightDiff = abs(floatval($right['diff_amount'] ?? 0) - $targetAmount);
            return $leftDiff === $rightDiff ? 0 : ($leftDiff <=> $rightDiff);
        });

        $sums = [0 => []];
        foreach ($candidates as $row) {
            $amountCents = self::moneyToCents($row['diff_amount'] ?? 0);
            $snapshot = $sums;
            foreach ($snapshot as $sumCents => $pickedRows) {
                if (count($pickedRows) >= $maxItems) {
                    continue;
                }
                $nextCents = intval($sumCents) + $amountCents;
                if ($nextCents > $targetCents || isset($sums[$nextCents])) {
                    continue;
                }
                $nextRows = $pickedRows;
                $nextRows[] = $row;
                if ($nextCents === $targetCents) {
                    return $nextRows;
                }
                $sums[$nextCents] = $nextRows;
            }
        }

        return [];
    }

    private static function normalizeSideOrderRows(array $orders = []): array
    {
        $orders = array_values(array_filter($orders, function ($order) {
            return trim((string) ($order['order_no'] ?? '')) !== '' && self::moneyToCents($order['amount'] ?? 0) > 0;
        }));
        foreach ($orders as &$order) {
            $order['member_order_id'] = intval($order['member_order_id'] ?? 0);
            $order['buyer_merchant_id'] = intval($order['buyer_merchant_id'] ?? 0);
            $order['source_merchant_id'] = intval($order['source_merchant_id'] ?? 0);
            $order['pay_type'] = intval($order['pay_type'] ?? 0);
            $order['amount'] = self::toFloat($order['amount'] ?? 0);
            $order['quantity'] = intval($order['quantity'] ?? 0);
            $order['pay_time'] = (string) ($order['pay_time'] ?? '');
            $sourceTypes = array_values(array_filter($order['source_types'] ?? []));
            $sourceTitles = array_values(array_filter($order['source_titles'] ?? []));
            $order['source_type_title'] = self::buildSourceTypeTitle(implode(',', $sourceTypes));
            $order['source_merchant_title'] = self::buildSourceTitle(implode(',', $sourceTitles));
            $order['pay_type_title'] = self::buildPayTypeTitle($order['pay_type']);
            unset($order['source_types'], $order['source_titles']);
        }
        unset($order);

        usort($orders, function ($left, $right) {
            return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
        });

        return $orders;
    }

    private static function buildGoodsTradeFlowRows(array $buyOrders = [], array $sellOrders = []): array
    {
        $rows = [];
        foreach ($buyOrders as $order) {
            $rows[] = self::buildGoodsTradeFlowEvent($order, 'buy');
        }
        foreach ($sellOrders as $order) {
            $rows[] = self::buildGoodsTradeFlowEvent($order, 'sell');
        }

        usort($rows, function ($left, $right) {
            $timeCompare = strcmp((string) ($left['pay_time'] ?? ''), (string) ($right['pay_time'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            return intval($left['member_order_id'] ?? 0) <=> intval($right['member_order_id'] ?? 0);
        });

        $balanceQuantity = 0;
        $balanceAmount = 0;
        foreach ($rows as $index => &$row) {
            $balanceQuantity += intval($row['quantity_delta'] ?? 0);
            $balanceAmount = self::toFloat($balanceAmount + self::toFloat($row['amount_delta'] ?? 0));
            $row['step_no'] = $index + 1;
            $row['balance_quantity'] = $balanceQuantity;
            $row['balance_amount'] = $balanceAmount;
            $row['balance_title'] = self::buildFlowBalanceTitle($balanceQuantity, $balanceAmount);
        }
        unset($row);

        self::attachTradeDiffOrderState($rows);
        return $rows;
    }

    private static function buildGoodsTradeFlowEvent(array $order = [], string $side = 'buy'): array
    {
        $sourceTitle = trim((string) ($order['source_merchant_title'] ?? ''));
        if ($sourceTitle === '') {
            $sourceTitle = ($order['source_type_title'] ?? '') === '平台商品' ? '平台自营' : '未知来源';
        }
        $buyerTitle = trim((string) ($order['buyer_merchant_title'] ?? ''));
        if ($buyerTitle === '') {
            $buyerTitle = '未知买方';
        }
        $quantity = intval($order['quantity'] ?? 0);
        $amount = self::toFloat($order['amount'] ?? 0);
        $isSell = $side === 'sell';

        return [
            'member_order_id' => intval($order['member_order_id'] ?? 0),
            'order_no' => trim((string) ($order['order_no'] ?? '')),
            'pay_time' => (string) ($order['pay_time'] ?? ''),
            'side' => $isSell ? 'sell' : 'buy',
            'side_title' => $isSell ? '卖出' : '买入',
            'buyer_merchant_id' => intval($order['buyer_merchant_id'] ?? 0),
            'buyer_merchant_title' => $buyerTitle,
            'source_merchant_id' => intval($order['source_merchant_id'] ?? 0),
            'source_merchant_title' => $sourceTitle,
            'source_type_title' => (string) ($order['source_type_title'] ?? ''),
            'pay_type' => intval($order['pay_type'] ?? 0),
            'pay_type_title' => (string) ($order['pay_type_title'] ?? ''),
            'quantity' => $quantity,
            'amount' => $amount,
            'quantity_delta' => $isSell ? -$quantity : $quantity,
            'amount_delta' => $isSell ? -$amount : $amount,
            'flow_direction_title' => $sourceTitle . ' -> ' . $buyerTitle,
        ];
    }

    private static function buildFlowBalanceTitle(int $quantity = 0, float $amount = 0): string
    {
        if ($quantity > 0 || $amount > 0.01) {
            return '买入结余 ' . $quantity . ' 件 / ¥' . number_format(abs($amount), 2, '.', '');
        }
        if ($quantity < 0 || $amount < -0.01) {
            return '卖出超出 ' . abs($quantity) . ' 件 / ¥' . number_format(abs($amount), 2, '.', '');
        }
        return '已配平';
    }

    private static function buildGoodsTradeFlowMessage(array $flowRows = []): string
    {
        if (empty($flowRows)) {
            return '当前商品没有可展示的流转流水。';
        }

        $last = $flowRows[count($flowRows) - 1];
        $quantity = intval($last['balance_quantity'] ?? 0);
        $amount = self::toFloat($last['balance_amount'] ?? 0);
        if ($quantity === 0 && abs($amount) <= 0.01) {
            return '从第一笔到最新一笔，该商品买入和卖出已经配平。';
        }
        if ($quantity > 0 || $amount > 0.01) {
            return '从第一笔到最新一笔，该商品仍有买入结余 ' . $quantity . ' 件 / ¥' . number_format(abs($amount), 2, '.', '') . '。';
        }
        return '从第一笔到最新一笔，该商品卖出超出 ' . abs($quantity) . ' 件 / ¥' . number_format(abs($amount), 2, '.', '') . '。';
    }

    private static function buildTradeBalancePairRows(array $buyOrders = [], array $sellOrders = []): array
    {
        $events = array_merge(
            self::prepareTradeBalanceOrders($buyOrders, 'buy'),
            self::prepareTradeBalanceOrders($sellOrders, 'sell')
        );
        usort($events, function ($left, $right) {
            $timeCompare = strcmp((string) ($left['pay_time'] ?? ''), (string) ($right['pay_time'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            if (($left['side'] ?? '') !== ($right['side'] ?? '')) {
                return ($left['side'] ?? '') === 'buy' ? -1 : 1;
            }
            return intval($left['member_order_id'] ?? 0) <=> intval($right['member_order_id'] ?? 0);
        });

        $buyQueue = [];
        $rows = [];
        foreach ($events as $event) {
            if (($event['side'] ?? '') === 'buy') {
                $buyQueue[] = $event;
                continue;
            }

            while (intval($event['remaining_quantity'] ?? 0) > 0 && !empty($buyQueue)) {
                $buyOrder = &$buyQueue[0];
                $quantity = min(
                    intval($buyOrder['remaining_quantity'] ?? 0),
                    intval($event['remaining_quantity'] ?? 0)
                );
                $buyAmountCents = self::calculateTradeBalanceConsumeCents($buyOrder, $quantity);
                $sellAmountCents = self::calculateTradeBalanceConsumeCents($event, $quantity);
                $rows[] = self::buildTradeBalancePairRow(
                    $buyOrder,
                    $event,
                    $quantity,
                    $buyAmountCents,
                    $sellAmountCents
                );
                self::consumeTradeBalanceOrder($buyOrder, $quantity);
                self::consumeTradeBalanceOrder($event, $quantity);
                $buyConsumed = intval($buyOrder['remaining_quantity'] ?? 0) <= 0;
                unset($buyOrder);
                if ($buyConsumed) {
                    array_shift($buyQueue);
                }
            }

            if (intval($event['remaining_quantity'] ?? 0) > 0) {
                $rows[] = self::buildTradeBalanceUnmatchedRow($event, 'sell');
            }
        }

        foreach ($buyQueue as $buyOrder) {
            if (intval($buyOrder['remaining_quantity'] ?? 0) > 0) {
                $rows[] = self::buildTradeBalanceUnmatchedRow($buyOrder, 'buy');
            }
        }

        foreach ($rows as $index => &$row) {
            $row['pair_no'] = $index + 1;
        }
        unset($row);

        return array_slice($rows, 0, 80);
    }

    private static function buildTradeBalancePairRow(array $buyOrder = [], array $sellOrder = [], int $quantity = 0, int $buyAmountCents = 0, int $sellAmountCents = 0): array
    {
        return [
            'match_status' => 'matched',
            'match_status_title' => '已配平',
            'diagnosis_message' => '这笔买入已被右侧卖出订单抵扣。',
            'buy_member_order_id' => intval($buyOrder['member_order_id'] ?? 0),
            'buy_order_no' => (string) ($buyOrder['order_no'] ?? ''),
            'buy_time' => (string) ($buyOrder['pay_time'] ?? ''),
            'buy_source_title' => (string) ($buyOrder['source_merchant_title'] ?? ''),
            'buy_source_type_title' => (string) ($buyOrder['source_type_title'] ?? ''),
            'buy_amount' => self::toFloat($buyAmountCents / 100),
            'sell_member_order_id' => intval($sellOrder['member_order_id'] ?? 0),
            'sell_order_no' => (string) ($sellOrder['order_no'] ?? ''),
            'sell_time' => (string) ($sellOrder['pay_time'] ?? ''),
            'sell_buyer_title' => (string) ($sellOrder['buyer_merchant_title'] ?? ''),
            'sell_source_title' => (string) ($sellOrder['source_merchant_title'] ?? ''),
            'sell_amount' => self::toFloat($sellAmountCents / 100),
            'matched_quantity' => $quantity,
            'matched_amount' => self::toFloat($sellAmountCents / 100),
        ];
    }

    private static function buildTradeBalanceUnmatchedRow(array $order = [], string $side = 'buy'): array
    {
        $isSell = $side === 'sell';
        return [
            'match_status' => $isSell ? 'unmatched_sell' : 'unmatched_buy',
            'match_status_title' => $isSell ? '卖出未配平' : '买入未配平',
            'diagnosis_message' => $isSell
                ? '这笔卖出没有找到可抵扣的买入流水，优先核对是否超卖或来源未登记。'
                : '这笔买入在当前筛选范围内没有找到后续卖出流水，优先核对是否仍未卖出、未展示，或卖出订单未写入流水。',
            'buy_member_order_id' => $isSell ? 0 : intval($order['member_order_id'] ?? 0),
            'buy_order_no' => $isSell ? '' : (string) ($order['order_no'] ?? ''),
            'buy_time' => $isSell ? '' : (string) ($order['pay_time'] ?? ''),
            'buy_source_title' => $isSell ? '' : (string) ($order['source_merchant_title'] ?? ''),
            'buy_source_type_title' => $isSell ? '' : (string) ($order['source_type_title'] ?? ''),
            'buy_amount' => $isSell ? 0 : self::toFloat($order['remaining_amount'] ?? $order['amount'] ?? 0),
            'sell_member_order_id' => $isSell ? intval($order['member_order_id'] ?? 0) : 0,
            'sell_order_no' => $isSell ? (string) ($order['order_no'] ?? '') : '',
            'sell_time' => $isSell ? (string) ($order['pay_time'] ?? '') : '',
            'sell_buyer_title' => $isSell ? (string) ($order['buyer_merchant_title'] ?? '') : '',
            'sell_source_title' => $isSell ? (string) ($order['source_merchant_title'] ?? '') : '',
            'sell_amount' => $isSell ? self::toFloat($order['remaining_amount'] ?? $order['amount'] ?? 0) : 0,
            'matched_quantity' => intval($order['remaining_quantity'] ?? $order['quantity'] ?? 0),
            'matched_amount' => self::toFloat($order['remaining_amount'] ?? $order['amount'] ?? 0),
        ];
    }

    private static function buildTradeBalancePairMessage(array $rows = []): string
    {
        if (empty($rows)) {
            return '当前商品没有可配对的买入/卖出流水。';
        }

        $matchedCount = 0;
        $unmatchedBuyAmount = 0;
        $unmatchedSellAmount = 0;
        foreach ($rows as $row) {
            $status = (string) ($row['match_status'] ?? '');
            if ($status === 'matched') {
                $matchedCount++;
            } elseif ($status === 'unmatched_buy') {
                $unmatchedBuyAmount = self::toFloat($unmatchedBuyAmount + self::toFloat($row['matched_amount'] ?? 0));
            } elseif ($status === 'unmatched_sell') {
                $unmatchedSellAmount = self::toFloat($unmatchedSellAmount + self::toFloat($row['matched_amount'] ?? 0));
            }
        }

        if ($unmatchedBuyAmount > 0.01) {
            return '已配平 ' . $matchedCount . ' 笔，剩余未配平买入 ¥' . number_format($unmatchedBuyAmount, 2, '.', '') . '。';
        }
        if ($unmatchedSellAmount > 0.01) {
            return '已配平 ' . $matchedCount . ' 笔，剩余未配平卖出 ¥' . number_format($unmatchedSellAmount, 2, '.', '') . '。';
        }
        return '已配平 ' . $matchedCount . ' 笔，没有剩余差额。';
    }

    private static function buildMissingSellLedgerRows(array $params = [], int $merchantId = 0, array $identity = [], array $current = []): array
    {
        if ($merchantId <= 0) {
            return [];
        }

        $goodsIds = self::normalizeIdList($current['goods_ids'] ?? $identity['goods_ids'] ?? []);
        $normalized = self::normalizeParams($params);
        $query = Db::name('member_order_detailed')
            ->alias('d')
            ->leftJoin('member_order o', 'o.id = d.member_order_id')
            ->leftJoin('goods g', 'g.id = d.goods_id')
            ->leftJoin('merchant bm', 'bm.member_id = o.member_id and bm.is_delete = 0')
            ->leftJoin('merchant_purchase_ledger l', 'l.member_order_detailed_id = d.id and l.is_delete = 0')
            ->where('o.is_delete', 0)
            ->where('o.pay_status', 1)
            ->where('g.is_delete', 0)
            ->where('g.merchant_id', $merchantId)
            ->whereNull('l.id');

        if (!empty($goodsIds)) {
            $query->whereIn('d.goods_id', $goodsIds);
        } else {
            self::applyGoodsIdentityFilter($query, $identity);
        }
        if ($normalized['start_time'] !== '') {
            $query->where('o.pay_time', '>=', $normalized['start_time']);
        }
        if ($normalized['end_time'] !== '') {
            $query->where('o.pay_time', '<=', $normalized['end_time']);
        }
        if ($normalized['order_no'] !== '') {
            $query->whereLike('o.order_no', '%' . $normalized['order_no'] . '%');
        }

        $rows = $query
            ->field('o.id as member_order_id,o.order_no,o.member_id,o.pay_time,o.pay_type,o.pay_status,o.status as order_status,bm.id as buyer_merchant_id,bm.title as buyer_merchant_title,d.id as member_order_detailed_id,d.goods_id,d.quantity,d.price,d.total,g.title as goods_title,g.code as goods_code,g.spec as goods_spec,g.unit as goods_unit')
            ->order('o.pay_time', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        foreach ($rows as &$row) {
            $row['member_order_id'] = intval($row['member_order_id'] ?? 0);
            $row['member_order_detailed_id'] = intval($row['member_order_detailed_id'] ?? 0);
            $row['goods_id'] = intval($row['goods_id'] ?? 0);
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['buyer_merchant_title'] = trim((string) ($row['buyer_merchant_title'] ?? '')) ?: '普通用户/未知买方';
            $row['source_merchant_id'] = $merchantId;
            $row['source_merchant_title'] = (string) ($identity['source_merchant_title'] ?? '');
            $row['source_type_title'] = '商家商品';
            $row['quantity'] = intval($row['quantity'] ?? 0);
            $row['price'] = self::toFloat($row['price'] ?? 0);
            $row['total'] = self::toFloat($row['total'] ?? 0);
            $row['amount'] = $row['total'];
            $row['pay_type'] = intval($row['pay_type'] ?? 0);
            $row['pay_type_title'] = self::buildPayTypeTitle($row['pay_type']);
            $row['pay_status'] = intval($row['pay_status'] ?? -1);
            $row['pay_status_title'] = self::buildPayStatusTitle($row['pay_status']);
            $row['order_status'] = intval($row['order_status'] ?? -1);
            $row['order_status_title'] = self::buildOrderStatusTitle($row['order_status']);
            $row['diagnosis_title'] = '已支付订单未写入采购流水';
            $row['diagnosis_message'] = '订单明细已卖出该商品，但采购流水表没有对应明细记录，优先核对是否需要补生成流水。';
        }
        unset($row);

        return $rows;
    }

    private static function applyGoodsIdentityFilter($query, array $identity = []): void
    {
        $code = trim((string) ($identity['goods_code'] ?? ''));
        $title = trim((string) ($identity['goods_title'] ?? ''));
        $spec = trim((string) ($identity['goods_spec'] ?? ''));
        $unit = trim((string) ($identity['goods_unit'] ?? ''));

        if ($code !== '') {
            $query->where('g.code', $code);
        } elseif ($title !== '') {
            $query->where('g.title', $title);
        }
        if ($spec !== '') {
            $query->where('g.spec', $spec);
        }
        if ($unit !== '') {
            $query->where('g.unit', $unit);
        }
    }

    private static function normalizeIdList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[、,，\s]+/u', $value) ?: [];
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    private static function buildMissingSellLedgerMessage(array $rows = []): string
    {
        if (empty($rows)) {
            return '没有发现已支付但漏写采购流水的卖出订单。';
        }

        $amount = array_reduce($rows, function ($sum, $row) {
            return self::toFloat($sum + self::toFloat($row['total'] ?? 0));
        }, 0);

        return '发现 ' . count($rows) . ' 笔已支付卖出订单没有写入采购流水，合计 ¥' . number_format($amount, 2, '.', '') . '。';
    }

    private static function buildTradeBalanceDiffOrders(array $buyOrders = [], array $sellOrders = [], string $direction = 'buy', float $diffAmount = 0): array
    {
        $side = $direction === 'sell' ? 'sell' : 'buy';
        $unbalancedOrders = self::findUnbalancedTradeOrders($buyOrders, $sellOrders, $side);
        if (!empty($unbalancedOrders)) {
            return [
                'match_type' => $side === 'sell' ? 'unbalanced_sell' : 'unbalanced_buy',
                'message' => $side === 'sell'
                    ? '按该商品买入/卖出流水配平后，剩余这些卖出订单未被买入流水抵扣，优先核对是否超卖或来源未登记。'
                    : '按该商品买入/卖出流水配平后，剩余这些买入订单未被卖出流水抵扣，优先核对是否还未卖出或未核销。',
                'orders' => $unbalancedOrders,
                'order_nos' => self::joinOrderNos(array_column($unbalancedOrders, 'order_no')),
            ];
        }

        return self::buildSuspectedDiffOrders($side === 'sell' ? $sellOrders : $buyOrders, $diffAmount);
    }

    private static function findUnbalancedTradeOrders(array $buyOrders = [], array $sellOrders = [], string $side = 'buy'): array
    {
        $buyOrders = self::prepareTradeBalanceOrders($buyOrders, 'buy');
        $sellOrders = self::prepareTradeBalanceOrders($sellOrders, 'sell');
        $buyIndex = 0;
        $sellIndex = 0;
        $buyCount = count($buyOrders);
        $sellCount = count($sellOrders);

        while ($buyIndex < $buyCount && $sellIndex < $sellCount) {
            if (intval($buyOrders[$buyIndex]['remaining_quantity'] ?? 0) <= 0) {
                $buyIndex++;
                continue;
            }
            if (intval($sellOrders[$sellIndex]['remaining_quantity'] ?? 0) <= 0) {
                $sellIndex++;
                continue;
            }

            $quantity = min(
                intval($buyOrders[$buyIndex]['remaining_quantity'] ?? 0),
                intval($sellOrders[$sellIndex]['remaining_quantity'] ?? 0)
            );
            self::consumeTradeBalanceOrder($buyOrders[$buyIndex], $quantity);
            self::consumeTradeBalanceOrder($sellOrders[$sellIndex], $quantity);
        }

        $orders = $side === 'sell' ? $sellOrders : $buyOrders;
        $orders = array_values(array_filter($orders, function ($order) {
            return intval($order['remaining_quantity'] ?? 0) > 0;
        }));
        foreach ($orders as &$order) {
            $order['original_amount'] = self::toFloat($order['amount'] ?? 0);
            $order['original_quantity'] = intval($order['quantity'] ?? 0);
            $order['amount'] = self::toFloat($order['remaining_amount'] ?? $order['amount'] ?? 0);
            $order['quantity'] = intval($order['remaining_quantity'] ?? $order['quantity'] ?? 0);
        }
        unset($order);

        usort($orders, function ($left, $right) {
            return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
        });

        return array_slice($orders, 0, 12);
    }

    private static function prepareTradeBalanceOrders(array $orders = [], string $side = 'buy'): array
    {
        $orders = array_values(array_filter($orders, function ($order) {
            return trim((string) ($order['order_no'] ?? '')) !== ''
                && intval($order['quantity'] ?? 0) > 0
                && self::moneyToCents($order['amount'] ?? 0) > 0;
        }));
        foreach ($orders as &$order) {
            $order['side'] = $side;
            $order['side_title'] = $side === 'sell' ? '未配平卖出' : '未配平买入';
            $order['remaining_quantity'] = intval($order['quantity'] ?? 0);
            $order['remaining_amount'] = self::toFloat($order['amount'] ?? 0);
        }
        unset($order);

        usort($orders, function ($left, $right) {
            $timeCompare = strcmp((string) ($left['pay_time'] ?? ''), (string) ($right['pay_time'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            return intval($left['member_order_id'] ?? 0) <=> intval($right['member_order_id'] ?? 0);
        });

        return $orders;
    }

    private static function consumeTradeBalanceOrder(array &$order, int $quantity = 0): void
    {
        $remainingQuantity = intval($order['remaining_quantity'] ?? 0);
        if ($quantity <= 0 || $remainingQuantity <= 0) {
            return;
        }

        $consumeQuantity = min($quantity, $remainingQuantity);
        $remainingCents = self::moneyToCents($order['remaining_amount'] ?? 0);
        $consumeCents = self::calculateTradeBalanceConsumeCents($order, $consumeQuantity);
        $order['remaining_quantity'] = $remainingQuantity - $consumeQuantity;
        $order['remaining_amount'] = self::toFloat(max(0, $remainingCents - $consumeCents) / 100);
    }

    private static function calculateTradeBalanceConsumeCents(array $order = [], int $quantity = 0): int
    {
        $remainingQuantity = intval($order['remaining_quantity'] ?? 0);
        $remainingCents = self::moneyToCents($order['remaining_amount'] ?? 0);
        if ($quantity <= 0 || $remainingQuantity <= 0 || $remainingCents <= 0) {
            return 0;
        }

        return $quantity >= $remainingQuantity
            ? $remainingCents
            : intval(round($remainingCents * $quantity / $remainingQuantity));
    }

    private static function buildSuspectedDiffOrders(array $orders = [], float $diffAmount = 0): array
    {
        $targetCents = self::moneyToCents($diffAmount);
        if ($targetCents <= 0 || empty($orders)) {
            return self::emptySuspectedDiffOrders('当前差额方向没有可定位的订单');
        }

        $exactOrders = array_values(array_filter($orders, function ($order) use ($targetCents) {
            return self::moneyToCents($order['amount'] ?? 0) === $targetCents;
        }));
        if (!empty($exactOrders)) {
            $exactOrders = array_slice($exactOrders, 0, 8);
            return [
                'match_type' => 'single',
                'message' => '找到单笔金额等于该商品差额的订单，优先核对。',
                'orders' => $exactOrders,
                'order_nos' => self::joinOrderNos(array_column($exactOrders, 'order_no')),
            ];
        }

        $combination = self::findAmountCombination($orders, $diffAmount, 4);
        if (!empty($combination)) {
            return [
                'match_type' => 'combination',
                'message' => '找到几笔订单合计等于该商品差额，优先核对。',
                'orders' => $combination,
                'order_nos' => self::joinOrderNos(array_column($combination, 'order_no')),
            ];
        }

        $nearOrders = array_slice(self::buildNearAmountCandidates($orders, $diffAmount), 0, 5);
        return [
            'match_type' => empty($nearOrders) ? 'none' : 'near',
            'message' => empty($nearOrders) ? '没有找到疑似差额订单' : '没有完全匹配的订单，先列最接近的订单。',
            'orders' => $nearOrders,
            'order_nos' => self::joinOrderNos(array_column($nearOrders, 'order_no')),
        ];
    }

    private static function emptySuspectedDiffOrders(string $message = ''): array
    {
        return [
            'match_type' => 'none',
            'message' => $message,
            'orders' => [],
            'order_nos' => '',
        ];
    }

    private static function buildNearGoodsGapCandidates(array $rows = [], float $targetAmount = 0): array
    {
        foreach ($rows as &$row) {
            $row['diff_to_target'] = self::toFloat(abs(floatval($row['diff_amount'] ?? 0) - $targetAmount));
        }
        unset($row);

        usort($rows, function ($left, $right) {
            if ($left['diff_to_target'] === $right['diff_to_target']) {
                return strcmp((string) ($right['pay_time'] ?? ''), (string) ($left['pay_time'] ?? ''));
            }
            return $left['diff_to_target'] <=> $right['diff_to_target'];
        });

        return $rows;
    }

    private static function joinOrderNos(array $values = []): string
    {
        $values = array_values(array_unique(array_filter(array_map('strval', $values), function ($value) {
            return trim($value) !== '';
        })));
        return implode('、', $values);
    }

    private static function emptyDiffOrdersResult(int $merchantId = 0, string $direction = '', float $targetAmount = 0, string $message = '', array $goodsGaps = [], array $goodsGapResult = []): array
    {
        return [
            'merchant_id' => $merchantId,
            'direction' => $direction,
            'target_amount' => $targetAmount,
            'match_type' => 'none',
            'message' => $message,
            'orders' => [],
            'candidate_orders' => [],
            'goods_gaps' => $goodsGaps,
            'goods_gap_match_type' => $goodsGapResult['match_type'] ?? 'none',
            'goods_gap_message' => $goodsGapResult['message'] ?? '',
        ];
    }

    private static function emptyTradeCompareRow(int $merchantId = 0, string $merchantTitle = ''): array
    {
        return [
            'merchant_id' => $merchantId,
            'merchant_title' => $merchantTitle,
            'buy_amount' => 0,
            'buy_order_count' => 0,
            'buy_quantity' => 0,
            'sell_amount' => 0,
            'sell_order_count' => 0,
            'sell_quantity' => 0,
            'net_amount' => 0,
            'trade_ratio' => null,
            'trade_judgement' => '',
        ];
    }

    private static function buildTradeJudgement(float $buyAmount = 0, float $sellAmount = 0): string
    {
        if ($buyAmount <= 0 && $sellAmount <= 0) {
            return '暂无买卖';
        }
        if ($sellAmount <= 0) {
            return '只有买入';
        }
        if ($buyAmount <= 0) {
            return '只有卖出';
        }
        $ratio = $buyAmount / $sellAmount;
        if ($ratio >= 0.8 && $ratio <= 1.2) {
            return '买卖基本持平';
        }
        return $ratio > 1.2 ? '买入明显更多' : '卖出明显更多';
    }

    private static function resolveDateRange(string $quickDate = '', string $startDate = '', string $endDate = ''): array
    {
        if ($startDate !== '' && $endDate !== '') {
            return [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        }

        switch ($quickDate) {
            case 'all':
                return ['', ''];
            case 'today':
                return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
            case 'yesterday':
                return [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))];
            case 'last7':
                return [date('Y-m-d 00:00:00', strtotime('-6 days')), date('Y-m-d 23:59:59')];
            case 'last30':
            default:
                return [date('Y-m-d 00:00:00', strtotime('-29 days')), date('Y-m-d 23:59:59')];
        }
    }

    private static function buildQueryLabel(array $params = []): string
    {
        $normalized = self::normalizeParams($params);
        if ($normalized['start_time'] === '' || $normalized['end_time'] === '') {
            return '全部时间';
        }
        return substr($normalized['start_time'], 0, 10) . ' 至 ' . substr($normalized['end_time'], 0, 10);
    }

    private static function attachListReconciliation(array &$list = []): void
    {
        $orderIds = array_values(array_unique(array_filter(array_map(function ($row) {
            return intval($row['member_order_id'] ?? 0);
        }, $list))));
        if (empty($orderIds)) {
            return;
        }

        $reconcileRows = self::appendReconciliationState(self::buildOrderReconciliationRows(['order_ids' => $orderIds]));
        $map = [];
        foreach ($reconcileRows as $row) {
            $map[intval($row['member_order_id'] ?? 0)] = $row;
        }

        foreach ($list as &$row) {
            $reconcile = $map[intval($row['member_order_id'] ?? 0)] ?? [];
            $row['ledger_amount'] = self::toFloat($reconcile['ledger_amount'] ?? $row['total'] ?? 0);
            $row['order_current_pay_price'] = self::toFloat($reconcile['order_pay_price'] ?? $row['order_pay_price'] ?? 0);
            $row['order_pay_price_source'] = $reconcile['order_pay_price_source'] ?? 'order';
            $row['order_pay_price_source_title'] = $reconcile['order_pay_price_source_title'] ?? '订单实付';
            $row['bill_amount'] = self::toFloat($reconcile['bill_amount'] ?? 0);
            $row['bill_count'] = intval($reconcile['bill_count'] ?? 0);
            $row['pay_type'] = intval($reconcile['pay_type'] ?? $row['pay_type'] ?? 0);
            $row['pay_type_title'] = $reconcile['pay_type_title'] ?? self::buildPayTypeTitle($row['pay_type']);
            $row['pay_status'] = intval($reconcile['pay_status'] ?? -1);
            $row['pay_status_title'] = $reconcile['pay_status_title'] ?? self::buildPayStatusTitle($row['pay_status']);
            $row['order_status'] = intval($reconcile['order_status'] ?? -1);
            $row['order_status_title'] = $reconcile['order_status_title'] ?? self::buildOrderStatusTitle($row['order_status']);
            $row['pay_auth_msg'] = $reconcile['pay_auth_msg'] ?? '';
            $row['reconcile_status'] = $reconcile['reconcile_status'] ?? 'review';
            $row['reconcile_status_title'] = $reconcile['reconcile_status_title'] ?? '待复核';
            $row['reconcile_message'] = $reconcile['reconcile_message'] ?? '未生成核算结论';
            $row['reconcile_diff_amount'] = self::toFloat($reconcile['reconcile_diff_amount'] ?? 0);
        }
    }

    private static function buildOrderReconciliationRows(array $params = []): array
    {
        if (!empty($params['order_ids']) && is_array($params['order_ids'])) {
            $orderIds = array_map('intval', $params['order_ids']);
        } else {
            $orderIds = (clone self::baseQuery($params))->column('member_order_id');
            $orderIds = array_map('intval', $orderIds);
        }

        $orderIds = array_values(array_unique(array_filter($orderIds)));
        if (empty($orderIds)) {
            return [];
        }

        return Db::name('merchant_purchase_ledger')
            ->where('is_delete', 0)
            ->whereIn('member_order_id', $orderIds)
            ->field('member_order_id,order_no,buyer_merchant_id,buyer_merchant_title,SUM(total) as ledger_amount,MAX(order_pay_price) as snapshot_pay_price,COUNT(id) as detail_count,MAX(pay_time) as pay_time,GROUP_CONCAT(DISTINCT source_type) as source_types,GROUP_CONCAT(DISTINCT source_merchant_title) as source_titles')
            ->group('member_order_id,order_no,buyer_merchant_id,buyer_merchant_title')
            ->order('pay_time', 'desc')
            ->select()
            ->toArray();
    }

    private static function appendReconciliationState(array $orderRows = []): array
    {
        if (empty($orderRows)) {
            return [];
        }

        $orderIds = array_values(array_unique(array_filter(array_map(function ($row) {
            return intval($row['member_order_id'] ?? 0);
        }, $orderRows))));

        $orderMap = [];
        if (!empty($orderIds)) {
            $rows = Db::name('member_order')
                ->whereIn('id', $orderIds)
                ->field('id,pay_price,total_price,pay_status,pay_type,status,pay_auth_msg')
                ->select()
                ->toArray();
            foreach ($rows as $row) {
                $orderMap[intval($row['id'] ?? 0)] = $row;
            }
        }

        $billMap = [];
        if (!empty($orderIds)) {
            $rows = Db::name('member_bill')
                ->whereIn('order_id', $orderIds)
                ->where('is_delete', 0)
                ->where('title', '购买商品')
                ->where('in_out', 2)
                ->field('order_id,SUM(money) as bill_amount,COUNT(id) as bill_count')
                ->group('order_id')
                ->select()
                ->toArray();
            foreach ($rows as $row) {
                $billMap[intval($row['order_id'] ?? 0)] = $row;
            }
        }

        foreach ($orderRows as &$row) {
            $orderId = intval($row['member_order_id'] ?? 0);
            $order = $orderMap[$orderId] ?? [];
            $bill = $billMap[$orderId] ?? [];
            $ledgerAmount = self::toFloat($row['ledger_amount'] ?? 0);
            $orderPayInfo = self::resolveOrderPayPrice($order, $row);
            $orderPayPrice = self::toFloat($orderPayInfo['amount'] ?? 0);
            $billInfo = self::resolveBillAmount($bill, $order, $orderPayPrice, $ledgerAmount);
            $billAmount = self::toFloat($billInfo['amount'] ?? 0);
            $billCount = intval($bill['bill_count'] ?? 0);
            $ledgerDiff = self::toFloat($ledgerAmount - $orderPayPrice);
            $billDiff = self::toFloat($billAmount - $orderPayPrice);

            $status = 'normal';
            $title = '核算正常';
            $message = '采购流水、核算实付、账单金额一致';
            $diffAmount = 0;

            if (abs($ledgerDiff) > 0.01 && abs($billDiff) > 0.01) {
                $status = 'amount_mismatch';
                $title = '金额不一致';
                $message = '采购流水和账单都与核算实付不一致';
                $diffAmount = abs($ledgerDiff) >= abs($billDiff) ? $ledgerDiff : $billDiff;
            } elseif (abs($ledgerDiff) > 0.01) {
                $status = 'ledger_mismatch';
                $title = '流水不一致';
                $message = '采购流水金额与核算实付不一致';
                $diffAmount = $ledgerDiff;
            } elseif ($billCount <= 0) {
                $status = 'missing_bill';
                $title = '缺少账单';
                $message = '订单已付款，但未找到会员账单记录';
                $diffAmount = 0 - $orderPayPrice;
            } elseif (abs($billDiff) > 0.01) {
                $status = 'bill_mismatch';
                $title = '账单不一致';
                $message = '会员账单金额与核算实付不一致';
                $diffAmount = $billDiff;
            }

            $row['member_order_id'] = $orderId;
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['source_type_title'] = self::buildSourceTypeTitle($row['source_types'] ?? '');
            $row['source_merchant_title'] = self::buildSourceTitle($row['source_titles'] ?? '');
            $row['ledger_amount'] = $ledgerAmount;
            $row['order_pay_price'] = $orderPayPrice;
            $row['order_pay_price_source'] = $orderPayInfo['source'] ?? 'order';
            $row['order_pay_price_source_title'] = $orderPayInfo['source_title'] ?? '订单实付';
            $row['bill_amount'] = $billAmount;
            $row['bill_amount_source'] = $billInfo['source'] ?? 'bill';
            $row['bill_amount_source_title'] = $billInfo['source_title'] ?? '会员账单';
            $row['bill_count'] = $billCount;
            $row['detail_count'] = intval($row['detail_count'] ?? 0);
            $row['pay_type'] = intval($order['pay_type'] ?? 0);
            $row['pay_type_title'] = self::buildPayTypeTitle($row['pay_type']);
            $row['pay_status'] = intval($order['pay_status'] ?? -1);
            $row['pay_status_title'] = self::buildPayStatusTitle($row['pay_status']);
            $row['order_status'] = intval($order['status'] ?? -1);
            $row['order_status_title'] = self::buildOrderStatusTitle($row['order_status']);
            $row['pay_auth_msg'] = (string) ($order['pay_auth_msg'] ?? '');
            $row['reconcile_status'] = $status;
            $row['reconcile_status_title'] = $title;
            $row['reconcile_message'] = $message;
            $row['reconcile_diff_amount'] = self::toFloat($diffAmount);
        }

        return $orderRows;
    }

    private static function resolveOrderPayPrice(array $order = [], array $ledgerRow = []): array
    {
        $payPrice = self::toFloat($order['pay_price'] ?? 0);
        $totalPrice = self::toFloat($order['total_price'] ?? 0);
        $snapshotPayPrice = self::toFloat($ledgerRow['snapshot_pay_price'] ?? 0);
        $payType = intval($order['pay_type'] ?? 0);
        $payStatus = intval($order['pay_status'] ?? 0);

        if ($payStatus === 1 && $payType === MemberOrderModel::getPayType('voucher', 1) && $totalPrice > 0) {
            return [
                'amount' => $totalPrice,
                'source' => 'voucher_order_total',
                'source_title' => '凭证订单金额',
            ];
        }

        if ($payPrice > 0) {
            return [
                'amount' => $payPrice,
                'source' => 'order',
                'source_title' => '订单实付',
            ];
        }

        if ($payStatus === 1 && $payType === MemberOrderModel::getPayType('voucher', 1) && $snapshotPayPrice > 0) {
            return [
                'amount' => $snapshotPayPrice,
                'source' => 'voucher_snapshot',
                'source_title' => '凭证审核金额',
            ];
        }

        return [
            'amount' => $payPrice,
            'source' => 'order',
            'source_title' => '订单实付',
        ];
    }

    private static function resolveBillAmount(array $bill = [], array $order = [], float $orderPayPrice = 0, float $ledgerAmount = 0): array
    {
        $billAmount = self::toFloat($bill['bill_amount'] ?? 0);
        $billCount = intval($bill['bill_count'] ?? 0);
        $payType = intval($order['pay_type'] ?? 0);
        $payStatus = intval($order['pay_status'] ?? 0);

        if (
            $billCount > 0
            && $payStatus === 1
            && $payType === MemberOrderModel::getPayType('voucher', 1)
            && $orderPayPrice > 0
            && abs($ledgerAmount - $orderPayPrice) <= 0.01
            && abs($billAmount - $orderPayPrice) > 0.01
        ) {
            return [
                'amount' => $orderPayPrice,
                'source' => 'voucher_order_total',
                'source_title' => '凭证订单金额',
            ];
        }

        return [
            'amount' => $billAmount,
            'source' => 'bill',
            'source_title' => '会员账单',
        ];
    }

    private static function buildPayTypeTitle(int $payType = 0): string
    {
        return MemberOrderModel::getPayType($payType, 2) ?: '未知支付';
    }

    private static function buildPayStatusTitle(int $payStatus = -1): string
    {
        if ($payStatus === 1) {
            return '已支付';
        }
        if ($payStatus === 2) {
            return '已驳回';
        }
        if ($payStatus === 0) {
            return '待审核/未支付';
        }
        return '未知';
    }

    private static function buildOrderStatusTitle(int $status = -1): string
    {
        return MemberOrderModel::getStatus($status, 2) ?: '未知';
    }

    private static function buildSourceTypeTitle(string $sourceTypes = ''): string
    {
        $types = array_values(array_unique(array_filter(explode(',', $sourceTypes))));
        if (empty($types)) {
            return '';
        }
        if (in_array('platform', $types, true) && in_array('merchant', $types, true)) {
            return '平台商品 + 商家商品';
        }
        return $types[0] === 'platform' ? '平台商品' : '商家商品';
    }

    private static function buildSourceTitle(string $sourceTitles = ''): string
    {
        $titles = array_values(array_unique(array_filter(explode(',', $sourceTitles))));
        if (count($titles) <= 1) {
            return $titles[0] ?? '';
        }
        return implode('、', array_slice($titles, 0, 3)) . (count($titles) > 3 ? '等' : '');
    }

    private static function buildCsv(array $rows = []): string
    {
        $content = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $line = [];
            foreach ($row as $column) {
                $value = (string) $column;
                if (preg_match('/^\s*[=+\-@]/u', $value) === 1) {
                    $value = "'" . $value;
                }
                $line[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $content .= implode(',', $line) . "\r\n";
        }
        return $content;
    }

    private static function toFloat($value): float
    {
        return round(floatval($value), 2);
    }

    private static function moneyToCents($value): int
    {
        return intval(round(floatval($value) * 100));
    }
}
