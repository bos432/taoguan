<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (!$condition) throw new RuntimeException("Mobile permission refresh failed: {$message}");
};

$store = file_get_contents($root . '/zflUniApp/zflUniApp/store/common.js');
$login = file_get_contents($root . '/zflUniApp/zflUniApp/pages/my/login.vue');
$my = file_get_contents($root . '/zflUniApp/zflUniApp/pages/app/my.vue');
$app = file_get_contents($root . '/zflUniApp/zflUniApp/App.vue');

$assert(str_contains($store, 'permissionContext: cache.get(PERMISSION_CONTEXT_KEY, {}) || {}'), 'cached context hydrates global state');
$assert(str_contains($store, 'refreshPermissionContext({ commit })'), 'store exposes permission refresh action');
$assert(str_contains($store, '.merchantIdentityCurrent({})'), 'refresh uses authenticated current identity endpoint');
$assert(str_contains($store, 'commit("setPermissionContext", context);'), 'refresh replaces global context');
$assert(str_contains($store, 'cache.remove(PERMISSION_CONTEXT_KEY);'), 'logout and invalid login remove cached context');
$assert(str_contains($login, 'store.dispatch("hydrateLogin")'), 'explicit login refreshes context before redirect');
$assert(str_contains($app, 'store.dispatch("hydrateLogin")'), 'session restoration refreshes context on launch');
$assert(substr_count($my, 'store.dispatch("hydrateLogin")') >= 3, 'mobile login and cached session paths refresh context');
$assert(substr_count($my, '"setPermissionContext"') >= 2, 'identity current and switch replace context');
$assert(substr_count($my, 'permission_context') >= 2, 'identity responses consume unified context');

echo "Mobile permission context refresh passed: {$assertions} assertions\n";
