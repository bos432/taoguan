<?php
namespace app\common\service\finance;

use app\common\model\finance\MerchantPurchaseLedgerModel;
use app\common\model\merchant\MerchantModel;
use think\facade\Db;

class MerchantPurchaseLedgerService
{
    public static function recordOrder(array $order = [], int $buyerMerchantId = 0, string $payTime = ''): void
    {
        $orderId = intval($order['id'] ?? 0);
        if ($orderId <= 0 || $buyerMerchantId <= 0) {
            return;
        }

        $buyerMerchant = MerchantModel::where('id', $buyerMerchantId)->field('id,title')->find();
        $buyerMerchantTitle = (string) ($buyerMerchant['title'] ?? '');

        $rows = Db::name('member_order_detailed')
            ->alias('d')
            ->leftJoin('goods g', 'g.id = d.goods_id')
            ->leftJoin('merchant sm', 'sm.id = g.merchant_id')
            ->where('d.member_order_id', $orderId)
            ->field('d.id as detail_id,d.goods_id,d.quantity,d.price,d.total,g.title as goods_title,g.code as goods_code,g.spec as goods_spec,g.unit as goods_unit,g.merchant_id as source_merchant_id,sm.title as source_merchant_title')
            ->select()
            ->toArray();

        if (empty($rows)) {
            return;
        }

        $now = datetime();
        $insertRows = [];
        foreach ($rows as $row) {
            $detailId = intval($row['detail_id'] ?? 0);
            if ($detailId <= 0) {
                continue;
            }

            $sourceMerchantId = intval($row['source_merchant_id'] ?? 0);
            $sourceType = $sourceMerchantId > 0 ? 'merchant' : 'platform';
            $sourceMerchantTitle = $sourceMerchantId > 0 ? (string) ($row['source_merchant_title'] ?? '') : '平台自营';

            $insertRows[] = [
                'is_disable' => 0,
                'is_delete' => 0,
                'create_time' => $now,
                'update_time' => $now,
                'member_order_id' => $orderId,
                'member_order_detailed_id' => $detailId,
                'order_no' => (string) ($order['order_no'] ?? ''),
                'buyer_member_id' => intval($order['member_id'] ?? 0),
                'buyer_merchant_id' => $buyerMerchantId,
                'buyer_merchant_title' => $buyerMerchantTitle,
                'source_type' => $sourceType,
                'source_merchant_id' => $sourceMerchantId,
                'source_merchant_title' => $sourceMerchantTitle,
                'goods_id' => intval($row['goods_id'] ?? 0),
                'goods_title' => (string) ($row['goods_title'] ?? ''),
                'goods_code' => (string) ($row['goods_code'] ?? ''),
                'goods_spec' => (string) ($row['goods_spec'] ?? ''),
                'goods_unit' => (string) ($row['goods_unit'] ?? ''),
                'quantity' => intval($row['quantity'] ?? 0),
                'price' => round(floatval($row['price'] ?? 0), 2),
                'total' => round(floatval($row['total'] ?? 0), 2),
                'order_pay_price' => round(floatval($order['pay_price'] ?? 0), 2),
                'pay_type' => intval($order['pay_type'] ?? 0),
                'pay_time' => $payTime,
            ];
        }

        foreach ($insertRows as $insertRow) {
            $exists = MerchantPurchaseLedgerModel::where('member_order_detailed_id', $insertRow['member_order_detailed_id'])->value('id');
            if ($exists) {
                continue;
            }
            MerchantPurchaseLedgerModel::insert($insertRow);
        }
    }
}
