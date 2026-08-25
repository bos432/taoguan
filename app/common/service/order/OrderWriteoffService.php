<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\cache\member\MemberOrderCache;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\goods\GoodsInventoryModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberOrderLogService;
use app\common\service\operation\BusinessOperationRequestService;

class OrderWriteoffService
{
    public static function writeoff(array $ids, array $param = []): bool
    {
        $model = new MemberOrderModel();
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy', 'system', intval(operate_user_id()), '订单核销'
        );

        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('order.writeoff', $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    $model::commit();
                    return boolval(json_decode(strval($operation['result_json'] ?? 'true'), true) ?? true);
                }
                exception(intval($operation['status'] ?? 0) === 0
                    ? '该请求正在处理中，请勿重复提交'
                    : '该请求已处理失败，请更换请求编号后重试');
            }

            $orders = $model->whereIn($model->getPk(), $ids)
                ->with(['detaileds'])
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->where('status', 1)
                ->select();
            foreach ($orders as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply(OrderStateTransitionPolicy::PICKED_UP, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                if ($order['pick_up_code'] != $param['pick_up_code']) {
                    exception('提货码错误');
                }

                $inventoryRows = self::buildInventoryRows($order);
                if ($inventoryRows !== []) {
                    GoodsInventoryModel::insertAll($inventoryRows);
                }
                $order->status = MemberOrderModel::getStatus('p_evaluate', 1);
                $order->delivery_time = datetime();
                MemberOrderLogService::add([
                    'title' => '用户已提货',
                    'member_order_id' => $order['id'],
                    'role_type' => 1,
                ]);
                $order->save();

                $after = array_merge($before, OrderStateTransitionPolicy::after(OrderStateTransitionPolicy::PICKED_UP), [
                    'delivery_time' => strval($order->delivery_time),
                ]);
                OrderBusinessEventService::record(OrderStateTransitionPolicy::PICKED_UP, $before, $after, $context, [
                    'operation_request_id' => $operation['id'],
                    'amount' => floatval($order['pay_price'] ?? $order['total_price'] ?? 0),
                    'quantity' => intval($order['total_num'] ?? 0),
                ]);
            }

            BusinessOperationRequestService::complete(intval($operation['id']), true);
            $model::commit();
        } catch (\Throwable $throwable) {
            $model::rollback();
            exception($throwable->getMessage());
        }

        MemberOrderCache::del($ids);
        return true;
    }

    private static function buildInventoryRows(MemberOrderModel $order): array
    {
        $rows = [];
        foreach ($order['detaileds'] as $detail) {
            $available = GoodsInventoryModel::query()
                ->where('goods_id', $detail['goods_id'])
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->sum('warehousing_num');
            if ($available < $detail['quantity']) {
                continue;
            }
            $merchantId = GoodsModel::query()->where('id', $detail['goods_id'])->value('merchant_id');
            $lastInventory = GoodsInventoryModel::query()
                ->where('goods_id', $detail['goods_id'])
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->order(['create_time' => 'desc'])
                ->field('id,setting_warehouse_id,setting_hall_id')
                ->find();
            $rows[] = [
                'merchant_id' => $merchantId,
                'goods_id' => $detail['goods_id'],
                'warehousing_num' => -$detail['quantity'],
                'setting_warehouse_id' => $lastInventory['setting_warehouse_id'] ?? null,
                'setting_hall_id' => $lastInventory['setting_hall_id'] ?? null,
                'inventory_type' => 2,
                'member_id' => $order['member_id'],
                'member_order_id' => $order['id'],
                'create_time' => datetime(),
                'create_uid' => operate_user_id(),
            ];
        }
        return $rows;
    }
}
