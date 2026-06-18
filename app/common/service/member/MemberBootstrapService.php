<?php

namespace app\common\service\member;

use app\common\service\runtime\RuntimeSnapshotService;
use app\common\service\setting\AccordRuntimeService;
use app\common\service\setting\SettingService as SiteSettingService;

class MemberBootstrapService
{
    public static function bootstrap(int $memberId, string $group = 'ui', array $keys = []): array
    {
        $profile = MemberService::getMemberInfo($memberId, true, 'member_id,avatar_id,nickname,username,phone,email,name,gender,create_time,update_time');
        unset($profile['is_disable'], $profile['is_delete']);
        $memberSetting = SettingService::info('default_avatar_url,token_type,token_name,create_time,update_time');
        $setting = SiteSettingService::info('system_name,page_title,logo_url,favicon_url,create_time,update_time');
        $accordRuntime = AccordRuntimeService::memberSummary($memberId);
        $preferences = MemberPreferenceService::list($memberId, $group, $keys);
        $snapshot = RuntimeSnapshotService::build(
            'member',
            ['system' => $setting, 'member_setting' => $memberSetting, 'accord_runtime' => $accordRuntime],
            $profile,
            MemberPreferenceService::summary($memberId, $group)
        );
        $displayName = trim((string) ($profile['nickname'] ?? '')) ?: trim((string) ($profile['username'] ?? ''));

        return [
            'profile' => $profile,
            'profile_cache' => [
                'display_name' => $displayName,
                'username' => '' . ($profile['username'] ?? ''),
                'nickname' => '' . ($profile['nickname'] ?? ''),
                'avatar_url' => '' . ($profile['avatar_url'] ?? ($memberSetting['default_avatar_url'] ?? '')),
                'phone' => '' . ($profile['phone'] ?? ''),
                'email' => '' . ($profile['email'] ?? ''),
            ],
            'member_setting' => $memberSetting,
            'setting' => $setting,
            'accord_runtime' => $accordRuntime,
            'setting_cache' => [
                'systemName' => '' . ($setting['system_name'] ?? ''),
                'pageTitle' => '' . ($setting['page_title'] ?? ''),
                'logoUrl' => '' . ($setting['logo_url'] ?? ''),
                'faviconUrl' => '' . ($setting['favicon_url'] ?? ''),
                'defaultAvatarUrl' => '' . ($memberSetting['default_avatar_url'] ?? ''),
            ],
            'preferences' => $preferences,
            'snapshot' => $snapshot,
            'supported_value_types' => ['json', 'string', 'int', 'float', 'bool'],
        ];
    }
}
