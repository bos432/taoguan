<?php

namespace app\common\service\report;

use app\common\model\member\MemberBillModel;
use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\order\MemberOrderLogModel;
use think\facade\Db;

class InternalTakeoverReportService
{
    const STAGE_PENDING_REVIEW = 'pending_review';
    const STAGE_PENDING_TRANSFER = 'pending_transfer';
    const STAGE_COMPLETED = 'completed';
    const STAGE_EXCEPTION = 'exception';

    private static $hasMemberSuperColumn = null;

    public static function filters(): array
    {
        $internalMerchants = self::loadInternalMerchantRows();
        $internalMerchantIds = array_values(array_map(function ($item) {
            return intval($item['id'] ?? 0);
        }, $internalMerchants));

        $sourceMerchantOptions = [];
        if (!empty($internalMerchantIds)) {
            $sourceMerchantRows = Db::name('member_order')
                ->alias('o')
                ->leftJoin('member_order_detailed d', 'd.member_order_id = o.id')
                ->leftJoin('goods g', 'g.id = d.goods_id')
                ->leftJoin('merchant sm', 'sm.id = g.merchant_id')
                ->where('o.is_delete', 0)
                ->where('o.is_disable', 0)
                ->where('o.pay_type', 2)
                ->whereIn('o.member_id', array_values(array_unique(array_map(function ($item) {
                    return intval($item['member_id'] ?? 0);
                }, $internalMerchants))))
                ->where('sm.is_delete', 0)
                ->where('sm.is_disable', 0)
                ->field('sm.id,sm.title')
                ->group('sm.id,sm.title')
                ->order('sm.id', 'desc')
                ->select()
                ->toArray();

            foreach ($sourceMerchantRows as $row) {
                $sourceMerchantOptions[] = [
                    'value' => intval($row['id'] ?? 0),
                    'label' => self::maskMerchantTitle((string) ($row['title'] ?? ''), '商家'),
                ];
            }
        }

        return [
            'quick_dates' => [
                ['value' => 'today', 'label' => '今天'],
                ['value' => 'yesterday', 'label' => '昨天'],
                ['value' => 'last7', 'label' => '近7天'],
                ['value' => 'last30', 'label' => '近30天'],
            ],
            'pay_status_options' => [
                ['value' => -1, 'label' => '全部审核状态'],
                ['value' => 0, 'label' => '待审核'],
                ['value' => 1, 'label' => '已确认收款'],
                ['value' => 2, 'label' => '已驳回'],
            ],
            'stage_options' => [
                ['value' => '', 'label' => '全部处理阶段'],
                ['value' => self::STAGE_PENDING_REVIEW, 'label' => '待审核'],
                ['value' => self::STAGE_PENDING_TRANSFER, 'label' => '待转商品'],
                ['value' => self::STAGE_COMPLETED, 'label' => '已完成'],
                ['value' => self::STAGE_EXCEPTION, 'label' => '真正异常'],
            ],
            'internal_merchants' => array_map(function ($item) {
                return [
                    'value' => intval($item['id'] ?? 0),
                    'label' => self::maskMerchantTitle((string) ($item['title'] ?? ''), '内部号'),
                ];
            }, $internalMerchants),
            'source_merchants' => $sourceMerchantOptions,
        ];
    }

    public static function summary(array $params = []): array
    {
        $rows = self::buildDataset($params);
        $cards = self::buildSummaryCards($rows);

        return [
            'cards' => $cards,
            'health_panel' => self::buildHealthPanel($rows, $params),
            'query_label' => self::buildQueryLabel($params),
        ];
    }

    public static function list(array $params = []): array
    {
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, intval($params['limit'] ?? 20));
        $rows = self::buildDataset($params);
        $count = count($rows);
        $pages = $limit > 0 ? intval(ceil($count / $limit)) : 1;
        $offset = ($page - 1) * $limit;

