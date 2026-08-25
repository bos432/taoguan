<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\merchant\MerchantSuperAuthorizationService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$merchant = Db::name('merchant')->where('member_id', '>', 0)->where('auth_state', 1)
    ->where('is_disable', 0)->where('is_delete', 0)->find();
if (!$merchant) throw new RuntimeException('No grantable merchant fixture');
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant super authorization failed: {$message}");
};
$requestId = 'MERCHANT-SUPER-' . bin2hex(random_bytes(5));
$target = intval($merchant['member_is_super'] ?? 0) === 1 ? 0 : 1;
$context = ['request_id' => $requestId, 'source' => 'admin-next', 'operator_type' => 'platform_admin', 'operator_id' => 1, 'reason' => 'integration authorization'];

Db::startTrans();
try {
    $result = MerchantSuperAuthorizationService::execute([intval($merchant['id'])], $target, $context);
    $assert($result['member_is_super'] === $target, 'target state returned');
    $assert(in_array(intval($merchant['id']), $result['changed_ids'], true), 'changed merchant returned');
    $assert(intval(Db::name('merchant')->where('id', $merchant['id'])->value('member_is_super')) === $target, 'merchant state changed');
    $log = Db::name('merchant_authorization_log')->where('request_id', $requestId)->find();
    $assert($log && intval($log['before_value']) === intval($merchant['member_is_super']), 'before state logged');
    $assert(intval($log['after_value']) === $target && $log['reason'] === 'integration authorization', 'target and reason logged');
    $duplicate = MerchantSuperAuthorizationService::execute([intval($merchant['id'])], $target, $context);
    $assert($duplicate === $result, 'duplicate request replays result');
    $assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 1, 'duplicate does not duplicate log');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 0, 'fixtures rolled back');
echo "Merchant super authorization integration passed: {$assertions} assertions\n";
