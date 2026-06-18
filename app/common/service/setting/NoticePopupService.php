<?php

namespace app\common\service\setting;

use app\common\model\merchant\MerchantModel;
use app\common\model\setting\NoticeModel;
use app\common\model\setting\NoticeReadModel;
use app\common\service\member\MemberService;
use app\common\service\system\MobileAdminAccessService;
use think\facade\Cache;

class NoticePopupService
{
    private const READ_CACHE_PREFIX = 'notice_popup_read:';

    public static function virtualApis(): array
    {
        return [
            [
                'api_url' => 'api/setting.Notice/popupInfo',
                'api_name' => '弹窗公告信息',
                'is_unlogin' => 1,
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
            [
                'api_url' => 'api/setting.Notice/popupRead',
                'api_name' => '弹窗公告已读',
                'is_unlogin' => 1,
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
        ];
    }

    public static function virtualApiInfo(string $apiUrl = ''): array
    {
        foreach (self::virtualApis() as $api) {
            if (($api['api_url'] ?? '') === $apiUrl) {
                return $api;
            }
        }
        return [];
    }

    public static function popupInfo(string $clientKey = '', int $memberId = 0): array
    {
        if (!NoticeService::hasSettingNoticeTable() || !NoticeService::supportsPopupFields()) {
            return [];
        }

        $clientKey = self::normalizeClientKey($clientKey);

        $now = datetime();
        try {
            $list = NoticeModel::where('is_delete', 0)
                ->where('is_disable', 0)
                ->where('popup_enabled', 1)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->order(['popup_sort' => 'desc', 'sort' => 'desc', 'notice_id' => 'desc'])
                ->field('notice_id,image_id,type,popup_enabled,popup_frequency,popup_scope,popup_sort,popup_button_text,popup_jump_type,popup_jump_value,content_type,title,title_color,start_time,end_time,desc,content,remark,sort,create_time,update_time')
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($list as $item) {
            if (!self::matchScope($item, $memberId)) {
                continue;
            }
            if (!self::shouldPopup($item, $memberId, $clientKey)) {
                continue;
            }

            $notice = NoticeService::info(intval($item['notice_id']), true);
            $notice['popup_enabled'] = intval($notice['popup_enabled'] ?? 0);
            $notice['popup_frequency'] = strval($notice['popup_frequency'] ?? 'once');
            $notice['popup_scope'] = strval($notice['popup_scope'] ?? 'all');
            $notice['popup_button_text'] = trim((string) ($notice['popup_button_text'] ?? '')) ?: '查看详情';
            $notice['popup_jump_type'] = strval($notice['popup_jump_type'] ?? 'detail');
            $notice['popup_jump_value'] = strval($notice['popup_jump_value'] ?? '');
            $notice['content_type'] = strval($notice['content_type'] ?? 'html');

            return $notice;
        }

        return [];
    }

    public static function markRead(int $noticeId = 0, string $clientKey = '', int $memberId = 0, string $readType = 'popup_close'): array
    {
        if ($noticeId <= 0) {
            exception('请选择公告');
        }

        $clientKey = self::normalizeClientKey($clientKey);
        if ($clientKey === '' && $memberId <= 0) {
            exception('缺少设备标识');
        }

        if (!NoticeService::hasSettingNoticeReadTable()) {
            self::markReadByCache($noticeId, $memberId, $clientKey);
            return [
                'notice_id' => $noticeId,
                'member_id' => max(0, intval($memberId)),
                'client_key' => $clientKey,
                'read_type' => $readType ?: 'popup_close',
            ];
        }

        $read = new NoticeReadModel();
        $read->save([
            'notice_id' => $noticeId,
            'member_id' => max(0, intval($memberId)),
            'client_key' => $clientKey,
            'read_type' => $readType ?: 'popup_close',
            'read_date' => date('Y-m-d'),
            'create_time' => datetime(),
        ]);

        return [
            'notice_id' => $noticeId,
            'member_id' => max(0, intval($memberId)),
            'client_key' => $clientKey,
            'read_type' => $readType ?: 'popup_close',
        ];
    }

    private static function shouldPopup(array $notice = [], int $memberId = 0, string $clientKey = ''): bool
    {
        $frequency = strval($notice['popup_frequency'] ?? 'once');
        if ($frequency === 'always') {
            return true;
        }
        if (!NoticeService::hasSettingNoticeReadTable()) {
            return !self::hasReadByCache(intval($notice['notice_id'] ?? 0), $memberId, $clientKey, $frequency);
        }

        $query = NoticeReadModel::where('notice_id', intval($notice['notice_id'] ?? 0));
        if ($memberId > 0) {
            $query->where('member_id', $memberId);
        } elseif ($clientKey !== '') {
            $query->where('client_key', $clientKey);
        } else {
            return true;
        }

        if ($frequency === 'daily') {
            $query->where('read_date', date('Y-m-d'));
        }

        return !$query->count();
    }

    private static function matchScope(array $notice = [], int $memberId = 0): bool
    {
        $scope = strval($notice['popup_scope'] ?? 'all');
        if ($scope === 'all') {
            return true;
        }
        if ($scope === 'login') {
            return $memberId > 0;
        }
        if ($memberId <= 0) {
            return false;
        }

        if ($scope === 'merchant') {
            return MerchantModel::where('member_id', $memberId)
                ->where('is_delete', 0)
                ->where('is_disable', 0)
                ->count() > 0;
        }

        if ($scope === 'audit_admin') {
            $member = MemberService::getMemberInfo($memberId, false, 'member_id,username,phone,nickname');
            if (empty($member)) {
                return false;
            }
            $access = MobileAdminAccessService::getAccessByMember($member);
            return intval($access['has_any_permission'] ?? 0) === 1;
        }

        return true;
    }

    private static function normalizeClientKey(string $clientKey = ''): string
    {
        return substr(trim($clientKey), 0, 64);
    }

    private static function buildReadIdentity(int $memberId = 0, string $clientKey = ''): string
    {
        if ($memberId > 0) {
            return 'member:' . intval($memberId);
        }

        $clientKey = self::normalizeClientKey($clientKey);
        if ($clientKey !== '') {
            return 'client:' . sha1($clientKey);
        }

        return '';
    }

    private static function buildReadCacheKey(int $noticeId = 0, string $identity = '', string $frequency = 'once'): string
    {
        $noticeId = intval($noticeId);
        $identity = trim($identity);
        $frequency = strval($frequency ?: 'once');
        if ($noticeId <= 0 || $identity === '') {
            return '';
        }

        $suffix = $frequency;
        if ($frequency === 'daily') {
            $suffix .= ':' . date('Y-m-d');
        }

        return self::READ_CACHE_PREFIX . $noticeId . ':' . $identity . ':' . $suffix;
    }

    private static function hasReadByCache(int $noticeId = 0, int $memberId = 0, string $clientKey = '', string $frequency = 'once'): bool
    {
        $identity = self::buildReadIdentity($memberId, $clientKey);
        $key = self::buildReadCacheKey($noticeId, $identity, $frequency);
        if ($key === '') {
            return false;
        }

        return !!Cache::get($key);
    }

    private static function markReadByCache(int $noticeId = 0, int $memberId = 0, string $clientKey = ''): void
    {
        $identity = self::buildReadIdentity($memberId, $clientKey);
        if ($identity === '' || $noticeId <= 0) {
            return;
        }

        $frequency = 'once';
        $notice = NoticeService::info($noticeId, false);
        if (!empty($notice)) {
            $frequency = strval($notice['popup_frequency'] ?? 'once');
        }

        if ($frequency === 'always') {
            return;
        }

        $key = self::buildReadCacheKey($noticeId, $identity, $frequency);
        if ($key === '') {
            return;
        }

        $ttl = 31536000;
        if ($frequency === 'daily') {
            $ttl = strtotime(date('Y-m-d 23:59:59')) - time() + 1;
            if ($ttl <= 0) {
                $ttl = 60;
            }
        }

        Cache::set($key, 1, $ttl);
    }
}
