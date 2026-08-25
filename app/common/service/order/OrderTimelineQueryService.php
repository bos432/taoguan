<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\model\member\MemberOrderModel;
use app\common\model\order\OrderBusinessEventModel;
use think\facade\Db;

class OrderTimelineQueryService
{
    public static function byOrderId(int $orderId): array
    {
        $order = MemberOrderModel::where('id', $orderId)
            ->field('id,order_no,member_id,merchant_id,total_num,total_price,pay_price,pay_status,pay_type,status,refund_status,create_time,pay_time,delivery_time,receipt_time,success_time,refund_time,is_delete')
            ->find();
        if (!$order) {
            exception('订单不存在');
        }
        $order = $order->toArray();
        $events = OrderBusinessEventModel::where('member_order_id', $orderId)
            ->order(['occurred_at' => 'asc', 'id' => 'asc'])->select()->toArray();
        foreach ($events as &$event) {
            $event['before_status_title'] = self::statusTitle($event['before_status'] ?? null);
            $event['after_status_title'] = self::statusTitle($event['after_status'] ?? null);
            $event['payload'] = json_decode((string) ($event['payload_json'] ?? ''), true) ?: [];
            unset($event['payload_json']);
        }
        unset($event);

        $legacyLogs = Db::name('member_order_log')->where('member_order_id', $orderId)
            ->where('is_delete', 0)->order(['create_time' => 'asc', 'id' => 'asc'])
            ->field('id,title,content,role_type,create_uid,create_time')->select()->toArray();

        return [
            'order' => array_merge($order, [
                'status_title' => self::statusTitle($order['status'] ?? null),
            ]),
            'coverage' => empty($events) ? 'legacy_only' : (empty($legacyLogs) ? 'event' : 'hybrid'),
            'events' => $events,
            'legacy_logs' => $legacyLogs,
        ];
    }

    private static function statusTitle(mixed $status): string
    {
        return $status === null ? '状态缺失' : strval(MemberOrderModel::getStatus(intval($status), 2) ?? '未知状态');
    }
}
