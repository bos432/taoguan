<?php

namespace app\common\service\report;

use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use think\facade\Db;

class PlatformExportService
{
    public static function exportOrders($params = [])
    {
        $scope = PlatformAnalyticsService::resolveScopeData($params);
        $range = $scope['range'];
        $payStatus = intval($params['pay_status'] ?? -1);
        $timeField = $payStatus === 1 ? 'pay_time' : 'create_time';

        $query = PlatformAnalyticsService::createOrderBaseQuery($scope, $params)
            ->whereBetweenTime($timeField, $range['start_time'], $range['end_time'])
            ->field('id,order_no,merchant_id,member_id,pay_type,pay_status,status,total_num,total_price,pay_price,refund_price,create_time,pay_time')
            ->order('id', 'desc');

        $list = $query->select()->toArray();

        $merchantIds = array_values(array_unique(array_filter(array_map('intval', array_column($list, 'merchant_id')))));
        $memberIds = array_values(array_unique(array_filter(array_map('intval', array_column($list, 'member_id')))));

        $merchantMap = [];
        if (!empty($merchantIds)) {
            $merchantRows = Db::name('merchant')
                ->whereIn('id', $merchantIds)
                ->field('id,title')
                ->select()
                ->toArray();
            foreach ($merchantRows as $row) {
                $merchantMap[intval($row['id'])] = $row;
            }
        }

        $memberMap = [];
        if (!empty($memberIds)) {
            $memberRows = Db::name('member')
                ->whereIn('member_id', $memberIds)
                ->field('member_id,nickname,phone')
                ->select()
                ->toArray();
            foreach ($memberRows as $row) {
                $memberMap[intval($row['member_id'])] = $row;
            }
        }

        $rows = [
            ['订单号', '商家', '买家昵称', '买家手机', '支付方式', '支付状态', '订单状态', '商品数量', '订单金额', '支付金额', '退款金额', '创建时间', '支付时间'],
        ];

        foreach ($list as $item) {
            $merchant = $merchantMap[intval($item['merchant_id'] ?? 0)] ?? [];
            $member = $memberMap[intval($item['member_id'] ?? 0)] ?? [];
            $rows[] = [
                $item['order_no'] ?? '',
                $merchant['title'] ?? '',
                $member['nickname'] ?? '',
                $member['phone'] ?? '',
                MemberOrderModel::getPayType($item['pay_type'] ?? 0, 2),
                intval($item['pay_status'] ?? 0) === 1 ? '已支付' : '未支付',
                MemberOrderModel::getStatus($item['status'] ?? 0, 2),
                intval($item['total_num'] ?? 0),
                self::toFloat($item['total_price'] ?? 0),
                self::toFloat($item['pay_price'] ?? 0),
                self::toFloat($item['refund_price'] ?? 0),
                $item['create_time'] ?? '',
                $item['pay_time'] ?? '',
            ];
        }

        return [
            'filename' => 'platform_orders_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
        ];
    }

    public static function exportMerchants($params = [])
    {
        $scope = PlatformAnalyticsService::resolveScopeData($params);
        $range = $scope['range'];

        $merchantList = $scope['merchant_list'];
        $merchantIds = array_values(array_map('intval', array_column($merchantList, 'id')));

        $paidMap = [];
        if (!$scope['merchant_force_empty'] && !empty($merchantIds)) {
            $paidRows = PlatformAnalyticsService::createPaidCompletedQuery($scope, $params)
                ->whereBetweenTime('pay_time', $range['start_time'], $range['end_time'])
                ->field('merchant_id, COUNT(id) as paid_order_count, SUM(pay_price) as paid_amount')
                ->group('merchant_id')
                ->select()
                ->toArray();

            foreach ($paidRows as $row) {
                $paidMap[intval($row['merchant_id'])] = $row;
            }
        }

        $goodsMap = [];
        if (!$scope['merchant_force_empty'] && !empty($merchantIds)) {
            $goodsRows = GoodsModel::whereIn('merchant_id', $merchantIds)
                ->where('is_delete', 0)
                ->where('is_disable', 0)
                ->field('merchant_id, COUNT(id) as goods_count, SUM(CASE WHEN status = 1 AND stock > 0 THEN 1 ELSE 0 END) as on_sale_goods_count')
                ->group('merchant_id')
                ->select()
                ->toArray();
            foreach ($goodsRows as $row) {
                $goodsMap[intval($row['merchant_id'])] = $row;
            }
        }

        $rows = [
            ['商家ID', '商家名称', '联系人', '联系电话', '审核状态', '到期状态', '到期时间', '剩余天数', '商品数', '在售商品数', '支付订单数', '成交额'],
        ];

        foreach ($merchantList as $merchant) {
            $merchantId = intval($merchant['id']);
            $paid = $paidMap[$merchantId] ?? [];
            $goods = $goodsMap[$merchantId] ?? [];
            $rows[] = [
                $merchantId,
                $merchant['title'] ?? '',
                $merchant['name'] ?? '',
                $merchant['phone'] ?? '',
                MerchantModel::getAuthState($merchant['auth_state'] ?? 0, 2),
                $merchant['expire_status_title'] ?? '未设置期限',
                $merchant['expire_time'] ?? '',
                $merchant['days_left'] ?? '',
                intval($goods['goods_count'] ?? 0),
                intval($goods['on_sale_goods_count'] ?? 0),
                intval($paid['paid_order_count'] ?? 0),
                self::toFloat($paid['paid_amount'] ?? 0),
            ];
        }

        return [
            'filename' => 'platform_merchants_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
        ];
    }

