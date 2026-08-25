<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\cache\system\UserCache;
use app\common\exception\PermissionDeniedException;
use app\common\service\permission\UnifiedPermissionContextService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant authorization menu failed: {$message}");
};
$urls = [
    'admin/merchant.Merchant/memberBind',
    'admin/merchant.Merchant/memberSuper',
    'admin/merchant.Merchant/authorizationLogs',
];
$parentId = intval(Db::name('system_menu')->where('menu_url', 'admin/merchant.Merchant/list')
    ->where('is_delete', 0)->value('menu_id'));
$menus = Db::name('system_menu')->whereIn('menu_url', $urls)->where('is_delete', 0)->select()->toArray();
$assert($parentId > 0 && count($menus) === 3, 'three authorization menus exist');
foreach ($menus as $menu) {
    $assert(intval($menu['menu_pid']) === $parentId, 'authorization menu uses merchant parent');
    $assert(intval($menu['hidden']) === 1 && intval($menu['is_unlogin']) === 0, 'authorization menu hidden and login-required');
}
$menuIds = array_map('intval', array_column($menus, 'menu_id'));
$assert(Db::name('system_role_menus')->whereIn('menu_id', $menuIds)->count() === 0, 'migration does not grant ordinary roles');

Db::startTrans();
try {
    $ordinaryId = intval(Db::name('system_user')->insertGetId([
        'nickname' => 'Refactor ordinary user',
        'username' => 'refactor-ordinary-' . bin2hex(random_bytes(4)),
        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
        'is_super' => 0,
        'is_disable' => 0,
        'is_delete' => 0,
        'create_time' => date('Y-m-d H:i:s'),
    ]));
    UserCache::del([$ordinaryId]);
    $ordinary = UnifiedPermissionContextService::forPlatformUser($ordinaryId);
    $assert(!in_array('platform.merchant.bind', $ordinary['permission_codes'], true), 'ordinary user not granted binding');
    $assert(!in_array('platform.merchant.super_authorize', $ordinary['permission_codes'], true), 'ordinary user not granted super authorization');
    $denied = null;
    try {
        UnifiedPermissionContextService::assertAllowed($ordinary, 'platform.merchant.bind');
    } catch (PermissionDeniedException $exception) {
        $denied = $exception;
    }
    $assert($denied instanceof PermissionDeniedException && $denied->getStatusCode() === 403, 'ordinary user denied with unified 403');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
} finally {
    if (isset($ordinaryId)) UserCache::del([$ordinaryId]);
}

$super = UnifiedPermissionContextService::forPlatformUser(1);
$assert(in_array('platform.merchant.bind', $super['permission_codes'], true), 'platform super can bind merchant member');
$assert(in_array('platform.merchant.super_authorize', $super['permission_codes'], true), 'platform super can authorize merchant super');
echo "Merchant authorization menu integration passed: {$assertions} assertions\n";
