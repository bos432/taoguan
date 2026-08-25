<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\merchant\MerchantMemberBindingService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$merchant = Db::name('merchant')->where('member_id', '>', 0)->where('is_delete', 0)->find();
$memberId = intval(Db::name('member')->alias('m')
    ->leftJoin('merchant mer', 'mer.member_id=m.member_id AND mer.is_delete=0')
    ->whereNull('mer.id')->where('m.is_delete', 0)->value('m.member_id'));
$conflictMemberId = intval(Db::name('merchant')->where('id', '<>', intval($merchant['id'] ?? 0))
    ->where('member_id', '>', 0)->where('is_delete', 0)->value('member_id'));
if (!$merchant || $memberId <= 0 || $conflictMemberId <= 0) {
    throw new RuntimeException('No merchant binding fixtures');
}
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant member binding failed: {$message}");
};
$requestId = 'MERCHANT-BIND-' . bin2hex(random_bytes(5));
$context = [
    'request_id' => $requestId,
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 1,
    'reason' => 'integration binding',
];

Db::startTrans();
try {
    Db::name('merchant')->where('id', $merchant['id'])->update(['member_is_super' => 1]);
    $result = MerchantMemberBindingService::execute(intval($merchant['id']), $memberId, $context);
    $assert($result['changed'] === true && $result['member_id'] === $memberId, 'binding result returned');
    $updated = Db::name('merchant')->where('id', $merchant['id'])->find();
    $assert(intval($updated['member_id']) === $memberId, 'member binding changed');
    $assert(intval($updated['member_is_super']) === 0, 'super permission revoked on rebind');
    $logs = Db::name('merchant_authorization_log')->where('request_id', $requestId)->order('id')->select()->toArray();
    $assert(count($logs) === 2, 'binding and super revocation logged');
    $assert(intval($logs[0]['before_value']) === intval($merchant['member_id']) && intval($logs[0]['after_value']) === $memberId, 'binding values logged');
    $assert($logs[0]['reason'] === 'integration binding', 'binding reason logged');
    $duplicate = MerchantMemberBindingService::execute(intval($merchant['id']), $memberId, $context);
    $assert($duplicate === $result, 'duplicate request replays result');
    $assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 2, 'duplicate does not duplicate logs');

    $conflictRequestId = 'MERCHANT-BIND-CONFLICT-' . bin2hex(random_bytes(5));
    $conflict = false;
    try {
        MerchantMemberBindingService::execute(intval($merchant['id']), $conflictMemberId, array_merge($context, ['request_id' => $conflictRequestId]));
    } catch (Throwable $throwable) {
        $conflict = str_contains($throwable->getMessage(), '已绑定其他商家');
    }
    $assert($conflict, 'member binding conflict rejected');
    $assert(Db::name('business_operation_request')->where('request_id', $conflictRequestId)->count() === 0, 'conflict operation rolled back');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 0, 'fixtures rolled back');
echo "Merchant member binding integration passed: {$assertions} assertions\n";
