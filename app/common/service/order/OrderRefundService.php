<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\cache\member\MemberOrderCache;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberOrderLogService;
use app\common\service\member\MemberBillService;
use app\common\service\merchant\MerchantService;
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

    public static function reviewVoucher(int $id, array $param = [], int $source = 1): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'platform_admin', intval(operate_user_id()), self::reviewReason($param)
        );
        $refundStatus = intval($param['refund_status'] ?? 0);
        $refundType = intval($param['refund_type'] ?? 0);
        [$eventType, $operationType, $isRefund] = self::reviewTransition($refundStatus, $refundType);

        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin($operationType, $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    $model::commit();
                    return true;
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该请求正在处理中，请勿重复提交' : '该请求已处理失败，请更换请求编号后重试');
            }

            $order = $model->with(['detaileds.goods'])
                ->where('id', $id)
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('service', 1))
                ->where('pay_type', MemberOrderModel::getPayType('voucher', 1))
                ->lock(true)
                ->find();
            if (!$order) {
                exception('凭证支付售后订单不存在');
            }
            $before = $order->toArray();
            if (!OrderStateTransitionPolicy::canApply($eventType, $before)) {
                exception('售后状态已变化，请刷新后重试');
            }

            $refundPrice = isset($param['refund_price']) ? floatval($param['refund_price']) : floatval($before['refund_price'] ?? 0);
            $paidAmount = floatval($before['pay_price'] ?? 0) > 0 ? floatval($before['pay_price']) : floatval($before['total_price'] ?? 0);
            if ($refundPrice <= 0 || ($paidAmount > 0 && $refundPrice > $paidAmount)) {
                exception($refundPrice <= 0 ? '退款金额必须大于0' : '退款金额不能大于实际支付金额');
            }

            $save = [
                'refund_status' => $refundStatus,
                'refund_price' => $refundPrice,
                'update_time' => date('Y-m-d H:i:s'),
                'update_uid' => intval(operate_user_id()),
            ];
            if ($refundStatus === 2 && $refundType === 2) {
                $save['refund_consignee'] = $param['refund_consignee'] ?? '';
                $save['refund_phone'] = $param['refund_phone'] ?? '';
                $save['refund_address'] = $param['refund_address'] ?? '';
            } elseif ($refundStatus === 3) {
                $save['refund_reason'] = $param['refund_reason'] ?? '';
            }

            $logTitle = self::reviewReason($param);
            if ($isRefund) {
                $outRefundNo = MemberOrderModel::generateOrderNumber(1)[0];
                $save['status'] = MemberOrderModel::getStatus('refund', 1);
                $save['refund_time'] = date('Y-m-d H:i:s');
                $save['out_refund_no'] = $outRefundNo;
                $logTitle .= "，退款金额：{$refundPrice}，退款单号：{$outRefundNo}（凭证支付人工退款）";
                MemberBillService::add([
                    'member_id' => $before['member_id'],
                    'title' => '售后退款',
                    'in_out' => 1,
                    'money' => $refundPrice,
                    'bill_type_id' => 4,
                    'order_id' => $before['id'],
                    'remark' => '凭证支付售后退款',
                ]);
                self::deductMerchantRefund($before['detaileds'] ?? [], $refundPrice);
            }

            MemberOrderModel::where('id', $before['id'])->update($save);
            $after = array_merge($before, OrderStateTransitionPolicy::after($eventType), $save);
            MemberOrderLogService::add([
                'title' => $logTitle,
                'member_order_id' => $before['id'],
                'role_type' => $source,
                'create_uid' => intval(operate_user_id()),
            ]);
            OrderBusinessEventService::record($eventType, $before, $after, $context, [
                'operation_request_id' => $operation['id'],
                'amount' => $isRefund ? $refundPrice : 0,
                'quantity' => intval($before['total_num'] ?? 0),
            ]);
            BusinessOperationRequestService::complete(intval($operation['id']), true);
            $model::commit();
        } catch (\Throwable $throwable) {
            $model::rollback();
            throw $throwable;
        }

        MemberOrderCache::del($id);
        return true;
    }

    private static function reviewTransition(int $refundStatus, int $refundType): array
    {
        if ($refundStatus === 2 && $refundType === 1) {
            return [OrderStateTransitionPolicy::REFUNDED, 'refund.voucher_complete', true];
        }
        if ($refundStatus === 2 && $refundType === 2) {
            return [OrderStateTransitionPolicy::SERVICE_APPROVED, 'refund.return_approve', false];
        }
        if ($refundStatus === 3) {
            return [OrderStateTransitionPolicy::SERVICE_REJECTED, 'refund.reject', false];
        }
        if ($refundStatus === 4) {
            return [OrderStateTransitionPolicy::REFUNDED, 'refund.voucher_complete', true];
        }
        exception('请选择售后状态');
    }

    private static function reviewReason(array $param): string
    {
        $refundStatus = intval($param['refund_status'] ?? 0);
        $refundType = intval($param['refund_type'] ?? 0);
        return match (true) {
            $refundStatus === 2 && $refundType === 1 => '操作员同意退款',
            $refundStatus === 2 && $refundType === 2 => '操作员同意退货',
            $refundStatus === 3 => '操作员拒绝售后',
            $refundStatus === 4 => '操作员同意退货退款',
            default => '售后处理',
        };
    }

    private static function deductMerchantRefund(array $details, float $refundTotal): void
    {
        $eligible = array_values(array_filter($details, static fn(array $detail): bool => intval($detail['goods']['merchant_id'] ?? 0) > 0));
        $amountTotal = array_sum(array_map(static fn(array $detail): float => floatval($detail['total'] ?? 0), $eligible));
        $allocated = 0.0;
        $count = count($eligible);
        foreach ($eligible as $index => $detail) {
            $amount = $index === $count - 1
                ? round($refundTotal - $allocated, 2)
                : round($refundTotal * ($amountTotal > 0 ? floatval($detail['total'] ?? 0) / $amountTotal : 1 / max(1, $count)), 2);
            $allocated += $amount;
            MerchantService::consumption(
                $amount,
                '用户申请退款' . intval($detail['quantity'] ?? 0) . '件【' . strval($detail['goods']['title'] ?? '') . '】',
                intval($detail['id'] ?? 0),
                '',
                intval($detail['goods']['merchant_id']),
                false
            );
        }
    }
}
