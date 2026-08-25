<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\cache\inspection\InspectionRoleCache;
use app\common\cache\inspection\InspectionUserCache;
use app\common\cache\merchant\MerchantRoleCache;
use app\common\cache\merchant\MerchantUserCache;
use app\common\exception\PermissionDeniedException;
use app\common\service\inspection\InspectionRoleService;
use app\common\service\merchant\MerchantRoleService;
use app\common\service\permission\UnifiedPermissionContextService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Merchant/inspection role refresh failed: {$message}");
};

Db::startTrans();
try {
    $merchantId = intval(Db::name('merchant')->where('is_delete', 0)->where('is_disable', 0)->value('id'));
    $orderViewMenuId = intval(Db::name('merchant_menu')->where('menu_url', 'merchant/order.Order/list')->where('is_delete', 0)->value('menu_id'));
    if ($merchantId <= 0 || $orderViewMenuId <= 0) throw new RuntimeException('Merchant permission fixtures missing');
    $merchantRoleId = intval(Db::name('merchant_role')->insertGetId([
        'mer_id' => $merchantId, 'role_name' => 'Refactor merchant role',
        'is_disable' => 0, 'is_delete' => 0, 'create_time' => date('Y-m-d H:i:s'),
    ]));
    $merchantUserId = intval(Db::name('merchant_user')->insertGetId([
        'mer_id' => $merchantId, 'nickname' => 'Refactor merchant user',
        'username' => 'refactor-merchant-' . bin2hex(random_bytes(4)),
        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
        'is_super' => 0, 'is_admin' => 0, 'is_disable' => 0, 'is_delete' => 0,
        'create_time' => date('Y-m-d H:i:s'),
    ]));
    Db::name('merchant_user_attributes')->insert(['mer_user_id' => $merchantUserId, 'role_id' => $merchantRoleId]);
    MerchantUserCache::del([$merchantUserId]);
    MerchantRoleCache::del([$merchantRoleId]);
    $merchantInitial = UnifiedPermissionContextService::forMerchantUser($merchantUserId);
    $assert($merchantInitial['permission_codes'] === [], 'merchant starts without business permissions');
    $assert($merchantInitial['data_scope']['merchant_ids'] === [$merchantId], 'merchant scope isolated');
    Db::name('merchant_role_menus')->insert(['role_id' => $merchantRoleId, 'menu_id' => $orderViewMenuId]);
    MerchantRoleCache::del([$merchantRoleId]);
    MerchantRoleService::clearUserCacheByRoles([$merchantRoleId]);
    $merchantGranted = UnifiedPermissionContextService::forMerchantUser($merchantUserId);
    $assert(in_array('merchant.order.view', $merchantGranted['permission_codes'], true), 'merchant permission granted');
    $assert($merchantGranted['permission_version'] !== $merchantInitial['permission_version'], 'merchant grant changes version');
    Db::name('merchant_role_menus')->where('role_id', $merchantRoleId)->delete();
    MerchantRoleCache::del([$merchantRoleId]);
    MerchantRoleService::clearUserCacheByRoles([$merchantRoleId]);
    $merchantRevoked = UnifiedPermissionContextService::forMerchantUser($merchantUserId);
    $assert(!in_array('merchant.order.view', $merchantRevoked['permission_codes'], true), 'merchant permission revoked');
    $merchantDenied = null;
    try { UnifiedPermissionContextService::assertAllowed($merchantRevoked, 'merchant.order.view'); }
    catch (PermissionDeniedException $exception) { $merchantDenied = $exception; }
    $assert($merchantDenied instanceof PermissionDeniedException, 'merchant revocation denied immediately');
    $assert(MerchantRoleService::userRemove([$merchantRoleId], [$merchantUserId]) === 1, 'merchant role user removal uses correct key');

    $inspection = Db::name('inspection')->where('is_delete', 0)->where('is_disable', 0)->find();
    $inspectionId = intval($inspection['id'] ?? 0);
    $manageMenuId = intval(Db::name('inspection_menu')->where('menu_url', 'inspection/order.InspectionOrder/edit')->where('is_delete', 0)->value('menu_id'));
    if ($inspectionId <= 0 || $manageMenuId <= 0) throw new RuntimeException('Inspection permission fixtures missing');
    $inspectionRoleId = intval(Db::name('inspection_role')->insertGetId([
        'ins_id' => $inspectionId, 'role_name' => 'Refactor inspection role',
        'is_disable' => 0, 'is_delete' => 0, 'create_time' => date('Y-m-d H:i:s'),
    ]));
    $inspectionUserId = intval(Db::name('inspection_user')->insertGetId([
        'ins_id' => $inspectionId, 'nickname' => 'Refactor inspection user',
        'username' => 'refactor-inspection-' . bin2hex(random_bytes(4)),
        'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
        'is_super' => 0, 'is_admin' => 0, 'is_disable' => 0, 'is_delete' => 0,
        'create_time' => date('Y-m-d H:i:s'),
    ]));
    Db::name('inspection_user_attributes')->insert(['ins_user_id' => $inspectionUserId, 'role_id' => $inspectionRoleId]);
    InspectionUserCache::del([$inspectionUserId]);
    InspectionRoleCache::del([$inspectionRoleId]);
    $inspectionInitial = UnifiedPermissionContextService::forInspectionUser($inspectionUserId);
    $assert($inspectionInitial['permission_codes'] === [], 'inspection starts without business permissions');
    $assert($inspectionInitial['data_scope']['inspection_ids'] === [$inspectionId], 'inspection scope isolated');
    Db::name('inspection_role_menus')->insert(['role_id' => $inspectionRoleId, 'menu_id' => $manageMenuId]);
    InspectionRoleCache::del([$inspectionRoleId]);
    InspectionRoleService::clearUserCacheByRoles([$inspectionRoleId]);
    $inspectionGranted = UnifiedPermissionContextService::forInspectionUser($inspectionUserId);
    $assert(in_array('inspection.order.manage', $inspectionGranted['permission_codes'], true), 'inspection permission granted');
    $assert($inspectionGranted['permission_version'] !== $inspectionInitial['permission_version'], 'inspection grant changes version');
    Db::name('inspection_role_menus')->where('role_id', $inspectionRoleId)->delete();
    InspectionRoleCache::del([$inspectionRoleId]);
    InspectionRoleService::clearUserCacheByRoles([$inspectionRoleId]);
    $inspectionRevoked = UnifiedPermissionContextService::forInspectionUser($inspectionUserId);
    $assert(!in_array('inspection.order.manage', $inspectionRevoked['permission_codes'], true), 'inspection permission revoked');
    $inspectionDenied = null;
    try { UnifiedPermissionContextService::assertAllowed($inspectionRevoked, 'inspection.order.manage'); }
    catch (PermissionDeniedException $exception) { $inspectionDenied = $exception; }
    $assert($inspectionDenied instanceof PermissionDeniedException, 'inspection revocation denied immediately');
    $assert(InspectionRoleService::userRemove([$inspectionRoleId], [$inspectionUserId]) === 1, 'inspection role user removal uses correct key');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
} finally {
    if (isset($merchantUserId)) MerchantUserCache::del([$merchantUserId]);
    if (isset($merchantRoleId)) MerchantRoleCache::del([$merchantRoleId]);
    if (isset($inspectionUserId)) InspectionUserCache::del([$inspectionUserId]);
    if (isset($inspectionRoleId)) InspectionRoleCache::del([$inspectionRoleId]);
}
$merchantRoleSource = file_get_contents(dirname(__DIR__, 2) . '/app/common/service/merchant/MerchantRoleService.php');
$inspectionRoleSource = file_get_contents(dirname(__DIR__, 2) . '/app/common/service/inspection/InspectionRoleService.php');
$assert(substr_count($merchantRoleSource, 'self::clearUserCacheByRoles($ids);') === 2, 'merchant edit and delete invalidate associated users');
$assert(substr_count($inspectionRoleSource, 'self::clearUserCacheByRoles($ids);') === 2, 'inspection edit and delete invalidate associated users');
$assert(Db::name('merchant_user')->where('username', 'like', 'refactor-merchant-%')->count() === 0, 'merchant fixtures rolled back');
$assert(Db::name('inspection_user')->where('username', 'like', 'refactor-inspection-%')->count() === 0, 'inspection fixtures rolled back');
echo "Merchant/inspection role permission refresh passed: {$assertions} assertions\n";
