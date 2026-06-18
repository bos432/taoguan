<?php
namespace app\common\service\report;

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
            'reconciliation' => self::reconciliation($params),
            'query_label' => self::buildQueryLabel($params),
        ];
    }

    public static function reconciliation(array $params = []): array
    {
        $rows = self::appendReconciliationState(self::buildOrderReconciliationRows($params));
        $cards = [
            'order_count' => count($rows),
            'normal_count' => 0,
            'exception_count' => 0,
            'missing_bill_count' => 0,
            'amount_mismatch_count' => 0,
            'exception_amount' => 0,
        ];

        foreach ($rows as $row) {
            if (($row['reconcile_status'] ?? '') === 'normal') {
                $cards['normal_count']++;
            } else {
                $cards['exception_count']++;
                $cards['exception_amount'] = self::toFloat($cards['exception_amount'] + abs(floatval($row['reconcile_diff_amount'] ?? 0)));
                if (($row['reconcile_status'] ?? '') === 'missing_bill') {
                    $cards['missing_bill_count']++;
                } else {
                    $cards['amount_mismatch_count']++;
                }
            }
        }

        return [
            'cards' => $cards,
            'exception_list' => array_values(array_slice(array_filter($rows, function ($row) {
                return ($row['reconcile_status'] ?? '') !== 'normal';
            }), 0, 20)),
        ];
    }

    public static function list(array $params = []): array
    {
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, intval($params['limit'] ?? 20));
        $query = self::baseQuery($params);
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
            ['订单号', '支付时间', '核算结果', '核算说明', '核算差额', '买方商家ID', '买方商家', '来源类型', '来源商家ID', '来源名称', '商品ID', '商品名称', '商品编码', '规格', '单位', '数量', '单价', '明细金额', '订单实付', '账单金额'],
        ];

        foreach ($list as $item) {
            $rows[] = [
                $item['order_no'] ?? '',
                $item['pay_time'] ?? '',
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
                self::toFloat($item['order_pay_price'] ?? 0),
                self::toFloat($item['bill_amount'] ?? 0),
            ];
        }

        return [
            'filename' => 'merchant_purchase_ledger_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
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
            'keyword' => trim((string) ($params['keyword'] ?? '')),
        ];
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
            $row['bill_amount'] = self::toFloat($reconcile['bill_amount'] ?? 0);
            $row['bill_count'] = intval($reconcile['bill_count'] ?? 0);
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
                ->field('id,pay_price,total_price,pay_status,status')
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
            $orderPayPrice = self::toFloat($order['pay_price'] ?? $row['snapshot_pay_price'] ?? 0);
            $billAmount = self::toFloat($bill['bill_amount'] ?? 0);
            $billCount = intval($bill['bill_count'] ?? 0);
            $ledgerDiff = self::toFloat($ledgerAmount - $orderPayPrice);
            $billDiff = self::toFloat($billAmount - $orderPayPrice);

            $status = 'normal';
            $title = '核算正常';
            $message = '采购流水、订单实付、账单金额一致';
            $diffAmount = 0;

            if (abs($ledgerDiff) > 0.01 && abs($billDiff) > 0.01) {
                $status = 'amount_mismatch';
                $title = '金额不一致';
                $message = '采购流水和账单都与订单实付不一致';
                $diffAmount = abs($ledgerDiff) >= abs($billDiff) ? $ledgerDiff : $billDiff;
            } elseif (abs($ledgerDiff) > 0.01) {
                $status = 'ledger_mismatch';
                $title = '流水不一致';
                $message = '采购流水金额与订单实付不一致';
                $diffAmount = $ledgerDiff;
            } elseif ($billCount <= 0) {
                $status = 'missing_bill';
                $title = '缺少账单';
                $message = '订单已付款，但未找到会员账单记录';
                $diffAmount = 0 - $orderPayPrice;
            } elseif (abs($billDiff) > 0.01) {
                $status = 'bill_mismatch';
                $title = '账单不一致';
                $message = '会员账单金额与订单实付不一致';
                $diffAmount = $billDiff;
            }

            $row['member_order_id'] = $orderId;
            $row['buyer_merchant_id'] = intval($row['buyer_merchant_id'] ?? 0);
            $row['source_type_title'] = self::buildSourceTypeTitle($row['source_types'] ?? '');
            $row['source_merchant_title'] = self::buildSourceTitle($row['source_titles'] ?? '');
            $row['ledger_amount'] = $ledgerAmount;
            $row['order_pay_price'] = $orderPayPrice;
            $row['bill_amount'] = $billAmount;
            $row['bill_count'] = $billCount;
            $row['detail_count'] = intval($row['detail_count'] ?? 0);
            $row['reconcile_status'] = $status;
            $row['reconcile_status_title'] = $title;
            $row['reconcile_message'] = $message;
            $row['reconcile_diff_amount'] = self::toFloat($diffAmount);
        }

        return $orderRows;
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
}
