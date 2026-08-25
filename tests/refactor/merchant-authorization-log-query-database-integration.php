<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\merchant\MerchantAuthorizationLogQueryService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$merchantId = intval(Db::name('merchant')->where('is_delete', 0)->value('id'));
if ($merchantId <= 0) throw new RuntimeException('No merchant fixture');
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant authorization log query failed: {$message}");
};
$requestId = 'MERCHANT-LOG-QUERY-' . bin2hex(random_bytes(5));

Db::startTrans();
try {
    Db::name('merchant_authorization_log')->insert([
        'merchant_id' => $merchantId, 'member_id' => 23,
        'authorization_type' => 'merchant_member_binding', 'before_value' => 0, 'after_value' => 23,
        'operator_type' => 'platform_admin', 'operator_id' => 1, 'source' => 'admin-next',
        'request_id' => $requestId, 'reason' => 'query evidence', 'create_time' => date('Y-m-d H:i:s'),
    ]);
    $data = MerchantAuthorizationLogQueryService::list($merchantId, '', 1, 20);
    $assert($data['count'] >= 1 && count($data['list']) >= 1, 'paginated logs returned');
    $row = $data['list'][0];
    $assert($row['action_title'] === '绑定会员', 'action title returned');
    $assert($row['before_title'] === '未绑定' && $row['after_title'] === '会员 #23', 'value titles returned');
    $assert($row['source_title'] === '平台后台' && $row['operator_title'] !== '', 'source and operator returned');
    $assert($row['reason'] === 'query evidence' && $row['request_id'] === $requestId, 'audit evidence returned');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 0, 'fixtures rolled back');
echo "Merchant authorization log query passed: {$assertions} assertions\n";
