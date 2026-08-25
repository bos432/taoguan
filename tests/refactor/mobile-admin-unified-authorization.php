<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use app\common\service\member\ApiService;
use app\common\service\system\MobileAdminAccessService;

$app = new think\App();
$app->initialize();
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Mobile admin unified authorization failed: {$message}");
};

$virtualApis = MobileAdminAccessService::virtualApis();
$assert(count($virtualApis) === 8, 'all mobile admin APIs declared');
$assert(count(array_filter($virtualApis, static fn(array $api): bool => intval($api['is_unauth'] ?? 0) === 1)) === 8, 'all mobile admin APIs defer authorization to controller');
$unauthUrls = ApiService::unauthList('url');
foreach (MobileAdminAccessService::API_URLS as $url) {
    $assert(in_array($url, $unauthUrls, true), 'logged-in routing allows controller authorization for ' . $url);
}
$controller = file_get_contents(dirname(__DIR__, 2) . '/app/api/controller/admin/MobileAdmin.php');
$assert(str_contains($controller, 'UnifiedPermissionContextService::assertAllowed'), 'controller uses unified assertion');
$assert(!str_contains($controller, 'MobileAdminAccessService::hasPermission'), 'controller no longer checks legacy booleans directly');
$assert(str_contains($controller, "assertPermission('platform.merchant.review')"), 'merchant review uses stable code');
$assert(str_contains($controller, "assertPermission('platform.order.payment_review')"), 'payment review uses stable code');
$assert(str_contains($controller, "assertPermission('platform.order.writeoff')"), 'writeoff uses stable code');

echo "Mobile admin unified authorization passed: {$assertions} assertions\n";
