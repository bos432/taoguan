<?php

namespace app\common\service\merchant;

use app\common\model\member\MemberModel;
use app\common\model\merchant\MerchantModel;
use app\common\model\merchant\MerchantUserModel;
use app\common\service\system\MobileAdminAccessService;
use think\facade\Db;
use think\facade\Request;

class MerchantIdentityService
{
    private const SYNTHETIC_MER_USER_OFFSET = 900000000;
    private static $hasMerchantMemberSuperColumn = null;

    public static function virtualApis(): array
    {
        return [
            [
                'api_url' => 'api/merchant.Identity/list',
                'api_name' => '前台商家身份列表',
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
            [
                'api_url' => 'api/merchant.Identity/current',
                'api_name' => '前台当前商家身份',
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
            [
                'api_url' => 'api/merchant.Identity/switch',
                'api_name' => '前台切换商家身份',
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
            [
                'api_url' => 'api/merchant.Identity/permissions',
                'api_name' => '前台商家身份权限',
                'is_unauth' => 1,
                'is_disable' => 0,
            ],
        ];
    }

    public static function virtualApiInfo(string $apiUrl = ''): array
    {
        if ($apiUrl === '') {
            return [];
        }

        foreach (self::virtualApis() as $api) {
            if (($api['api_url'] ?? '') === $apiUrl) {
                return $api;
            }
        }

        return [];
    }

    public static function requestedMerUserId(): int
    {
        $headerValue = intval(Request::header('MerchantUserId', 0));
        if ($headerValue > 0) {
            return $headerValue;
        }

        return intval(Request::param('mer_user_id', 0));
    }

    public static function list(int $memberId = 0): array
    {
        $memberId = $memberId > 0 ? $memberId : member_id(true);
        return self::resolveIdentities($memberId);
    }

    public static function current(int $memberId = 0, int $merUserId = 0): array
    {
        $memberId = $memberId > 0 ? $memberId : member_id(true);
        $merUserId = $merUserId > 0 ? $merUserId : self::requestedMerUserId();
        $list = self::resolveIdentities($memberId);
        $identity = self::pickIdentity($list, $merUserId, false);

        return [
            'identity' => $identity,
            'permissions' => self::buildPermissionPayload($identity),
        ];
    }

    public static function switch(int $memberId = 0, int $merUserId = 0): array
    {
        $memberId = $memberId > 0 ? $memberId : member_id(true);
        if ($merUserId <= 0) {
            exception('请选择商家身份');
        }

        $list = self::resolveIdentities($memberId);
        $identity = self::pickIdentity($list, $merUserId, true);

        return [
            'identity' => $identity,
            'permissions' => self::buildPermissionPayload($identity),
        ];
    }

    public static function permissions(int $memberId = 0, int $merUserId = 0): array
    {
        return self::current($memberId, $merUserId)['permissions'] ?? self::buildPermissionPayload([]);
    }

    public static function buildPermissionCodes(array $merchantInfo = []): array
    {
        $codes = [];
        $isApproved = intval($merchantInfo['auth_state'] ?? 0) === 1;
        $isSuper = intval($merchantInfo['merchant_system_super'] ?? $merchantInfo['member_is_super'] ?? 0) === 1;

        if ($isApproved) {
            $codes[] = 'edit_profile';
            $codes[] = 'admin_manage_merchant';
            $codes[] = 'verify_order';
            $codes[] = 'view_stats';
            $codes[] = 'publish_product';
        }

        if ($isSuper) {
            $codes[] = 'verify_cross_merchant_order';
        }

        $codes = array_values(array_unique(array_filter($codes)));
        sort($codes);

        return $codes;
    }

    private static function resolveIdentities(int $memberId): array
    {
        if ($memberId <= 0) {
            return [];
        }

        $member = MemberModel::where('member_id', $memberId)
            ->field('member_id,username,phone')
            ->find();
        if (!$member) {
            return [];
        }

        $memberAccount = $member->toArray();
        $supportsMemberSuper = self::supportsMemberSuperFlag();
        $field = 'id,title,auth_state,member_id,is_disable,is_delete';
        if ($supportsMemberSuper) {
            $field .= ',member_is_super';
        }

        $merchantList = MerchantModel::where('member_id', $memberId)
            ->where('is_delete', 0)
            ->order('id', 'desc')
            ->field($field)
            ->select()
            ->toArray();

        if (empty($merchantList)) {
            return [];
        }

        $list = [];
        foreach ($merchantList as $merchant) {
            $adminUser = self::resolveMatchedAdminUser(intval($merchant['id'] ?? 0), $memberAccount);
            $list[] = self::buildIdentity($merchant, $adminUser);
        }

        return $list;
    }

    private static function resolveMatchedAdminUser(int $merchantId, array $memberAccount = []): array
    {
        if ($merchantId <= 0) {
            return [];
        }

        $candidates = [];
        foreach (['username', 'phone'] as $field) {
            $value = trim((string) ($memberAccount[$field] ?? ''));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }
        $candidates = array_values(array_unique($candidates));
        if (empty($candidates)) {
            return [];
        }

        $adminUser = MerchantUserModel::where('mer_id', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('is_admin', 1)
            ->where(function ($query) use ($candidates) {
                $query->whereIn('username', $candidates)
                    ->whereOr(function ($subQuery) use ($candidates) {
                        $subQuery->whereIn('phone', $candidates);
                    });
            })
            ->order('is_super', 'desc')
            ->order('mer_user_id', 'asc')
            ->field('mer_user_id,mer_id,username,phone,nickname,is_super,is_admin')
            ->find();

        return $adminUser ? $adminUser->toArray() : [];
    }

    private static function buildIdentity(array $merchant = [], array $adminUser = []): array
    {
        $merchantId = intval($merchant['id'] ?? 0);
        $merchantTitle = trim((string) ($merchant['title'] ?? ''));
        $authState = intval($merchant['auth_state'] ?? 0);
        $merUserId = intval($adminUser['mer_user_id'] ?? 0);
        if ($merUserId <= 0) {
            $merUserId = self::syntheticMerUserId($merchantId);
        }

        $merchantSystemSuper = intval($merchant['member_is_super'] ?? 0) === 1;
        if (!$merchantSystemSuper && !empty($adminUser)) {
            // 检查是否为真正的超管（基于 MobileAdminAccessService 的逻辑）
            $memberAccess = \app\common\service\system\MobileAdminAccessService::getAccessByMember([
                'member_id' => $merchant['member_id'] ?? 0,
                'username' => $merchant['username'] ?? '',
                'phone' => $merchant['phone'] ?? '',
            ]);
            $merchantSystemSuper = intval($memberAccess['is_admin_super'] ?? 0) === 1;
        }

        $permissionCodes = self::buildPermissionCodes([
            'auth_state' => $authState,
            'merchant_system_super' => $merchantSystemSuper ? 1 : 0,
        ]);

        return [
            'mer_user_id' => $merUserId,
            'merchant_id' => $merchantId,
            'identity_name' => $merchantTitle !== '' ? $merchantTitle : '商家身份',
            'merchant' => [
                'id' => $merchantId,
                'title' => $merchantTitle,
                'auth_state' => $authState,
                'auth_state_title' => MerchantModel::getAuthState($authState, 2),
            ],
            'merchant_user' => [
                'mer_user_id' => intval($adminUser['mer_user_id'] ?? 0),
                'username' => strval($adminUser['username'] ?? ''),
                'phone' => strval($adminUser['phone'] ?? ''),
                'nickname' => strval($adminUser['nickname'] ?? ''),
                'is_super' => $merchantSystemSuper ? 1 : 0,
                'is_admin' => intval($adminUser['is_admin'] ?? 1),
            ],
            'permission_codes' => $permissionCodes,
            'permissions' => self::buildPermissionMap($permissionCodes),
        ];
    }

    private static function pickIdentity(array $list = [], int $merUserId = 0, bool $strict = false): array
    {
        if (empty($list)) {
            if ($strict) {
                exception('当前账号暂无商家身份');
            }
            return [];
        }

        if ($merUserId > 0) {
            foreach ($list as $identity) {
                if (intval($identity['mer_user_id'] ?? 0) === $merUserId) {
                    return $identity;
                }
            }
        }

        if ($strict) {
            exception('商家身份不存在或已失效');
        }

        return self::pickDefaultIdentity($list);
    }

    private static function pickDefaultIdentity(array $list = []): array
    {
        if (empty($list)) {
            return [];
        }

        if (count($list) === 1) {
            return $list[0];
        }

        foreach ($list as $identity) {
            if (intval($identity['merchant_user']['is_super'] ?? 0) !== 1) {
                return $identity;
            }
        }

        return $list[0];
    }

    private static function buildPermissionPayload(array $identity = []): array
    {
        $permissionCodes = array_values(array_unique(array_filter($identity['permission_codes'] ?? [])));
        sort($permissionCodes);

        return [
            'permission_codes' => $permissionCodes,
            'permission_map' => self::buildPermissionMap($permissionCodes),
        ];
    }

    private static function buildPermissionMap(array $permissionCodes = []): array
    {
        $permissionCodes = array_values(array_unique(array_filter($permissionCodes)));
        $map = [
            'edit_profile' => 0,
            'admin_manage_merchant' => 0,
            'verify_order' => 0,
            'verify_cross_merchant_order' => 0,
            'view_stats' => 0,
            'publish_product' => 0,
        ];

        foreach ($permissionCodes as $code) {
            if (array_key_exists($code, $map)) {
                $map[$code] = 1;
            }
        }

        return $map;
    }

    private static function syntheticMerUserId(int $merchantId): int
    {
        return self::SYNTHETIC_MER_USER_OFFSET + max(0, $merchantId);
    }

    private static function supportsMemberSuperFlag(): bool
    {
        if (self::$hasMerchantMemberSuperColumn !== null) {
            return self::$hasMerchantMemberSuperColumn;
        }

        try {
            $dbName = Db::query('SELECT DATABASE() AS db_name');
            $dbName = strval($dbName[0]['db_name'] ?? '');
            if ($dbName === '') {
                self::$hasMerchantMemberSuperColumn = false;
                return false;
            }

            $rows = Db::query(
                "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'ya_merchant' AND COLUMN_NAME = 'member_is_super'",
                [$dbName]
            );
            self::$hasMerchantMemberSuperColumn = intval($rows[0]['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            self::$hasMerchantMemberSuperColumn = false;
        }

        return self::$hasMerchantMemberSuperColumn;
    }
}
