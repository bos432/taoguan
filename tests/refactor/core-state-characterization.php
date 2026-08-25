<?php

declare(strict_types=1);

use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\merchant\MerchantService;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$failures = [];

$assertSame = static function (mixed $expected, mixed $actual, string $name) use (&$failures): void {
    if ($expected !== $actual) {
        $failures[] = sprintf('%s: expected %s, got %s', $name, var_export($expected, true), var_export($actual, true));
    }
};

$orderStates = [
    'p_pay' => [0, '待付款'],
    'p_shipment' => [1, '待发货'],
    'p_receipt' => [2, '待收货'],
    'p_evaluate' => [3, '待评价'],
    'success' => [4, '完成'],
    'service' => [5, '售后'],
    'refund' => [6, '已退款'],
    'cancel' => [7, '取消'],
];
foreach ($orderStates as $code => [$value, $label]) {
    $assertSame($value, MemberOrderModel::getStatus($code, 1), "order status {$code} value");
    $assertSame($label, MemberOrderModel::getStatus($value, 2), "order status {$code} label");
    $assertSame($code, MemberOrderModel::getStatus($value, 3), "order status {$code} code");
}

$assertSame(1, MemberOrderModel::getPayType('weChat', 1), 'wechat pay type');
$assertSame(2, MemberOrderModel::getPayType('voucher', 1), 'voucher pay type');
$assertSame(0, MerchantModel::getAuthState('wait', 1), 'merchant wait state');
$assertSame(1, MerchantModel::getAuthState('success', 1), 'merchant approved state');
$assertSame(2, MerchantModel::getAuthState('error', 1), 'merchant rejected state');
$assertSame(0, GoodsModel::getStatus('auth', 1), 'goods pending state');
$assertSame(1, GoodsModel::getStatus('auth_success', 1), 'goods approved state');
$assertSame(2, GoodsModel::getStatus('auth_error', 1), 'goods rejected state');

$merchantEditFields = array_keys(MerchantService::$edit_field);
$assertSame(false, in_array('member_id', $merchantEditFields, true), 'merchant member binding protected');
$assertSame(false, in_array('member_is_super', $merchantEditFields, true), 'merchant super flag protected');

if ($failures !== []) {
    fwrite(STDERR, "Core state characterization failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Core state characterization passed: ' . (count($orderStates) * 3 + 10) . " assertions\n";
