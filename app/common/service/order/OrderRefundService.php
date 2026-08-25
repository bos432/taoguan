<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\cache\member\MemberOrderCache;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberOrderLogService;
use app\common\service\operation\BusinessOperationRequestService;

class OrderRefundService
{
    public static function request(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'member', intval($param['member_id'] ?? 0), strval($param['refund_reason_wap_explain'] ?? '')
        );
        $refundType = isset($param['refund_type']) ? intval($param['refund_type']) : 2;
        $refundPrice = $param['refund_price'] ?? null;
        $refundExplain = $param['refund_reason_wap_explain'] ?? null;
        $refundImageIds = isset($param['refund_reason_wap_imgs'])
            ? implode(',', array_column($param['refund_reason_wap_imgs'], 'file_id'))
            : null;

        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('refund.request', $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    $model::commit();
                    return true;
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该请求正在处理中，请勿重复提交' : '该请求已处理失败，请更换请求编号后重试');
            }

            $orders = $model->whereIn('id', $ids)
                ->when(intval($param['member_id'] ?? 0) > 0, static function ($query) use ($param) {
                    $query->where('member_id', intval($param['member_id']));
                })
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('success', 1))
                ->lock(true)
                ->select();
            if ($orders->isEmpty()) {
                exception('订单不存在，或当前状态不支持申请售后');
            }

            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::SERVICE_REQUESTED, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::SERVICE_REQUESTED), [
                    'refund_type' => $refundType,
                    'refund_price' => $refundPrice,
                    'refund_reason_wap_explain' => $refundExplain,
                    'refund_reason_wap_img_ids' => $refundImageIds,
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'refund_status' => $after['refund_status'],
                    'refund_type' => $refundType,
                    'refund_price' => $refundPrice,
                    'refund_reason_wap_explain' => $refundExplain,
                    'refund_reason_wap_img_ids' => $refundImageIds,
                    'status' => $after['status'],
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                MemberOrderLogService::add([
                    'title' => '买家提交申请售后',
                    'member_order_id' => $before['id'],
                    'role_type' => 3,
                    'create_uid' => intval($param['member_id'] ?? 0),
                ]);
                OrderBusinessEventService::record(OrderStateTransitionPolicy::SERVICE_REQUESTED, $before, $after, $context, [
                    'operation_request_id' => $operation['id'],
                    'amount' => floatval($refundPrice ?? 0),
                    'quantity' => intval($before['total_num'] ?? 0),
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
