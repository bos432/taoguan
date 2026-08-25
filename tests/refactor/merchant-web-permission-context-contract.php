<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\permission\UnifiedPermissionContextService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant web permission context failed: {$message}");
};

$url = 'merchant/system.UserCenter/permissionContext';
$assert(in_array($url, config('merchant.menu_is_unauth', []), true), 'context endpoint requires login without a separate role grant');
$menus = Db::name('merchant_menu')->where('menu_url', $url)->where('is_delete', 0)->select()->toArray();
$assert(count($menus) === 1, 'context endpoint menu is unique');
$assert(intval($menus[0]['hidden'] ?? 0) === 1, 'context endpoint menu is hidden');
$assert(intval($menus[0]['is_unlogin'] ?? 0) === 0, 'context endpoint still requires login');

$apiSource = file_get_contents(dirname(__DIR__, 2) . '/zflMerchantWeb/src/api/system/user-center.js');
$storeSource = file_get_contents(dirname(__DIR__, 2) . '/zflMerchantWeb/src/store/modules/user.js');
$assert(str_contains($apiSource, 'export function permissionContext()'), 'merchant web exposes context API');
$assert(str_contains($storeSource, 'Promise.all([userInfoApi(), permissionContextApi()])'), 'login initialization fetches context with user info');
$assert(str_contains($storeSource, 'function refreshPermissionContext()'), 'merchant web exposes active context refresh');
foreach (['role_codes', 'permission_codes', 'permission_map', 'permission_version', 'data_scope'] as $field) {
    $assert(str_contains($storeSource, "user.{$field} = context.{$field}"), "merchant web stores {$field}");
}

$merchantUserId = intval(Db::name('merchant_user')->where('is_delete', 0)->where('is_disable', 0)->value('mer_user_id'));
if ($merchantUserId <= 0) throw new RuntimeException('Active merchant user fixture missing');
$context = UnifiedPermissionContextService::forMerchantUser($merchantUserId);
$assert(($context['identity']['type'] ?? '') === 'merchant_user', 'backend returns merchant identity');
$assert(strlen(strval($context['permission_version'] ?? '')) === 20, 'backend returns stable permission version');
$assert(!empty($context['data_scope']['merchant_ids'] ?? []), 'backend returns merchant data scope');

echo "Merchant web permission context passed: {$assertions} assertions\n";
