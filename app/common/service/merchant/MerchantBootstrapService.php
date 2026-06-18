<?php

namespace app\common\service\merchant;

use app\common\service\runtime\RuntimeSnapshotService;

class MerchantBootstrapService
{
    public static function bootstrap(int $merUserId, string $group = 'ui', array $keys = []): array
    {
        $profile = MerchantUserCenterService::info($merUserId);
        $setting = MerchantSettingService::info('mer_system_name,mer_page_title,page_limit,logo_url,favicon_url,login_bg_url,login_bg_color,create_time,update_time');
        $preferences = MerchantUserPreferenceService::list($merUserId, $group, $keys);
        $snapshot = RuntimeSnapshotService::build('merchant', $setting, $profile, MerchantUserPreferenceService::summary($merUserId, $group));
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
                'systemName' => '' . ($setting['mer_system_name'] ?? ''),
                'pageTitle' => '' . ($setting['mer_page_title'] ?? ''),
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
