<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\domain\order\OrderStateTransitionPolicy;
use app\common\model\file\MerchantFileModel;
use app\common\model\goods\GoodsImagesModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\finance\MerchantPurchaseLedgerService;
use app\common\service\member\MemberBillService;
use app\common\service\member\MemberOrderLogService;
use app\common\service\operation\BusinessOperationRequestService;
use think\facade\Db;

class VoucherPaymentReviewService
{
    public static function review(array $ids, array $param = []): array
    {
        if (!in_array(intval($param['pay_status'] ?? -1), [0, 1], true)) {
            exception('支付审核状态无效');
        }

        $model = new MemberOrderModel();
        $eventType = intval($param['pay_status'] ?? 0) === 1
            ? OrderStateTransitionPolicy::VOUCHER_APPROVED
            : OrderStateTransitionPolicy::VOUCHER_REJECTED;
        $operationType = intval($param['pay_status'] ?? 0) === 1
            ? 'payment.voucher_approve'
            : 'payment.voucher_reject';
        $context = $param['_operation_context'] ?? BusinessOperationContextFactory::fromRequest(
            'legacy',
            'system',
            intval(operate_user_id()),
            strval($param['pay_auth_msg'] ?? '')
        );
        $resultParam = $param;
        unset($resultParam['_operation_context']);

        $model::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin($operationType, $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    $model::commit();
                    $result = json_decode(strval($operation['result_json'] ?? ''), true);
                    return is_array($result) ? $result : $resultParam;
                }
                exception(intval($operation['status'] ?? 0) === 0
                    ? '该请求正在处理中，请勿重复提交'
                    : '该请求已处理失败，请更换请求编号后重试');
            }

            $orderList = $model->whereIn('id', $ids)
                ->where('is_disable', 0)
                ->where('is_delete', 0)
                ->where('pay_status', 0)
                ->where('pay_type', MemberOrderModel::getPayType('voucher', 1))
                ->when(isset($param['merchant_id']) && $param['merchant_id'], function ($query) use ($param) {
                    $query->where('merchant_id', $param['merchant_id']);
                })
                ->select();
            if (count($orderList) <= 0) {
                exception('未查询到符合条件的订单');
            }

            foreach ($orderList as $order) {
                $before = $order->toArray();
                if (!OrderStateTransitionPolicy::canApply($eventType, $before)) {
                    exception('订单状态已变化，请刷新后重试');
                }
                $eventAfter = $before;
                $eventAmount = 0;
                if (intval($param['pay_status']) === 1) {
                    $payTime = datetime();
                    $orderPayPrice = count($orderList) > 1
                        ? floatval($order['total_price'] ?? 0)
                        : floatval($param['pay_price'] ?? $order['total_price'] ?? 0);
                    $merchantId = MerchantModel::where('member_id', $order['member_id'])
                        ->where('is_disable', 0)
                        ->where('is_delete', 0)
                        ->where('auth_state', 1)
                        ->value('id');
                    if ($merchantId) {
                        self::transferPurchasedGoods($order->toArray(), intval($merchantId), $orderPayPrice, $payTime);
                    }
                    $model->where('id', $order['id'])->update([
                        'pay_time' => $payTime,
                        'pay_status' => 1,
                        'pay_price' => $orderPayPrice,
                        'status' => MemberOrderModel::getStatus('success', 1),
                    ]);
                    $eventAfter = array_merge($before, OrderStateTransitionPolicy::after($eventType), [
                        'pay_time' => $payTime,
                        'pay_price' => $orderPayPrice,
                    ]);
                    $eventAmount = $orderPayPrice;
                    MemberBillService::add([
                        'member_id' => $order['member_id'],
                        'title' => '购买商品',
                        'in_out' => 2,
                        'money' => $orderPayPrice,
                        'bill_type_id' => 4,
                        'order_id' => $order['id'],
                    ]);
                    MemberOrderLogService::add([
                        'title' => '凭证支付订单成功',
                        'member_order_id' => $order['id'],
                        'role_type' => 3,
                        'create_uid' => $order['member_id'],
                    ]);
                } else {
                    $model->where('id', $order['id'])->update([
                        'update_time' => datetime(),
                        'update_uid' => operate_user_id(),
                        'pay_auth_msg' => $param['pay_auth_msg'],
                        'pay_status' => 2,
                        'status' => MemberOrderModel::getStatus('cancel'),
                    ]);
                    $eventAfter = array_merge($before, OrderStateTransitionPolicy::after($eventType));
                    MemberOrderLogService::add([
                        'title' => '凭证支付订单驳回：' . $param['pay_auth_msg'],
                        'member_order_id' => $order['id'],
                        'role_type' => 3,
                        'create_uid' => operate_user_id(),
                    ]);
                }

                OrderBusinessEventService::record($eventType, $before, $eventAfter, $context, [
                    'operation_request_id' => $operation['id'],
                    'amount' => $eventAmount,
                    'quantity' => intval($order['total_num'] ?? 0),
                ]);
            }

            BusinessOperationRequestService::complete(intval($operation['id']), $resultParam);
            $model::commit();
        } catch (\Throwable $throwable) {
            $model::rollback();
            exception($throwable->getMessage());
        }

        return $resultParam;
    }

    private static function transferPurchasedGoods(array $order, int $merchantId, float $payPrice, string $payTime): void
    {
        $order['pay_price'] = $payPrice;
        MerchantPurchaseLedgerService::recordOrder($order, $merchantId, $payTime);
        $goodsList = MemberOrderDetailedModel::leftjoin('ya_goods', 'ya_goods.id=ya_member_order_detailed.goods_id')
            ->where('ya_member_order_detailed.member_order_id', $order['id'])
            ->field('ya_goods.*,ya_member_order_detailed.quantity')
            ->select()
            ->toArray();

        foreach ($goodsList as $goods) {
            $quantity = $goods['quantity'];
            $sourceGoodsId = $goods['id'];
            $goods['merchant_id'] = $merchantId;
            $goods['status'] = 1;
            $goods['sales_sum'] = 0;
            $goods['click_count'] = 0;
            $goods['stock'] = $quantity;
            unset($goods['quantity'], $goods['id']);
            $newGoodsId = GoodsModel::insertGetId($goods);

            $imageIds = GoodsImagesModel::where('goods_id', $sourceGoodsId)->column('image_id');
            $newImages = [];
            if (count($imageIds) > 0) {
                $files = Db::name('merchant_file')->whereIn('file_id', $imageIds)->select()->toArray();
                foreach ($files as $file) {
                    $file['mer_id'] = $merchantId;
                    unset($file['file_id']);
                    $newImages[] = [
                        'image_id' => MerchantFileModel::insertGetId($file),
                        'goods_id' => $newGoodsId,
                    ];
                }
            }
            if ($newImages !== []) {
                GoodsImagesModel::insertAll($newImages);
            }
        }
    }
}
