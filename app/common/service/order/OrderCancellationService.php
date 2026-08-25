<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\cache\member\MemberOrderCache;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberOrderLogService;
use app\common\service\operation\BusinessOperationRequestService;

class OrderCancellationService
{
    public static function cancel(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'member', intval($param['member_id'] ?? 0), '买家取消订单'
        );
        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('order.cancel', $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    $model::commit();
                    return true;
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该请求正在处理中，请勿重复提交' : '该请求已处理失败，请更换请求编号后重试');
            }
            $orders = $model->whereIn('id', $ids)->with(['detaileds'])
                ->when(intval($param['member_id'] ?? 0) > 0, static function ($query) use ($param) {
                    $query->where('member_id', intval($param['member_id']));
                })
                ->where('is_disable', 0)->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('p_pay', 1))
                ->where('pay_status', 0)->lock(true)->select();
            if ($orders->isEmpty()) {
                exception('订单不存在，或当前状态不支持取消');
            }

            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::CANCELED, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                foreach ($order['detaileds'] as $detail) {
                    GoodsModel::where('id', $detail['goods_id'])->inc('stock', intval($detail['quantity']))->update([
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                }
                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::CANCELED), [
                    'delete_uid' => intval($param['member_id'] ?? 0),
                    'delete_time' => date('Y-m-d H:i:s'),
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'is_delete' => 1,
                    'delete_uid' => $after['delete_uid'],
                    'delete_time' => $after['delete_time'],
                ]);
                MemberOrderLogService::add([
                    'title' => '买家取消订单',
                    'member_order_id' => $before['id'],
                    'role_type' => 3,
                    'create_uid' => intval($param['member_id'] ?? 0),
                ]);
                OrderBusinessEventService::record(OrderStateTransitionPolicy::CANCELED, $before, $after, $context, [
                    'operation_request_id' => $operation['id'],
                    'amount' => floatval($before['pay_price'] ?? 0),
                    'quantity' => intval($before['total_num'] ?? 0),
                    'payload' => ['compatibility_state' => 'soft_deleted_pending_payment'],
                ]);
            }
            BusinessOperationRequestService::complete(intval($operation['id']), true);
            $model::commit();
        } catch (\Throwable $throwable) {
            $model::rollback();
            throw $throwable;
        }

        MemberOrderCache::del($ids);
        return true;
    }
}
