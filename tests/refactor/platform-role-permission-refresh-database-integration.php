<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\cache\system\RoleCache;
use app\common\cache\system\UserCache;
use app\common\exception\PermissionDeniedException;
use app\common\service\permission\UnifiedPermissionContextService;
use app\common\service\system\RoleService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Platform role permission refresh failed: {$message}");
};
$viewMenuId = intval(Db::name('system_menu')->where('menu_url', 'admin/merchant.Merchant/list')->where('is_delete', 0)->value('menu_id'));
$bindMenuId = intval(Db::name('system_menu')->where('menu_url', 'admin/merchant.Merchant/memberBind')->where('is_delete', 0)->value('menu_id'));
if ($viewMenuId <= 0 || $bindMenuId <= 0) throw new RuntimeException('Merchant permission menus missing');

Db::startTrans();
try {
    $roleId = intval(Db::name('system_role')->insertGetId([
        'role_name' => 'Refactor minimum merchant role', 'is_disable' => 0, 'is_delete' => 0,
        'create_time' => date('Y-m-d H:i:s'),
    ]));
    $userId = intval(Db::name('system_user')->insertGetId([
        'nickname' => 'Refactor permission user',
        'username' => 'refactor-permission-' . bin2hex(random_bytes(4)),
        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
        'is_super' => 0, 'is_disable' => 0, 'is_delete' => 0,
        'create_time' => date('Y-m-d H:i:s'),
    ]));
    Db::name('system_user_attributes')->insert(['user_id' => $userId, 'role_id' => $roleId]);
    Db::name('system_role_menus')->insert(['role_id' => $roleId, 'menu_id' => $viewMenuId]);
    UserCache::del([$userId]);
    RoleCache::del([$roleId]);

    $initial = UnifiedPermissionContextService::forPlatformUser($userId);
    $assert(in_array('platform.merchant.view', $initial['permission_codes'], true), 'initial view permission returned');
    $assert(!in_array('platform.merchant.bind', $initial['permission_codes'], true), 'initial binding permission absent');

    RoleService::edit($roleId, ['menu_ids' => [$viewMenuId, $bindMenuId]]);
    $granted = UnifiedPermissionContextService::forPlatformUser($userId);
    $assert(in_array('platform.merchant.bind', $granted['permission_codes'], true), 'explicit binding permission granted');
    $assert(!in_array('platform.merchant.super_authorize', $granted['permission_codes'], true), 'unassigned super authorization absent');
    $assert($granted['permission_version'] !== $initial['permission_version'], 'grant changes permission version');

    RoleService::edit($roleId, ['menu_ids' => [$viewMenuId]]);
    $revoked = UnifiedPermissionContextService::forPlatformUser($userId);
    $assert(!in_array('platform.merchant.bind', $revoked['permission_codes'], true), 'binding permission revoked');
    $assert($revoked['permission_version'] !== $granted['permission_version'], 'revocation changes permission version');
    $denied = null;
    try {
        UnifiedPermissionContextService::assertAllowed($revoked, 'platform.merchant.bind');
    } catch (PermissionDeniedException $exception) {
        $denied = $exception;
    }
    $assert($denied instanceof PermissionDeniedException && $denied->getStatusCode() === 403, 'revoked permission denied immediately');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
} finally {
    if (isset($userId)) UserCache::del([$userId]);
    if (isset($roleId)) RoleCache::del([$roleId]);
}
$assert(Db::name('system_user')->where('username', 'like', 'refactor-permission-%')->count() === 0, 'fixtures rolled back');
echo "Platform role permission refresh passed: {$assertions} assertions\n";
