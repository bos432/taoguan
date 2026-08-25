<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberBillService;
use app\common\service\member\MemberOrderLogService;
use app\common\service\operation\BusinessOperationRequestService;
use InvalidArgumentException;
use think\facade\Db;

class WechatPaymentCallbackService
{
    public static function handleSuccess(array $message): array
    {
        $outTradeNo = trim((string) ($message['out_trade_no'] ?? ''));
        $transactionId = trim((string) ($message['transaction_id'] ?? ''));
        if ($outTradeNo === '' || $transactionId === '') {
            throw new InvalidArgumentException('微信支付回调缺少支付单号或交易号');
        }
        $context = [
            'request_id' => 'wechat:' . $transactionId,
            'source' => 'wechat',
            'operator_type' => 'system',
            'operator_id' => 0,
            'reason' => '微信支付成功回调',
        ];

        Db::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('payment.wechat_callback', $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    Db::commit();
                    return json_decode(strval($operation['result_json'] ?? '{}'), true) ?: ['processed' => 0, 'skipped' => 0];
                }
                exception('微信支付回调正在处理中');
            }

            $orders = MemberOrderModel::where('pay_common_on', $outTradeNo)
                ->where('is_delete', 0)
                ->where('is_disable', 0)
                ->lock(true)
                ->select();
            if ($orders->isEmpty()) {
                exception('微信支付订单不存在');
            }

            $processed = 0;
            $skipped = 0;
            $payTime = date('Y-m-d H:i:s');
            foreach ($orders as $order) {
                $before = $order->toArray();
                if (intval($before['pay_status'] ?? 0) === 1) {
                    $skipped++;
                    continue;
                }
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::WECHAT_PAID, $before)) {
                    exception('微信支付订单状态异常，请人工核对：' . strval($before['order_no'] ?? ''));
                }

                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::WECHAT_PAID), [
                    'pay_time' => $payTime,
                    'pay_price' => $before['total_price'],
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'pay_time' => $payTime,
                    'pay_price' => $before['total_price'],
                    'pay_status' => $after['pay_status'],
                    'status' => $after['status'],
                ]);
                MemberBillService::add([
                    'member_id' => $before['member_id'],
                    'title' => '购买商品',
                    'in_out' => 2,
                    'money' => $before['total_price'],
                    'bill_type_id' => 1,
                    'order_id' => $before['id'],
                    'trans_id' => $transactionId,
                ]);
                MemberOrderLogService::add([
                    'title' => '订单支付成功',
                    'member_order_id' => $before['id'],
                    'role_type' => 4,
                    'create_uid' => 0,
                ]);
                OrderBusinessEventService::record(OrderStateTransitionPolicy::WECHAT_PAID, $before, $after, $context, [
                    'operation_request_id' => $operation['id'],
                    'amount' => floatval($before['total_price'] ?? 0),
                    'quantity' => intval($before['total_num'] ?? 0),
                    'payload' => ['transaction_id' => $transactionId, 'out_trade_no' => $outTradeNo],
                    'occurred_at' => $payTime,
                ]);
                $processed++;
            }

            $result = ['processed' => $processed, 'skipped' => $skipped];
            BusinessOperationRequestService::complete(intval($operation['id']), $result);
            Db::commit();
            return $result;
        } catch (\Throwable $throwable) {
            Db::rollback();
            throw $throwable;
        }
    }
}
