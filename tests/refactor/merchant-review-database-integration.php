<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\merchant\MerchantReviewService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$merchant = Db::name('merchant')->where('auth_state', 0)->where('member_id', '>', 0)
    ->where('is_delete', 0)->find();
if (!$merchant) {
    throw new RuntimeException('No pending merchant fixture');
}
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant review failed: {$message}");
};
$requestId = 'MERCHANT-REVIEW-' . bin2hex(random_bytes(5));
$context = [
    'request_id' => $requestId,
    'source' => 'admin-next',
    'operator_type' => 'platform_admin',
    'operator_id' => 1,
    'reason' => 'integration approval',
];

Db::startTrans();
try {
    Db::name('merchant')->where('id', $merchant['id'])->update(['member_is_super' => 1]);
    $beforeUsers = Db::name('merchant_user')->where('mer_id', $merchant['id'])->where('is_delete', 0)->count();
    $result = MerchantReviewService::execute([intval($merchant['id'])], ['auth_state' => 1, 'auth_msg' => ''], $context);
    $assert($result['auth_state'] === 1 && $result['ids'] === [intval($merchant['id'])], 'approval result compatible');
    $approved = Db::name('merchant')->where('id', $merchant['id'])->find();
    $assert(intval($approved['auth_state']) === 1, 'merchant approved');
    $assert(intval($approved['member_id']) === intval($merchant['member_id']), 'member binding preserved');
    $assert(intval($approved['member_is_super']) === 1, 'member super permission preserved');
    $assert(Db::name('merchant_user')->where('mer_id', $merchant['id'])->where('is_delete', 0)->count() >= $beforeUsers, 'default merchant admin available');
    $log = Db::name('merchant_authorization_log')->where('request_id', $requestId)->find();
    $assert($log && $log['authorization_type'] === 'merchant_review' && intval($log['after_value']) === 1, 'approval audited');
    $duplicate = MerchantReviewService::execute([intval($merchant['id'])], ['auth_state' => 1, 'auth_msg' => ''], $context);
    $assert($duplicate === $result, 'duplicate request replays result');
    $assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 1, 'duplicate does not duplicate audit');

    $userCount = Db::name('merchant_user')->where('mer_id', $merchant['id'])->where('is_delete', 0)->count();
    Db::name('merchant')->where('id', $merchant['id'])->update(['auth_state' => 0]);
    $secondContext = array_merge($context, ['request_id' => 'MERCHANT-REREVIEW-' . bin2hex(random_bytes(5))]);
    MerchantReviewService::execute([intval($merchant['id'])], ['auth_state' => 1, 'auth_msg' => ''], $secondContext);
    $rereviewed = Db::name('merchant')->where('id', $merchant['id'])->find();
    $assert(intval($rereviewed['member_id']) === intval($merchant['member_id']) && intval($rereviewed['member_is_super']) === 1, 're-review preserves binding and super permission');
    $assert(Db::name('merchant_user')->where('mer_id', $merchant['id'])->where('is_delete', 0)->count() === $userCount, 're-review does not duplicate merchant admin');

    Db::name('merchant')->where('id', $merchant['id'])->update(['auth_state' => 0]);
    $rejectRequestId = 'MERCHANT-REJECT-' . bin2hex(random_bytes(5));
    MerchantReviewService::execute(
        [intval($merchant['id'])],
        ['auth_state' => 2, 'auth_msg' => 'integration rejected'],
        array_merge($context, ['request_id' => $rejectRequestId, 'reason' => 'rejection evidence'])
    );
    $rejected = Db::name('merchant')->where('id', $merchant['id'])->find();
    $assert(intval($rejected['auth_state']) === 2 && $rejected['auth_msg'] === 'integration rejected', 'rejection reason persisted');
    $assert(intval($rejected['member_id']) === intval($merchant['member_id']) && intval($rejected['member_is_super']) === 1, 'rejection preserves binding and super permission');
    $rejectLog = Db::name('merchant_authorization_log')->where('request_id', $rejectRequestId)->find();
    $assert($rejectLog && $rejectLog['reason'] === 'rejection evidence' && intval($rejectLog['after_value']) === 2, 'rejection audited with reason');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
}
$assert(Db::name('merchant_authorization_log')->where('request_id', $requestId)->count() === 0, 'fixtures rolled back');
echo "Merchant review integration passed: {$assertions} assertions\n";
