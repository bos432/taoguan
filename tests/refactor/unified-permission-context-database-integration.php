<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\exception\PermissionDeniedException;
use app\common\service\permission\UnifiedPermissionContextService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Unified permission context integration failed: {$message}");
};

$platform = UnifiedPermissionContextService::forPlatformUser(1);
$assert($platform['identity']['type'] === 'platform_user', 'platform identity returned');
$assert(in_array('platform_super', $platform['role_codes'], true), 'platform super role returned');
$assert(in_array('platform.order.writeoff', $platform['permission_codes'], true), 'platform permission mapped');
$assert($platform['data_scope']['type'] === 'all', 'platform data scope returned');
$assert(strlen($platform['permission_version']) === 20, 'platform permission version returned');

$merchantUserId = intval(Db::name('merchant_user')->where('is_delete', 0)->where('is_disable', 0)->value('mer_user_id'));
$merchant = UnifiedPermissionContextService::forMerchantUser($merchantUserId);
$assert($merchant['identity']['type'] === 'merchant_user', 'merchant identity returned');
$assert($merchant['data_scope']['type'] === 'merchant' && count($merchant['data_scope']['merchant_ids']) === 1, 'merchant data scope isolated');
$assert(!in_array('merchant.order.cross_verify', $merchant['permission_codes'], true), 'desktop merchant super cannot cross merchants');

$memberId = intval(Db::name('merchant')->where('member_id', '>', 0)->where('is_delete', 0)->value('member_id'));
$member = UnifiedPermissionContextService::forMember($memberId);
$assert($member['identity']['type'] === 'member', 'member identity returned');
$assert(in_array('member.order.view_own', $member['permission_codes'], true), 'member own-order permission returned');
$assert(intval($member['data_scope']['member_id']) === $memberId, 'member data scope returned');
$assert($member['permission_version'] === UnifiedPermissionContextService::forMember($memberId)['permission_version'], 'unchanged facts keep stable version');

UnifiedPermissionContextService::assertAllowed($member, 'member.order.view_own');
$assert(true, 'allowed permission accepted');
$denied = null;
try {
    UnifiedPermissionContextService::assertAllowed($member, 'platform.finance.ledger');
} catch (PermissionDeniedException $exception) {
    $denied = $exception;
}
$assert($denied instanceof PermissionDeniedException, 'missing permission denied');
$assert($denied?->getStatusCode() === 403, 'denial uses HTTP 403');
$assert($denied?->permission() === 'platform.finance.ledger', 'denial identifies permission');
$assert(PermissionDeniedException::BUSINESS_CODE === 40301 && PermissionDeniedException::ERROR_CODE === 'AUTH_FORBIDDEN', 'denial codes stable');
$response = (new \app\ExceptionHandle($app))->render(new \think\Request(), $denied);
$responseData = $response->getData();
$assert($response->getCode() === 403, 'rendered denial keeps HTTP 403');
$assert(intval($responseData['code'] ?? 0) === 40301 && ($responseData['error_code'] ?? '') === 'AUTH_FORBIDDEN', 'rendered denial codes stable');
$assert(($responseData['data']['permission'] ?? '') === 'platform.finance.ledger', 'rendered denial returns permission');

echo "Unified permission context integration passed: {$assertions} assertions\n";
