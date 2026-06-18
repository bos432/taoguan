<?php
namespace app\common\service\report;

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
        if (empty($orders)) {
            return self::emptyDiffOrdersResult($merchantId, $direction, $targetAmount, '当前筛选范围内没有找到可匹配的订单');
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
        ];
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

    private static function emptyDiffOrdersResult(int $merchantId = 0, string $direction = '', float $targetAmount = 0, string $message = ''): array
    {
        return [
            'merchant_id' => $merchantId,
            'direction' => $direction,
            'target_amount' => $targetAmount,
            'match_type' => 'none',
            'message' => $message,
            'orders' => [],
            'candidate_orders' => [],
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
