<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\merchant\MerchantIdentityService;
use app\common\service\system\MobileAdminAccessService;

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException("Permission characterization failed: {$message}");
    }
};

$emptyAccess = ['permissions' => []];
$assert(!MobileAdminAccessService::hasPermission($emptyAccess, ''), 'empty permission is denied');
$assert(!MobileAdminAccessService::hasPermission($emptyAccess, 'order_writeoff'), 'missing permission is denied');

$writeoffAccess = ['permissions' => ['order_writeoff' => 1]];
$writeoffUrls = MobileAdminAccessService::allowedApiUrls($writeoffAccess);
$assert(MobileAdminAccessService::hasPermission($writeoffAccess, 'order_writeoff'), 'explicit writeoff permission is allowed');
$assert(in_array(MobileAdminAccessService::API_URLS['order_list'], $writeoffUrls, true), 'writeoff grants order list');
$assert(in_array(MobileAdminAccessService::API_URLS['order_writeoff'], $writeoffUrls, true), 'writeoff grants write endpoint');
$assert(!in_array(MobileAdminAccessService::API_URLS['order_pay_auth'], $writeoffUrls, true), 'writeoff does not grant pay audit');
$assert(!in_array(MobileAdminAccessService::API_URLS['merchant_auth'], $writeoffUrls, true), 'writeoff does not grant merchant audit');

$merchantAuthUrls = MobileAdminAccessService::allowedApiUrls(['permissions' => ['merchant_auth' => 1]]);
$assert(in_array(MobileAdminAccessService::API_URLS['merchant_list'], $merchantAuthUrls, true), 'merchant audit grants merchant list');
$assert(in_array(MobileAdminAccessService::API_URLS['merchant_auth'], $merchantAuthUrls, true), 'merchant audit grants write endpoint');
$assert(!in_array(MobileAdminAccessService::API_URLS['order_list'], $merchantAuthUrls, true), 'merchant audit does not grant orders');

$unapproved = MerchantIdentityService::buildPermissionCodes(['auth_state' => 0, 'member_is_super' => 0]);
$approved = MerchantIdentityService::buildPermissionCodes(['auth_state' => 1, 'member_is_super' => 0]);
$super = MerchantIdentityService::buildPermissionCodes(['auth_state' => 1, 'member_is_super' => 1]);
$assert($unapproved === [], 'unapproved ordinary merchant has no permission codes');
$assert(in_array('edit_profile', $approved, true), 'approved merchant can edit profile');
$assert(in_array('verify_order', $approved, true), 'approved merchant can verify own orders');
$assert(!in_array('verify_cross_merchant_order', $approved, true), 'ordinary merchant cannot verify cross-merchant orders');
$assert(in_array('verify_cross_merchant_order', $super, true), 'merchant super receives cross-merchant permission code');

$controller = file_get_contents(dirname(__DIR__, 2) . '/app/api/controller/admin/MobileAdmin.php');
$middleware = file_get_contents(dirname(__DIR__, 2) . '/app/inspection/middleware/ApiVerifyMiddleware.php');
$assert(is_string($controller) && str_contains($controller, "assertPermission('merchant_auth')"), 'merchant audit endpoint enforces backend permission');
$assert(is_string($controller) && str_contains($controller, "assertPermission('order_pay_auth')"), 'pay audit endpoint enforces backend permission');
$assert(is_string($controller) && str_contains($controller, "assertPermission('order_writeoff')"), 'writeoff endpoint enforces backend permission');
$assert(is_string($middleware) && str_contains($middleware, 'RetCodeUtils::NO_PERMISSION'), 'inspection middleware has explicit permission denial');

echo "Permission characterization passed: {$assertions} assertions\n";
