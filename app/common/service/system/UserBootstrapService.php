<?php

namespace app\common\service\system;

use app\common\service\runtime\RuntimeSnapshotService;

class UserBootstrapService
{
    public static function bootstrap(int $userId, string $group = 'ui', array $keys = []): array
    {
        $profile = UserCenterService::info($userId);
        $setting = SettingService::info('system_name,page_title,page_limit,logo_url,favicon_url,login_bg_url,login_bg_color,create_time,update_time');
        $preferences = UserPreferenceService::list($userId, $group, $keys);
        $snapshot = RuntimeSnapshotService::build('admin', $setting, $profile, UserPreferenceService::summary($userId, $group));
        $displayName = trim((string) ($profile['nickname'] ?? '')) ?: trim((string) ($profile['username'] ?? ''));

        return [
            'profile' => $profile,
            'profile_cache' => [
                'display_name' => $displayName,
                'username' => '' . ($profile['username'] ?? ''),
                'nickname' => '' . ($profile['nickname'] ?? ''),
                'avatar_url' => '' . ($profile['avatar_url'] ?? ''),
                'phone' => '' . ($profile['phone'] ?? ''),
                'email' => '' . ($profile['email'] ?? ''),
            ],
            'setting' => $setting,
            'setting_cache' => [
                'systemName' => '' . ($setting['system_name'] ?? ''),
                'pageTitle' => '' . ($setting['page_title'] ?? ''),
                'pageLimit' => '' . ($setting['page_limit'] ?? ''),
                'logoUrl' => '' . ($setting['logo_url'] ?? ''),
                'faviconUrl' => '' . ($setting['favicon_url'] ?? ''),
                'loginBgUrl' => '' . ($setting['login_bg_url'] ?? ''),
                'loginBgColor' => '' . ($setting['login_bg_color'] ?? ''),
            ],
            'preferences' => $preferences,
            'snapshot' => $snapshot,
            'supported_value_types' => ['json', 'string', 'int', 'float', 'bool'],
        ];
    }
}
