<?php

declare(strict_types=1);

namespace app\common\service\payment;

use app\common\model\finance\PaymentGatewayAttemptModel;

class PaymentGatewayAttemptQueryService
{
    public const STATUS_LABELS = [0 => '准备中', 1 => '请求中', 2 => '成功', 3 => '失败', 4 => '结果未知'];

    public static function list(array $params): array
    {
        $page = max(1, intval($params['page'] ?? 1));
        $limit = min(100, max(1, intval($params['limit'] ?? 20)));
        $query = PaymentGatewayAttemptModel::alias('a')
            ->leftJoin('member_order o', 'o.id=a.member_order_id')
            ->field('a.id,a.business_type,a.provider,a.merchant_request_no,a.provider_transaction_id,a.member_order_id,a.order_no,a.total_amount,a.amount,a.status,a.attempt_count,a.error_message,a.requested_at,a.completed_at,a.create_time,a.update_time,o.status as order_status,o.pay_status as order_pay_status');
        foreach (['status', 'business_type', 'provider'] as $field) {
            if (($params[$field] ?? '') !== '' && ($params[$field] ?? null) !== null) {
                $query->where('a.' . $field, $params[$field]);
            }
        }
        $keyword = trim(strval($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(static function ($subQuery) use ($keyword) {
                $value = '%' . $keyword . '%';
                $subQuery->whereLike('a.order_no', $value)
                    ->whereOr('a.merchant_request_no', 'like', $value)
                    ->whereOr('a.provider_transaction_id', 'like', $value);
            });
        }
        if (!empty($params['start_time'])) {
            $query->where('a.create_time', '>=', strval($params['start_time']));
        }
        if (!empty($params['end_time'])) {
            $query->where('a.create_time', '<=', strval($params['end_time']));
        }
        $count = (clone $query)->count();
        $rows = $query->order(['a.id' => 'desc'])->page($page, $limit)->select()->toArray();
        foreach ($rows as &$row) {
            $row['status_title'] = self::STATUS_LABELS[intval($row['status'])] ?? '未知';
            $row['business_type_title'] = self::businessTypeTitle(strval($row['business_type']));
            $row['needs_attention'] = in_array(intval($row['status']), [1, 3, 4], true);
        }
        unset($row);
        return ['list' => $rows, 'count' => $count, 'page' => $page, 'limit' => $limit];
    }

    public static function summary(): array
    {
        $statusCounts = PaymentGatewayAttemptModel::field('status,count(*) as total')->group('status')->select()->toArray();
        $businessCounts = PaymentGatewayAttemptModel::field('business_type,count(*) as total')->group('business_type')->select()->toArray();
        return [
            'total' => PaymentGatewayAttemptModel::count(),
            'attention' => PaymentGatewayAttemptModel::whereIn('status', [1, 3, 4])->count(),
            'status_counts' => array_column($statusCounts, 'total', 'status'),
            'business_counts' => array_column($businessCounts, 'total', 'business_type'),
        ];
    }

    public static function info(int $id): array
    {
        $row = PaymentGatewayAttemptModel::where('id', $id)->find();
        if (!$row) {
            exception('网关调用记录不存在');
        }
        $data = $row->toArray();
        $data['status_title'] = self::STATUS_LABELS[intval($data['status'])] ?? '未知';
        $data['business_type_title'] = self::businessTypeTitle(strval($data['business_type']));
        $data['request'] = self::decodeJson($data['request_json'] ?? null);
        $data['response'] = self::decodeJson($data['response_json'] ?? null);
        unset($data['request_json'], $data['response_json']);
        return $data;
    }

    private static function decodeJson(mixed $value): array
    {
        return json_decode(strval($value ?? ''), true) ?: [];
    }

    private static function businessTypeTitle(string $type): string
    {
        return ['prepayment' => '微信预下单', 'refund' => '微信退款'][$type] ?? $type;
    }
}
