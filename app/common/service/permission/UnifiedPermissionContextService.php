<?php

declare(strict_types=1);

namespace app\common\service\permission;

use app\common\exception\PermissionDeniedException;
use app\common\model\member\MemberModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\merchant\MerchantIdentityService;
use app\common\service\merchant\MerchantUserService;
use app\common\service\system\MobileAdminAccessService;
use app\common\service\system\UserService;

class UnifiedPermissionContextService
{
    public const PLATFORM_PERMISSIONS = [
        'platform.merchant.view', 'platform.merchant.review', 'platform.order.view',
        'platform.order.payment_review', 'platform.order.writeoff', 'platform.order.refund',
        'platform.finance.ledger', 'platform.finance.gateway_attempt',
    ];
    public const MERCHANT_PERMISSIONS = [
        'merchant.profile.edit', 'merchant.order.view', 'merchant.order.verify',
        'merchant.order.cross_verify', 'merchant.stats.view', 'merchant.product.publish',
    ];
    public const MEMBER_PERMISSIONS = [
        'member.profile.view', 'member.order.view_own', 'member.order.create', 'member.order.refund_own',
    ];

    private const PLATFORM_URL_MAP = [
        'admin/merchant.Merchant/list' => 'platform.merchant.view',
        'admin/merchant.Merchant/auth' => 'platform.merchant.review',
        'admin/order.Order/list' => 'platform.order.view',
        'admin/order.Order/orderPayAuth' => 'platform.order.payment_review',
        'admin/order.Order/takeDelivery' => 'platform.order.writeoff',
        'admin/order.Order/orderRefundAuth' => 'platform.order.refund',
        'admin/report.MerchantPurchaseLedger/list' => 'platform.finance.ledger',
        'admin/report.PaymentGatewayAttempt/list' => 'platform.finance.gateway_attempt',
    ];
    private const MERCHANT_URL_MAP = [
        'merchant/merchant.Merchant/edit' => 'merchant.profile.edit',
        'merchant/order.Order/list' => 'merchant.order.view',
        'merchant/order.Order/takeDelivery' => 'merchant.order.verify',
        'merchant/report.Statistic/index' => 'merchant.stats.view',
        'merchant/goods.Goods/add' => 'merchant.product.publish',
    ];
    private const IDENTITY_CODE_MAP = [
        'edit_profile' => 'merchant.profile.edit',
        'verify_order' => 'merchant.order.verify',
        'verify_cross_merchant_order' => 'merchant.order.cross_verify',
        'view_stats' => 'merchant.stats.view',
        'publish_product' => 'merchant.product.publish',
    ];
    private const MOBILE_PERMISSION_MAP = [
        'merchant_view' => 'platform.merchant.view',
        'merchant_auth' => 'platform.merchant.review',
        'order_view' => 'platform.order.view',
        'order_pay_auth' => 'platform.order.payment_review',
        'order_writeoff' => 'platform.order.writeoff',
    ];

    public static function forPlatformUser(int $userId): array
    {
        $user = UserService::info($userId, true, false);
        $isSuper = intval($user['is_super'] ?? 0) === 1 || user_is_super($userId);
        $permissions = $isSuper
            ? self::PLATFORM_PERMISSIONS
            : self::permissionsFromUrls($user['roles'] ?? [], self::PLATFORM_URL_MAP);

        return self::context(
            ['type' => 'platform_user', 'id' => $userId, 'label' => strval($user['nickname'] ?? $user['username'] ?? '')],
            [],
            $isSuper ? ['platform_super'] : ['platform_user'],
            $permissions,
            ['type' => $isSuper ? 'all' : 'role', 'merchant_ids' => [], 'member_id' => 0]
        );
    }