        return [
            'count' => $count,
            'pages' => $pages,
            'page' => $page,
            'limit' => $limit,
            'list' => array_slice($rows, $offset, $limit),
        ];
    }

    public static function detail(int $id): array
    {
        if ($id <= 0) {
            exception('缺少订单ID');
        }

        $rows = self::buildDataset(['id' => $id]);
        if (empty($rows)) {
            exception('未查询到内部接盘订单');
        }

        return $rows[0];
    }

    private static function buildDataset(array $params = []): array
    {
        $internalMerchants = self::loadInternalMerchantRows();
        if (empty($internalMerchants)) {
            return [];
        }

        $internalMerchantByMember = [];
        foreach ($internalMerchants as $merchant) {
            $memberId = intval($merchant['member_id'] ?? 0);
            if ($memberId > 0) {
                $internalMerchantByMember[$memberId] = $merchant;
            }
        }
        if (empty($internalMerchantByMember)) {
            return [];
        }

        $normalized = self::normalizeParams($params);
        $query = Db::name('member_order')
            ->alias('o')
            ->leftJoin('member m', 'm.member_id = o.member_id')
            ->where('o.is_delete', 0)
            ->where('o.is_disable', 0)
            ->where('o.pay_type', 2)
            ->whereIn('o.member_id', array_keys($internalMerchantByMember));

        if ($normalized['id'] > 0) {
            $query->where('o.id', $normalized['id']);
        }
        if ($normalized['start_time'] !== '') {
            $query->where('o.create_time', '>=', $normalized['start_time']);
        }
        if ($normalized['end_time'] !== '') {
            $query->where('o.create_time', '<=', $normalized['end_time']);
        }
        if ($normalized['pay_status'] >= 0) {
            $query->where('o.pay_status', $normalized['pay_status']);
        }

        $baseRows = $query
            ->field(
                'o.id,o.order_no,o.member_id,o.merchant_id,o.total_num,o.total_price,o.pay_price,o.pay_status,o.pay_type,o.status,o.mark,o.remark,o.pay_time,o.create_time,o.update_time,o.pay_auth_msg,o.pay_voucher_imgs,o.delivery_type,o.take_name,o.take_phone,o.take_region,o.take_address,o.self_name,o.self_phone,m.nickname as buyer_nickname,m.phone as buyer_phone'
            )
            ->order('o.id', 'desc')
            ->select()
            ->toArray();

        if (empty($baseRows)) {
            return [];
        }

        $orderIds = array_values(array_map('intval', array_column($baseRows, 'id')));
        $detailsMap = self::loadOrderDetails($orderIds);
        $billMap = self::loadBillMap($orderIds);
        $logMap = self::loadLogMap($orderIds);
        $duplicateTargetCount = [];
        foreach ($baseRows as $row) {
            $internalMerchant = $internalMerchantByMember[intval($row['member_id'] ?? 0)] ?? null;
            if ($internalMerchant) {
                $key = intval($internalMerchant['id'] ?? 0);
                $duplicateTargetCount[$key] = intval($duplicateTargetCount[$key] ?? 0) + 1;
            }
        }

        $rows = [];
        foreach ($baseRows as $row) {
            $memberId = intval($row['member_id'] ?? 0);
            $internalMerchant = $internalMerchantByMember[$memberId] ?? null;
            $details = $detailsMap[intval($row['id'] ?? 0)] ?? [];
            $bills = $billMap[intval($row['id'] ?? 0)] ?? [];
            $logs = $logMap[intval($row['id'] ?? 0)] ?? [];

            $sourceMerchantId = intval($details[0]['source_merchant_id'] ?? 0);
            $sourceMerchantTitle = (string) ($details[0]['source_merchant_title'] ?? '平台自营');
            $transferProbe = self::probeTransferState($row, $details, $internalMerchant);
            $billAmount = 0;
            foreach ($bills as $bill) {
                $billAmount += floatval($bill['money'] ?? 0);
            }
            $billAmount = round($billAmount, 2);

            $payStatus = intval($row['pay_status'] ?? 0);
            $exceptionReasons = [];
            $stageCode = self::STAGE_PENDING_REVIEW;
            if (empty($internalMerchant)) {
                $stageCode = self::STAGE_EXCEPTION;
                $exceptionReasons[] = '未找到接手内部号';
            } elseif ($payStatus === 0) {
                $stageCode = self::STAGE_PENDING_REVIEW;
            } elseif ($payStatus === 1 && !$transferProbe['is_transferred']) {
                $stageCode = self::STAGE_PENDING_TRANSFER;
                if ($transferProbe['reason'] !== '') {
                    $exceptionReasons[] = $transferProbe['reason'];
                }
            } elseif ($payStatus === 1 && $transferProbe['is_transferred']) {
                if (empty($bills)) {
                    $stageCode = self::STAGE_EXCEPTION;
                    $exceptionReasons[] = '账单缺失';
                } elseif (abs($billAmount - floatval($row['pay_price'] ?? 0)) > 0.01) {
                    $stageCode = self::STAGE_EXCEPTION;
                    $exceptionReasons[] = '账单金额与支付金额不一致';
                } else {
                    $stageCode = self::STAGE_COMPLETED;
                }
            } else {
                $stageCode = self::STAGE_EXCEPTION;
                $exceptionReasons[] = $payStatus === 2 ? '付款审核已驳回' : '付款状态异常';
                if (!empty($row['pay_auth_msg'])) {
                    $exceptionReasons[] = (string) $row['pay_auth_msg'];
                }
            }

            $rowInternalMerchantId = intval($internalMerchant['id'] ?? 0);
            if ($rowInternalMerchantId > 0 && intval($duplicateTargetCount[$rowInternalMerchantId] ?? 0) >= 3) {
                $exceptionReasons[] = '同一内部号在当前条件下接手订单较多';
            }
            if (floatval($row['pay_price'] ?? 0) >= 10000 || intval($row['total_num'] ?? 0) >= 10) {
                $exceptionReasons[] = '当前订单金额或件数偏大';
            }

            $currentStepText = self::buildCurrentStepText($stageCode);
            $processingText = self::buildProcessingText($stageCode, $exceptionReasons);

            $enriched = [
                'id' => intval($row['id'] ?? 0),
                'order_no' => (string) ($row['order_no'] ?? ''),
                'create_time' => (string) ($row['create_time'] ?? ''),
                'pay_time' => (string) ($row['pay_time'] ?? ''),
                'internal_merchant_id' => $rowInternalMerchantId,
                'internal_merchant_title' => self::maskMerchantTitle((string) ($internalMerchant['title'] ?? ''), '内部号'),
                'buyer_nickname' => self::maskNickname((string) ($row['buyer_nickname'] ?? '')),
                'buyer_phone' => self::maskPhone((string) ($row['buyer_phone'] ?? '')),
                'source_merchant_id' => $sourceMerchantId,
                'source_merchant_title' => self::maskMerchantTitle($sourceMerchantTitle, '平台自营'),
                'goods_summary' => self::buildGoodsSummary($details),
                'total_num' => intval($row['total_num'] ?? 0),
                'total_price' => round(floatval($row['total_price'] ?? 0), 2),
                'pay_price' => round(floatval($row['pay_price'] ?? 0), 2),
                'pay_status' => $payStatus,
                'pay_status_title' => self::buildPayStatusTitle($payStatus),
                'transfer_status' => $transferProbe['is_transferred'] ? 1 : 0,
                'transfer_status_title' => $transferProbe['is_transferred'] ? '已转入内部号' : ($payStatus === 1 ? '待转商品' : '未到转入阶段'),
                'stage_code' => $stageCode,
                'stage_title' => self::buildStageTitle($stageCode),
                'current_step_text' => $currentStepText,
                'processing_text' => $processingText,
                'affects_fund' => self::buildAffectsFund($stageCode),
                'affects_fund_title' => self::buildAffectsFund($stageCode) ? '影响资金' : '不影响资金',
                'exception_reason' => implode('；', array_values(array_unique(array_filter($exceptionReasons)))),
                'pay_auth_msg' => (string) ($row['pay_auth_msg'] ?? ''),
                'bill_count' => count($bills),
                'bill_amount' => $billAmount,
                'bill_status_title' => empty($bills) ? '未生成账单' : '已生成账单',
                'transfer_match_count' => intval($transferProbe['match_count'] ?? 0),
                'delivery_type' => intval($row['delivery_type'] ?? 0),
                'delivery_type_title' => intval($row['delivery_type'] ?? 0) === 1 ? '快递配送' : '门店自提',
                'take_name' => (string) ($row['take_name'] ?? ''),
                'take_phone' => (string) ($row['take_phone'] ?? ''),
                'take_region' => (string) ($row['take_region'] ?? ''),
                'take_address' => (string) ($row['take_address'] ?? ''),
                'self_name' => (string) ($row['self_name'] ?? ''),
                'self_phone' => (string) ($row['self_phone'] ?? ''),
                'mark' => (string) ($row['mark'] ?? ''),
                'remark' => (string) ($row['remark'] ?? ''),
                'pay_voucher_imgs' => self::resolveVoucherFiles((string) ($row['pay_voucher_imgs'] ?? '')),
                'detail_items' => $details,
                'logs' => $logs,
                'bills' => $bills,
            ];

            if (!self::matchesFilters($enriched, $normalized)) {
                continue;
            }

            $rows[] = $enriched;
        }

        return array_values($rows);
    }

    private static function buildSummaryCards(array $rows): array
    {
        $totalCount = count($rows);
        $completed = 0;
        $totalAmount = 0;
        $pendingReview = 0;
        $pendingTransfer = 0;
        $exceptions = 0;

        foreach ($rows as $row) {
            $totalAmount += floatval($row['pay_price'] ?? 0);
            switch ($row['stage_code']) {
                case self::STAGE_COMPLETED:
                    $completed++;
                    break;
                case self::STAGE_PENDING_REVIEW:
                    $pendingReview++;
                    break;
                case self::STAGE_PENDING_TRANSFER:
                    $pendingTransfer++;
                    break;
                case self::STAGE_EXCEPTION:
                    $exceptions++;
                    break;
            }
        }

        $priorityText = '整体正常';
        if ($exceptions > 0) {
            $priorityText = '异常 ' . $exceptions . ' 单';
        } elseif ($pendingReview > 0) {
            $priorityText = '待审核 ' . $pendingReview . ' 单';
        } elseif ($pendingTransfer > 0) {
            $priorityText = '待转商品 ' . $pendingTransfer . ' 单';
        }

        return [
            'total_count' => $totalCount,
            'priority_text' => $priorityText,
            'completed_count' => $completed,
            'total_amount' => round($totalAmount, 2),
            'pending_review_count' => $pendingReview,
            'pending_transfer_count' => $pendingTransfer,
            'exception_count' => $exceptions,
        ];
    }

    private static function buildHealthPanel(array $rows, array $params = []): array
    {
        $targetMerchantId = intval($params['internal_merchant_id'] ?? 0);
        if ($targetMerchantId <= 0 && !empty($rows)) {
            $targetMerchantId = intval($rows[0]['internal_merchant_id'] ?? 0);
        }
        if ($targetMerchantId <= 0) {
            return [];
        }

        $targetRows = array_values(array_filter($rows, function ($row) use ($targetMerchantId) {
            return intval($row['internal_merchant_id'] ?? 0) === $targetMerchantId;
        }));
        if (empty($targetRows)) {
            return [];
        }

        $merchantTitle = (string) ($targetRows[0]['internal_merchant_title'] ?? '内部号');
        $orderCount = count($targetRows);
        $paidCount = 0;
        $billCount = 0;
        $transferCount = 0;
        $completedCount = 0;
        $pendingReview = 0;
        $pendingTransfer = 0;
        $exceptionCount = 0;
        $totalAmount = 0;
        $paidAmount = 0;
        $billAmount = 0;

        foreach ($targetRows as $row) {
            $totalAmount += floatval($row['pay_price'] ?? 0);
            if (intval($row['pay_status'] ?? 0) === 1) {
                $paidCount++;
                $paidAmount += floatval($row['pay_price'] ?? 0);
            }
            if (intval($row['bill_count'] ?? 0) > 0) {
                $billCount++;
                $billAmount += floatval($row['bill_amount'] ?? 0);
            }
            if (intval($row['transfer_status'] ?? 0) === 1) {
                $transferCount++;
            }
            switch ($row['stage_code']) {
                case self::STAGE_COMPLETED:
                    $completedCount++;
                    break;
                case self::STAGE_PENDING_REVIEW:
                    $pendingReview++;
                    break;
                case self::STAGE_PENDING_TRANSFER:
                    $pendingTransfer++;
                    break;
                case self::STAGE_EXCEPTION:
                    $exceptionCount++;
                    break;
            }
        }

        $statusTag = '正常运行';
        $summaryText = '当前内部号整体流转平稳，可以按例行抽查节奏复核。';
        $nextAction = '继续抽查最近已完成订单，确认账单和商品归属都正常。';
        if ($exceptionCount > 0) {
            $statusTag = '存在风险';
            $summaryText = '当前内部号存在需要人工核查的异常单，建议优先处理。';
            $nextAction = '先处理真正异常，再回头看待审核和待转商品。';
        } elseif ($pendingTransfer > 0 || $pendingReview > 0) {
            $statusTag = '流程处理中';
            $summaryText = '当前内部号还有正常积压中的订单，需要继续跟进。';
            $nextAction = $pendingReview > 0
                ? '优先去确认付款审核，再处理待转商品。'
                : '优先补做商品转入，避免已收款订单继续堆积。';
        }

        return [
            'merchant_id' => $targetMerchantId,
            'merchant_title' => $merchantTitle,
            'status_tag' => $statusTag,
            'summary_text' => $summaryText,
            'next_action' => $nextAction,
            'completed_count' => $completedCount,
            'pending_review_count' => $pendingReview,
            'pending_transfer_count' => $pendingTransfer,
            'exception_count' => $exceptionCount,
            'fund_status' => $exceptionCount > 0 ? '需复核' : '基本正常',
            'bill_status' => $billCount < $paidCount ? '存在缺口' : '账单齐全',
            'transfer_status' => $transferCount < $paidCount ? '仍有待转商品' : '转入闭环正常',
            'exception_status' => $exceptionCount > 0 ? '存在异常' : '未见明显异常',
            'order_count' => $orderCount,
            'paid_count' => $paidCount,
            'bill_count' => $billCount,
            'transfer_count' => $transferCount,
            'total_amount' => round($totalAmount, 2),
            'paid_amount' => round($paidAmount, 2),
            'bill_amount' => round($billAmount, 2),
            'fund_gap_amount' => round($paidAmount - $billAmount, 2),
        ];
    }

    private static function normalizeParams(array $params = []): array
    {
        $quickDate = trim((string) ($params['quick_date'] ?? ''));
        $startDate = trim((string) ($params['start_date'] ?? ''));
        $endDate = trim((string) ($params['end_date'] ?? ''));

        if ($quickDate === '') {
            $quickDate = 'last7';
        }

        [$startTime, $endTime] = self::resolveDateRange($quickDate, $startDate, $endDate);

        return [
            'id' => intval($params['id'] ?? 0),
            'quick_date' => $quickDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'internal_merchant_id' => intval($params['internal_merchant_id'] ?? 0),
            'source_merchant_id' => intval($params['source_merchant_id'] ?? 0),
            'pay_status' => intval($params['pay_status'] ?? -1),
            'stage_code' => trim((string) ($params['stage_code'] ?? '')),
            'keyword' => trim((string) ($params['keyword'] ?? '')),
        ];
    }

    private static function resolveDateRange(string $quickDate = '', string $startDate = '', string $endDate = ''): array
    {
        if ($startDate !== '' && $endDate !== '') {
            return [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        }

        switch ($quickDate) {
            case 'today':
                return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
            case 'yesterday':
                return [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))];
            case 'last30':
                return [date('Y-m-d 00:00:00', strtotime('-29 days')), date('Y-m-d 23:59:59')];
            case 'last7':
            default:
                return [date('Y-m-d 00:00:00', strtotime('-6 days')), date('Y-m-d 23:59:59')];
        }
    }

    private static function loadInternalMerchantRows(): array
    {
        $query = Db::name('merchant')
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('auth_state', 1)
            ->where('member_id', '>', 0);

        if (self::supportsMemberSuperColumn()) {
            $query->where('member_is_super', 1);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->field('id,title,member_id')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    private static function supportsMemberSuperColumn(): bool
    {
        if (self::$hasMemberSuperColumn !== null) {
            return self::$hasMemberSuperColumn;
        }

        try {
            $database = config('database.connections.mysql.database');
            $prefix = config('database.connections.mysql.prefix');
            $rows = Db::query(
                "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$database, $prefix . 'merchant', 'member_is_super']
            );
            self::$hasMemberSuperColumn = intval($rows[0]['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            self::$hasMemberSuperColumn = false;
        }

        return self::$hasMemberSuperColumn;
    }

    private static function loadOrderDetails(array $orderIds = []): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $detailRows = MemberOrderDetailedModel::with([
            'goods' => function ($query) {
                $query
                    ->with(['merchant', 'image'])
                    ->append(['image_url', 'merchant_title'])
                    ->field('id,title,spec,price,image_id,merchant_id,member_id,create_time,source,unit');
            },
        ])
            ->whereIn('member_order_id', $orderIds)
            ->select()
            ->toArray();

        $map = [];
        foreach ($detailRows as $row) {
            $goods = $row['goods'] ?? [];
            $map[intval($row['member_order_id'] ?? 0)][] = [
                'id' => intval($row['id'] ?? 0),
                'goods_id' => intval($row['goods_id'] ?? 0),
                'quantity' => intval($row['quantity'] ?? 0),
                'price' => round(floatval($row['price'] ?? 0), 2),
                'total' => round(floatval($row['total'] ?? 0), 2),
                'goods_title' => (string) ($goods['title'] ?? ''),
                'goods_spec' => (string) ($goods['spec'] ?? ''),
                'goods_image_url' => (string) ($goods['image_url'] ?? ''),
                'goods_unit' => (string) ($goods['unit'] ?? ''),
                'source_merchant_id' => intval($goods['merchant_id'] ?? 0),
                'source_merchant_title' => (string) ($goods['merchant_title'] ?? '平台自营'),
                'goods_member_id' => intval($goods['member_id'] ?? 0),
                'goods_create_time' => (string) ($goods['create_time'] ?? ''),
            ];
        }

        return $map;
    }

    private static function loadBillMap(array $orderIds = []): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $rows = MemberBillModel::whereIn('order_id', $orderIds)
            ->field('id,order_id,money,bill_type_id,title,create_time')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['order_id'] ?? 0)][] = $row;
        }

        return $map;
    }

    private static function loadLogMap(array $orderIds = []): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $rows = MemberOrderLogModel::whereIn('member_order_id', $orderIds)
            ->field('id,member_order_id,title,role_type,create_time')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['member_order_id'] ?? 0)][] = $row;
        }

        return $map;
    }

    private static function probeTransferState(array $order, array $details = [], ?array $internalMerchant = null): array
    {
        if (empty($internalMerchant)) {
            return ['is_transferred' => false, 'match_count' => 0, 'reason' => '未找到目标内部号'];
        }
        if (intval($order['pay_status'] ?? 0) !== 1) {
            return ['is_transferred' => false, 'match_count' => 0, 'reason' => '尚未确认收款'];
        }
        if (empty($details)) {
            return ['is_transferred' => false, 'match_count' => 0, 'reason' => '订单缺少商品明细'];
        }

        $targetMerchantId = intval($internalMerchant['id'] ?? 0);
        $payTime = trim((string) ($order['pay_time'] ?? ''));
        $windowStart = $payTime !== '' ? date('Y-m-d H:i:s', strtotime($payTime . ' -2 day')) : date('Y-m-d H:i:s', strtotime('-7 day'));

        $titles = [];
        $memberIds = [];
        foreach ($details as $detail) {
            $title = trim((string) ($detail['goods_title'] ?? ''));
            if ($title !== '') {
                $titles[] = $title;
            }
            $memberId = intval($detail['goods_member_id'] ?? 0);
            if ($memberId > 0) {
                $memberIds[] = $memberId;
            }
        }
        $titles = array_values(array_unique($titles));
        $memberIds = array_values(array_unique($memberIds));

        $query = Db::name('goods')
            ->where('merchant_id', $targetMerchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('create_time', '>=', $windowStart);

        if (!empty($titles)) {
            $query->whereIn('title', $titles);
        }
        if (!empty($memberIds)) {
            $query->whereIn('member_id', $memberIds);
        }

        $goodsRows = $query
            ->field('id,title,spec,price,member_id,create_time')
            ->select()
            ->toArray();

        $matchCount = 0;
        foreach ($details as $detail) {
            foreach ($goodsRows as $goodsRow) {
                if (
                    trim((string) ($goodsRow['title'] ?? '')) === trim((string) ($detail['goods_title'] ?? '')) &&
                    abs(floatval($goodsRow['price'] ?? 0) - floatval($detail['price'] ?? 0)) <= 0.01
                ) {
                    $matchCount++;
                    break;
                }
            }
        }

        return [
            'is_transferred' => $matchCount > 0,
            'match_count' => $matchCount,
            'reason' => $matchCount > 0 ? '' : '已收款但未识别到转入内部号的商品记录',
        ];
    }

    private static function resolveVoucherFiles(string $fileIds = ''): array
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', $fileIds))));
        if (empty($ids)) {
            return [];
        }

        $rows = Db::name('file')
            ->whereIn('file_id', $ids)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->field('file_id,file_name,file_path,file_url')
            ->select()
            ->toArray();

        foreach ($rows as &$row) {
            if (empty($row['file_url']) && !empty($row['file_path'])) {
                $row['file_url'] = file_url($row['file_path']);
            }
        }

        return $rows;
    }

    private static function matchesFilters(array $row, array $params): bool
    {
        if (intval($params['internal_merchant_id'] ?? 0) > 0 && intval($row['internal_merchant_id'] ?? 0) !== intval($params['internal_merchant_id'])) {
            return false;
        }
        if (intval($params['source_merchant_id'] ?? 0) > 0 && intval($row['source_merchant_id'] ?? 0) !== intval($params['source_merchant_id'])) {
            return false;
        }
        if (($params['stage_code'] ?? '') !== '' && (string) ($row['stage_code'] ?? '') !== (string) $params['stage_code']) {
            return false;
        }

        $keyword = trim((string) ($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $haystack = implode(' ', [
                $row['order_no'] ?? '',
                $row['buyer_phone'] ?? '',
                $row['buyer_nickname'] ?? '',
                $row['internal_merchant_title'] ?? '',
                $row['source_merchant_title'] ?? '',
                $row['goods_summary'] ?? '',
            ]);
            if (mb_stripos($haystack, $keyword) === false) {
                return false;
            }
        }

        return true;
    }

    private static function buildQueryLabel(array $params = []): string
    {
        $normalized = self::normalizeParams($params);
        if ($normalized['start_time'] !== '' && $normalized['end_time'] !== '') {
            return substr($normalized['start_time'], 0, 10) . ' 至 ' . substr($normalized['end_time'], 0, 10);
        }

        return '当前筛选范围';
    }

    private static function buildGoodsSummary(array $details = []): string
    {
        if (empty($details)) {
            return '暂无商品信息';
        }

        $parts = [];
        foreach ($details as $detail) {
            $parts[] = trim((string) ($detail['goods_title'] ?? '商品')) . ' x' . intval($detail['quantity'] ?? 0);
        }

        return implode(' / ', array_slice($parts, 0, 3));
    }

    private static function buildPayStatusTitle(int $payStatus): string
    {
        if ($payStatus === 1) {
            return '已确认收款';
        }
        if ($payStatus === 2) {
            return '已驳回';
        }

        return '待审核';
    }

    private static function buildStageTitle(string $stageCode): string
    {
        switch ($stageCode) {
            case self::STAGE_PENDING_TRANSFER:
                return '待转商品';
            case self::STAGE_COMPLETED:
                return '已完成';
            case self::STAGE_EXCEPTION:
                return '真正异常';
            case self::STAGE_PENDING_REVIEW:
            default:
                return '待审核';
        }
    }

    private static function buildCurrentStepText(string $stageCode): string
    {
        switch ($stageCode) {
            case self::STAGE_PENDING_TRANSFER:
                return '平台已确认收款，等待商品转到内部号';
            case self::STAGE_COMPLETED:
                return '收款、账单、转入都已闭环';
            case self::STAGE_EXCEPTION:
                return '存在需要人工排查的异常';
            case self::STAGE_PENDING_REVIEW:
            default:
                return '买家已提交凭证，等待平台确认';
        }
    }

    private static function buildProcessingText(string $stageCode, array $exceptionReasons = []): string
    {
        if ($stageCode === self::STAGE_EXCEPTION && !empty($exceptionReasons)) {
            return implode('；', array_values(array_unique(array_filter($exceptionReasons))));
        }
        if ($stageCode === self::STAGE_COMPLETED) {
            return '当前订单已可视为正常完成';
        }
        if ($stageCode === self::STAGE_PENDING_TRANSFER) {
            return '建议优先补做商品转入';
        }

        return '建议先确认付款审核';
    }

    private static function buildAffectsFund(string $stageCode): int
    {
        return $stageCode === self::STAGE_EXCEPTION ? 1 : 0;
    }

    private static function maskMerchantTitle(string $title = '', string $fallback = ''): string
    {
        $title = trim($title);
        if ($title === '') {
            return $fallback;
        }
        $length = mb_strlen($title);
        if ($length <= 1) {
            return $title;
        }

        return mb_substr($title, 0, 1) . '***' . mb_substr($title, -1, 1);
    }

    private static function maskPhone(string $phone = ''): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }
        if (mb_strlen($phone) < 7) {
            return $phone;
        }

        return mb_substr($phone, 0, 3) . '****' . mb_substr($phone, -4);
    }

    private static function maskNickname(string $nickname = ''): string
    {
        $nickname = trim($nickname);
        if ($nickname === '') {
            return '';
        }
        $length = mb_strlen($nickname);
        if ($length <= 2) {
            return mb_substr($nickname, 0, 1) . '*';
        }

        return mb_substr($nickname, 0, 1) . '***' . mb_substr($nickname, -1);
    }
}