    public static function exportRenewRecords($params = [])
    {
        $scope = PlatformAnalyticsService::resolveScopeData($params);
        $range = $scope['range'];

        $query = Db::name('merchant_renew_record')
            ->alias('r')
            ->leftJoin('merchant m', 'm.id = r.merchant_id')
            ->where('r.is_delete', 0)
            ->where('r.is_disable', 0)
            ->whereBetweenTime('r.create_time', $range['start_time'], $range['end_time'])
            ->field('r.merchant_id,r.renew_source,r.renew_months,r.renew_days,r.before_expire_time,r.after_expire_time,r.amount,r.remark,r.renew_remind_days,r.create_time,m.title as merchant_title')
            ->order('r.id', 'desc');

        if ($scope['merchant_force_empty']) {
            $query->where('r.id', 0);
        } elseif (!empty($scope['merchant_ids'])) {
            $query->whereIn('r.merchant_id', $scope['merchant_ids']);
        }

        $list = $query->select()->toArray();

        $rows = [
            ['商家ID', '商家名称', '续费来源', '续费月数', '续费天数', '续费前到期时间', '续费后到期时间', '续费金额', '提醒天数', '备注', '创建时间'],
        ];

        foreach ($list as $item) {
            $rows[] = [
                intval($item['merchant_id'] ?? 0),
                $item['merchant_title'] ?? '',
                $item['renew_source'] ?? '',
                intval($item['renew_months'] ?? 0),
                intval($item['renew_days'] ?? 0),
                $item['before_expire_time'] ?? '',
                $item['after_expire_time'] ?? '',
                self::toFloat($item['amount'] ?? 0),
                intval($item['renew_remind_days'] ?? 0),
                $item['remark'] ?? '',
                $item['create_time'] ?? '',
            ];
        }

        return [
            'filename' => 'platform_renew_records_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
        ];
    }

    public static function exportAnalytics($params = [])
    {
        $summary = PlatformAnalyticsService::summary($params);
        $trend = PlatformAnalyticsService::trend($params);
        $ranking = PlatformAnalyticsService::ranking($params);
        $alerts = PlatformAnalyticsService::alerts($params);

        $rows = [
            ['平台数据中心导出'],
            ['统计范围', $summary['range']['label'] ?? ''],
            [],
            ['概览指标', '值'],
        ];

        foreach (($summary['cards'] ?? []) as $key => $value) {
            $rows[] = [$key, $value];
        }

        $rows[] = [];
        $rows[] = ['成交趋势', '周期', '成交额', '支付订单数', '支付买家数'];
        foreach (($trend['trade_trend'] ?? []) as $item) {
            $rows[] = ['', $item['label'] ?? '', self::toFloat($item['amount'] ?? 0), intval($item['order_count'] ?? 0), intval($item['buyer_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['退款趋势', '周期', '退款金额', '退款订单数'];
        foreach (($trend['refund_trend'] ?? []) as $item) {
            $rows[] = ['', $item['label'] ?? '', self::toFloat($item['refund_amount'] ?? 0), intval($item['refund_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['商家增长趋势', '周期', '新增商家', '累计商家'];
        foreach (($trend['merchant_growth_trend'] ?? []) as $item) {
            $rows[] = ['', $item['label'] ?? '', intval($item['new_merchant_count'] ?? 0), intval($item['cumulative_merchant_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['续费趋势', '周期', '续费金额', '续费次数'];
        foreach (($trend['renew_trend'] ?? []) as $item) {
            $rows[] = ['', $item['label'] ?? '', self::toFloat($item['renew_amount'] ?? 0), intval($item['renew_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['热销商品 Top10', '商品ID', '商品名称', '销量', '成交额', '订单数'];
        foreach (($ranking['top_goods'] ?? []) as $item) {
            $rows[] = ['', intval($item['goods_id'] ?? 0), $item['title'] ?? '', intval($item['sale_num'] ?? 0), self::toFloat($item['sale_amount'] ?? 0), intval($item['order_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['商家成交 Top10', '商家ID', '商家名称', '成交额', '支付订单数', '买家数'];
        foreach (($ranking['top_merchants'] ?? []) as $item) {
            $rows[] = ['', intval($item['merchant_id'] ?? 0), $item['title'] ?? '', self::toFloat($item['paid_amount'] ?? 0), intval($item['paid_order_count'] ?? 0), intval($item['buyer_count'] ?? 0)];
        }

        $rows[] = [];
        $rows[] = ['异常预警', '商家ID', '商家名称', '级别', '内容', '指标'];
        foreach (($alerts['list'] ?? []) as $item) {
            $rows[] = ['', intval($item['merchant_id'] ?? 0), $item['merchant_title'] ?? '', $item['level'] ?? '', $item['message'] ?? '', $item['value'] ?? ''];
        }

        return [
            'filename' => 'platform_analytics_' . date('Ymd_His') . '.csv',
            'content' => self::buildCsv($rows),
        ];
    }

    private static function buildCsv($rows = [])
    {
        $content = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $line = [];
            foreach ($row as $column) {
                $value = self::sanitizeCsvCell($column);
                $value = str_replace('"', '""', $value);
                $line[] = '"' . $value . '"';
            }
            $content .= implode(',', $line) . "\r\n";
        }
        return $content;
    }

    private static function sanitizeCsvCell($value)
    {
        $stringValue = (string) $value;
        if (is_string($value) && preg_match('/^\s*[=+\-@]/u', $stringValue) === 1) {
            return "'" . $stringValue;
        }
        return $stringValue;
    }

    private static function toFloat($value)
    {
        return round(floatval($value), 2);
    }
}
