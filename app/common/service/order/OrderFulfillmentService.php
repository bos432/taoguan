<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\cache\member\MemberOrderCache;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\goods\GoodsInventoryModel;
use app\common\model\goods\GoodsModel;
use app\common\model\setting\SettingDeliveryModel;
use app\common\service\member\MemberOrderLogService;
use app\common\service\merchant\MerchantService;
use app\common\service\operation\BusinessOperationRequestService;

class OrderFulfillmentService
{
    public static function deliver(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'platform_admin', 0, '订单发货'
        );
        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('order.deliver', $context);
            if (self::returnDuplicate($operation, $model)) {
                return true;
            }
            $delivery = SettingDeliveryModel::where('id', intval($param['setting_delivery_id'] ?? 0))
                ->where('is_disable', 0)->where('is_delete', 0)->find();
            if (!$delivery) {
                exception('快递公司不存在或已禁用');
            }
            $orders = $model->whereIn('id', $ids)->with(['detaileds'])
                ->where('is_disable', 0)->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('p_shipment', 1))->lock(true)->select();
            if ($orders->isEmpty()) {
                exception('订单不存在，或当前状态不支持发货');
            }
            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::DELIVERED, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                self::recordInventoryOutbound($before, intval($context['operator_id'] ?? 0));
                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::DELIVERED), [
                    'kuaidi_order_no' => strval($param['kuaidi_order_no'] ?? ''),
                    'setting_delivery_id' => intval($delivery['id']),
                    'delivery_name' => strval($delivery['title']),
                    'delivery_code' => strval($delivery['code']),
                    'delivery_time' => date('Y-m-d H:i:s'),
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'status' => $after['status'],
                    'kuaidi_order_no' => $after['kuaidi_order_no'],
                    'setting_delivery_id' => $after['setting_delivery_id'],
                    'delivery_name' => $after['delivery_name'],
                    'delivery_code' => $after['delivery_code'],
                    'delivery_time' => $after['delivery_time'],
                ]);
                MemberOrderLogService::add([
                    'title' => '订单发货', 'member_order_id' => $before['id'], 'role_type' => 1,
                    'create_uid' => intval($context['operator_id'] ?? 0),
                ]);
                self::recordEvent(OrderStateTransitionPolicy::DELIVERED, $before, $after, $context, $operation);
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

    public static function receive(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'member', intval($param['member_id'] ?? 0), '买家确认收货'
        );
        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('order.receive', $context);
            if (self::returnDuplicate($operation, $model)) {
                return true;
            }
            $orders = $model->whereIn('id', $ids)
                ->when(intval($param['member_id'] ?? 0) > 0, static function ($query) use ($param) {
                    $query->where('member_id', intval($param['member_id']));
                })
                ->where('is_disable', 0)->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('p_receipt', 1))->lock(true)->select();
            if ($orders->isEmpty()) {
                exception('订单不存在，或当前状态不支持确认收货');
            }
            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::RECEIVED, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::RECEIVED), [
                    'receipt_time' => date('Y-m-d H:i:s'),
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'status' => $after['status'], 'receipt_time' => $after['receipt_time'],
                ]);
                MemberOrderLogService::add([
                    'title' => '买家已收货', 'member_order_id' => $before['id'], 'role_type' => 3,
                    'create_uid' => intval($param['member_id'] ?? 0),
                ]);
                self::recordEvent(OrderStateTransitionPolicy::RECEIVED, $before, $after, $context, $operation);
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

    public static function evaluate(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'member', intval($param['member_id'] ?? 0), '买家完成评价'
        );
        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('order.evaluate', $context);
            if (self::returnDuplicate($operation, $model)) {
                return true;
            }
            $orders = $model->whereIn('id', $ids)->with(['detaileds.goods'])
                ->when(intval($param['member_id'] ?? 0) > 0, static function ($query) use ($param) {
                    $query->where('member_id', intval($param['member_id']));
                })
                ->where('is_disable', 0)->where('is_delete', 0)
                ->where('status', MemberOrderModel::getStatus('p_evaluate', 1))->lock(true)->select();
            if ($orders->isEmpty()) {
                exception('订单不存在，或当前状态不支持评价');
            }
            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::EVALUATED, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                MemberOrderDetailedModel::where('member_order_id', $before['id'])->update([
                    'evaluate_content' => $param['evaluate_content'],
                    'evaluate_num' => $param['evaluate_num'],
                ]);
                if (intval($before['pay_type'] ?? 0) !== MemberOrderModel::getPayType('voucher', 1)) {
                    foreach ($before['detaileds'] ?? [] as $detail) {
                        if (intval($detail['goods']['merchant_id'] ?? 0) > 0) {
                            MerchantService::recharge(
                                intval($detail['goods']['merchant_id']),
                                floatval($detail['total'] ?? 0),
                                '用户购买' . intval($detail['quantity'] ?? 0) . '件【' . strval($detail['goods']['title'] ?? '') . '】',
                                intval($detail['id'])
                            );
                        }
                    }
                }
                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::EVALUATED), [
                    'success_time' => date('Y-m-d H:i:s'),
                ]);
                MemberOrderModel::where('id', $before['id'])->update([
                    'status' => $after['status'], 'success_time' => $after['success_time'],
                ]);
                MemberOrderLogService::add([
                    'title' => '买家完成评论', 'member_order_id' => $before['id'], 'role_type' => 3,
                    'create_uid' => intval($param['member_id'] ?? 0),
                ]);
                self::recordEvent(OrderStateTransitionPolicy::EVALUATED, $before, $after, $context, $operation);
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

    private static function returnDuplicate(array $operation, MemberOrderModel $model): bool
    {
        if (empty($operation['duplicate'])) {
            return false;
        }
        if (intval($operation['status'] ?? 0) === 1) {
            $model::commit();
            return true;
        }
        exception(intval($operation['status'] ?? 0) === 0 ? '该请求正在处理中，请勿重复提交' : '该请求已处理失败，请更换请求编号后重试');
    }

    private static function recordInventoryOutbound(array $order, int $operatorId): void
    {
        $inventorys = [];
        foreach ($order['detaileds'] ?? [] as $detail) {
            $rows = GoodsInventoryModel::where('goods_id', intval($detail['goods_id']))
                ->where('is_disable', 0)->where('is_delete', 0)
                ->order(['create_time' => 'desc', 'id' => 'desc'])->lock(true)->select()->toArray();
            $available = array_sum(array_map(static fn(array $row): int => intval($row['warehousing_num'] ?? 0), $rows));
            if ($available < intval($detail['quantity'])) {
                continue;
            }
            $last = $rows[0] ?? [];
            $inventorys[] = [
                'merchant_id' => intval(GoodsModel::where('id', intval($detail['goods_id']))->value('merchant_id')),
                'goods_id' => intval($detail['goods_id']),
                'warehousing_num' => -intval($detail['quantity']),
                'setting_warehouse_id' => $last['setting_warehouse_id'] ?? null,
                'setting_hall_id' => $last['setting_hall_id'] ?? null,
                'inventory_type' => 2,
                'member_id' => intval($order['member_id'] ?? 0),
                'member_order_id' => intval($order['id']),
                'create_time' => date('Y-m-d H:i:s'),
                'create_uid' => $operatorId,
            ];
        }
        if ($inventorys !== []) {
            GoodsInventoryModel::insertAll($inventorys);
        }
    }

    private static function recordEvent(string $eventType, array $before, array $after, array $context, array $operation): void
    {
        OrderBusinessEventService::record($eventType, $before, $after, $context, [
            'operation_request_id' => $operation['id'],
            'amount' => floatval($before['pay_price'] ?? $before['total_price'] ?? 0),
            'quantity' => intval($before['total_num'] ?? 0),
        ]);
    }
}
