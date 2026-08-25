<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\cache\inspection\InspectionUserCache;
use app\common\exception\PermissionDeniedException;
use app\common\service\permission\UnifiedPermissionContextService;
use think\facade\Db;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Inspection permission context failed: {$message}");
};
$inspectionUserSource = file_get_contents(dirname(__DIR__, 2) . '/app/common/service/inspection/InspectionUserService.php');
$assert(str_contains($inspectionUserSource, 'if (ins_user_is_super($id))'), 'inspection super helper used');
$assert(!str_contains($inspectionUserSource, 'if (user_is_super($id))'), 'platform super helper not used for inspection users');
$menu = Db::name('inspection_menu')->where('menu_url', 'inspection/system.UserCenter/permissionContext')
    ->where('is_delete', 0)->find();
$assert($menu && intval($menu['hidden']) === 1 && intval($menu['is_unlogin']) === 0, 'permission context menu is hidden and login-required');

$superId = intval(Db::name('inspection_user')->alias('u')->join('inspection i', 'i.id=u.ins_id')
    ->where('u.is_super', 1)->where('u.is_delete', 0)->where('i.is_disable', 0)->where('i.is_delete', 0)
    ->value('u.ins_user_id'));
if ($superId <= 0) throw new RuntimeException('No active inspection super fixture');
InspectionUserCache::del([$superId]);
$super = UnifiedPermissionContextService::forInspectionUser($superId);
$assert($super['identity']['type'] === 'inspection_user', 'inspection identity returned');
$assert(in_array('inspection_super', $super['role_codes'], true), 'inspection super role returned');
$assert(count($super['permission_codes']) === count(UnifiedPermissionContextService::INSPECTION_PERMISSIONS), 'inspection super permissions returned');
$assert($super['data_scope']['type'] === 'inspection' && count($super['data_scope']['inspection_ids']) === 1, 'inspection data scope isolated');

$ordinaryId = intval(Db::name('inspection_user')->alias('u')->join('inspection i', 'i.id=u.ins_id')
    ->where('u.ins_user_id', '<>', $superId)->where('u.is_delete', 0)
    ->where('i.is_disable', 0)->where('i.is_delete', 0)->value('u.ins_user_id'));
if ($ordinaryId <= 0) throw new RuntimeException('No inspection user fixture');
Db::startTrans();
try {
    Db::name('inspection_user')->where('ins_user_id', $ordinaryId)->update(['is_super' => 0]);
    Db::name('inspection_user_attributes')->where('ins_user_id', $ordinaryId)->delete();
    InspectionUserCache::del([$ordinaryId]);
    $ordinary = UnifiedPermissionContextService::forInspectionUser($ordinaryId);
    $assert(in_array('inspection_operator', $ordinary['role_codes'], true), 'ordinary inspection role returned');
    $assert($ordinary['permission_codes'] === [], 'unassigned inspection user has no mapped permissions');
    $denied = null;
    try {
        UnifiedPermissionContextService::assertAllowed($ordinary, 'inspection.order.manage');
    } catch (PermissionDeniedException $exception) {
        $denied = $exception;
    }
    $assert($denied instanceof PermissionDeniedException, 'unassigned inspection write denied');
    $assert($denied?->getStatusCode() === 403 && $denied?->permission() === 'inspection.order.manage', 'inspection denial uses unified contract');
    Db::rollback();
} catch (Throwable $throwable) {
    Db::rollback();
    throw $throwable;
} finally {
    InspectionUserCache::del([$ordinaryId]);
}
echo "Inspection unified permission context passed: {$assertions} assertions\n";
