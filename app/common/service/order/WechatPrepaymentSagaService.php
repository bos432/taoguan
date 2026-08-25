<?php

declare(strict_types=1);

namespace app\common\service\order;

use app\common\gateway\payment\PrepaymentGatewayInterface;
use app\common\model\finance\PaymentGatewayAttemptModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\member\MemberShopCartModel;
use app\common\service\operation\BusinessOperationRequestService;
use app\common\service\payment\PaymentGatewayAttemptService;
use think\facade\Db;

class WechatPrepaymentSagaService
{
    public static function execute(
        array $attempt,
        array $operation,
        array $gatewayRequest,
        array $orderIds,
        array $param,
        PrepaymentGatewayInterface $gateway
    ): array {
        PaymentGatewayAttemptService::markRequesting(intval($attempt['id']));
        try {
            $result = $gateway->prepay($gatewayRequest);
        } catch (\Throwable $throwable) {
            PaymentGatewayAttemptService::fail(intval($attempt['id']), [
                'error' => $throwable->getMessage(), 'response' => [],
            ], true);
            throw new \RuntimeException('微信预下单结果未知，订单已保留并进入人工核对队列', 0, $throwable);
        }

        if (empty($result['success'])) {
            PaymentGatewayAttemptService::fail(intval($attempt['id']), $result);
            self::compensate($orderIds, $param);
            BusinessOperationRequestService::fail(intval($operation['id']), strval($result['error'] ?? '微信预下单失败'));
            exception(strval($result['error'] ?? '微信预下单失败'));
        }

        $bridgeConfig = is_array($result['bridge_config'] ?? null) ? $result['bridge_config'] : [];
        $result['response'] = [
            'gateway' => is_array($result['response'] ?? null) ? $result['response'] : [],
            'bridge_config' => $bridgeConfig,
        ];
        PaymentGatewayAttemptService::succeed(intval($attempt['id']), $result);
        try {
            BusinessOperationRequestService::complete(intval($operation['id']), $bridgeConfig);
        } catch (\Throwable $throwable) {
            PaymentGatewayAttemptService::fail(intval($attempt['id']), [
                'error' => '微信预下单成功，本地结果待补偿：' . $throwable->getMessage(),
                'response' => $result['response'],
            ], true);
            throw $throwable;
        }
        return $bridgeConfig;
    }

    public static function replayOrBlock(array $operation): mixed
    {
        if (intval($operation['status'] ?? 0) === 1) {
            return json_decode(strval($operation['result_json'] ?? 'true'), true);
        }
        $attempt = PaymentGatewayAttemptModel::where('operation_request_id', intval($operation['id']))->find();
        if ($attempt && intval($attempt['status']) === 2) {
            $response = json_decode(strval($attempt['response_json'] ?? ''), true) ?: [];
            $bridgeConfig = is_array($response['bridge_config'] ?? null) ? $response['bridge_config'] : [];
            BusinessOperationRequestService::complete(intval($operation['id']), $bridgeConfig);
            return $bridgeConfig;
        }
        if ($attempt && intval($attempt['status']) === 3) {
            exception('微信预下单已失败，请重新结算后再试');
        }
        exception('微信预下单结果待确认，请勿重复提交');
    }

    private static function compensate(array $orderIds, array $param): void
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return;
        }
        Db::startTrans();
        try {
            $details = MemberOrderDetailedModel::whereIn('member_order_id', $orderIds)->lock(true)->select()->toArray();
            $quantities = [];
            foreach ($details as $detail) {
                $goodsId = intval($detail['goods_id']);
                $quantities[$goodsId] = ($quantities[$goodsId] ?? 0) + intval($detail['quantity']);
            }
            foreach ($quantities as $goodsId => $quantity) {
                GoodsModel::where('id', $goodsId)->inc('stock', $quantity)->dec('sales_sum', $quantity)->update([
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            if (strval($param['source'] ?? '') === 'shop_cart' && !empty($param['goods_ids'])) {
                $goodsIds = is_array($param['goods_ids']) ? $param['goods_ids'] : explode(',', strval($param['goods_ids']));
                MemberShopCartModel::where('member_id', intval($param['member_id'] ?? 0))
                    ->whereIn('goods_id', array_map('intval', $goodsIds))->where('is_pay', 0)->update([
                        'is_delete' => 0, 'delete_time' => null, 'delete_uid' => 0,
                    ]);
            }
            Db::name('member_order_log')->whereIn('member_order_id', $orderIds)->delete();
            Db::name('order_business_event')->whereIn('member_order_id', $orderIds)->delete();
            MemberOrderDetailedModel::whereIn('member_order_id', $orderIds)->delete();
            MemberOrderModel::whereIn('id', $orderIds)->delete();
            Db::commit();
        } catch (\Throwable $throwable) {
            Db::rollback();
            throw new \RuntimeException('微信预下单失败且订单补偿未完成：' . $throwable->getMessage(), 0, $throwable);
        }
    }
}