    public static function forMerchantUser(int $merchantUserId): array
    {
        $user = MerchantUserService::info($merchantUserId, true, false);
        $merchantId = intval($user['mer_id'] ?? 0);
        $merchant = MerchantModel::where('id', $merchantId)->field('id,title,is_disable,is_delete,auth_state')->find();
        $merchant = $merchant ? $merchant->toArray() : [];
        $enabled = intval($user['is_disable'] ?? 0) === 0
            && intval($merchant['is_disable'] ?? 0) === 0
            && intval($merchant['is_delete'] ?? 0) === 0;
        $isSuper = intval($user['is_super'] ?? 0) === 1;
        $permissions = [];
        if ($enabled) {
            $permissions = $isSuper
                ? array_values(array_diff(self::MERCHANT_PERMISSIONS, ['merchant.order.cross_verify']))
                : self::permissionsFromUrls($user['roles'] ?? [], self::MERCHANT_URL_MAP);
        }
        $role = $isSuper ? 'merchant_owner' : (intval($user['is_admin'] ?? 0) === 1 ? 'merchant_manager' : 'merchant_employee');

        return self::context(
            ['type' => 'merchant_user', 'id' => $merchantUserId, 'label' => strval($user['nickname'] ?? $user['username'] ?? '')],
            self::merchantSummary($merchant),
            [$role],
            $permissions,
            ['type' => 'merchant', 'merchant_ids' => $merchantId > 0 ? [$merchantId] : [], 'member_id' => 0]
        );
    }

    public static function forMember(int $memberId, int $merchantUserId = 0): array
    {
        $member = MemberModel::where('member_id', $memberId)
            ->field('member_id,username,phone,nickname,is_disable,is_delete')->find();
        if (!$member) {
            exception('会员不存在：' . $memberId);
        }
        $member = $member->toArray();
        $identityPayload = MerchantIdentityService::current($memberId, $merchantUserId);
        $identity = $identityPayload['identity'] ?? [];
        $mobileAccess = MobileAdminAccessService::getAccessByMember($member);
        $permissions = self::MEMBER_PERMISSIONS;
        foreach ($identity['permission_codes'] ?? [] as $legacyCode) {
            if (isset(self::IDENTITY_CODE_MAP[$legacyCode])) {
                $permissions[] = self::IDENTITY_CODE_MAP[$legacyCode];
            }
        }
        foreach ($mobileAccess['permissions'] ?? [] as $legacyCode => $allowed) {
            if (intval($allowed) === 1 && isset(self::MOBILE_PERMISSION_MAP[$legacyCode])) {
                $permissions[] = self::MOBILE_PERMISSION_MAP[$legacyCode];
            }
        }
        $roles = ['member'];
        if (!empty($identity)) {
            $roles[] = in_array('verify_cross_merchant_order', $identity['permission_codes'] ?? [], true)
                ? 'merchant_super'
                : 'merchant_owner';
        }
        if (!empty($mobileAccess['has_any_permission'])) {
            $roles[] = 'mobile_operator';
        }
        $merchant = $identity['merchant'] ?? [];
        $merchantId = intval($merchant['id'] ?? 0);

        return self::context(
            ['type' => 'member', 'id' => $memberId, 'label' => strval($member['nickname'] ?? $member['username'] ?? '')],
            $merchant,
            $roles,
            $permissions,
            [
                'type' => $merchantId > 0 ? 'member_and_merchant' : 'member',
                'merchant_ids' => $merchantId > 0 ? [$merchantId] : [],
                'member_id' => $memberId,
            ]
        );
    }

    public static function assertAllowed(array $context, string $permission): void
    {
        if ($permission === '' || !in_array($permission, $context['permission_codes'] ?? [], true)) {
            throw new PermissionDeniedException($permission);
        }
    }

    private static function context(array $identity, array $merchant, array $roles, array $permissions, array $dataScope): array
    {
        $roles = self::sorted($roles);
        $permissions = self::sorted($permissions);
        $payload = [
            'identity' => $identity,
            'merchant' => $merchant,
            'role_codes' => $roles,
            'permission_codes' => $permissions,
            'permission_map' => array_fill_keys($permissions, 1),
            'data_scope' => $dataScope,
        ];
        $payload['permission_version'] = substr(hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 20);
        return $payload;
    }

    private static function permissionsFromUrls(array $urls, array $map): array
    {
        $permissions = [];
        foreach ($urls as $url) {
            if (isset($map[$url])) {
                $permissions[] = $map[$url];
            }
        }
        return self::sorted($permissions);
    }

    private static function merchantSummary(array $merchant): array
    {
        return empty($merchant) ? [] : [
            'id' => intval($merchant['id'] ?? 0),
            'title' => strval($merchant['title'] ?? ''),
            'auth_state' => intval($merchant['auth_state'] ?? 0),
        ];
    }

    private static function sorted(array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map('strval', $values))));
        sort($values);
        return $values;
    }
}
