<?php

namespace app\common\service\system;

use app\common\model\merchant\MerchantModel;
use app\common\model\system\UserModel;

class MobileAdminAccessService
{
    public const MENU_URLS = [
        'merchant_list' => 'admin/merchant.Merchant/list',
        'merchant_auth' => 'admin/merchant.Merchant/auth',
        'order_list' => 'admin/order.Order/list',
        'order_pay_auth' => 'admin/order.Order/orderPayAuth',
        'order_writeoff' => 'admin/order.Order/takeDelivery',
    ];

    public const API_URLS = [
        'merchant_params' => 'api/admin.MobileAdmin/merchantParams',
        'merchant_list' => 'api/admin.MobileAdmin/merchantList',
        'merchant_info' => 'api/admin.MobileAdmin/merchantInfo',
        'merchant_auth' => 'api/admin.MobileAdmin/merchantAuth',
        'order_params' => 'api/admin.MobileAdmin/orderParams',
        'order_list' => 'api/admin.MobileAdmin/orderList',
        'order_pay_auth' => 'api/admin.MobileAdmin/orderPayAuth',
        'order_writeoff' => 'api/admin.MobileAdmin/orderWriteoff',
    ];

    public static function virtualApis(): array
    {
        return [
            ['api_url' => self::API_URLS['merchant_params'], 'api_name' => '移动端商家审核参数', 'is_disable' => 0],
            ['api_url' => self::API_URLS['merchant_list'], 'api_name' => '移动端商家审核列表', 'is_disable' => 0],
            ['api_url' => self::API_URLS['merchant_info'], 'api_name' => '移动端商家审核详情', 'is_disable' => 0],
            ['api_url' => self::API_URLS['merchant_auth'], 'api_name' => '移动端商家审核操作', 'is_disable' => 0],
            ['api_url' => self::API_URLS['order_params'], 'api_name' => '移动端订单审核参数', 'is_disable' => 0],
            ['api_url' => self::API_URLS['order_list'], 'api_name' => '移动端订单审核列表', 'is_disable' => 0],
            ['api_url' => self::API_URLS['order_pay_auth'], 'api_name' => '移动端订单支付审核', 'is_disable' => 0],
            ['api_url' => self::API_URLS['order_writeoff'], 'api_name' => '移动端订单核销', 'is_disable' => 0],
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

    public static function decorateMemberInfo(array $member = []): array
    {
        if (empty($member)) {
            return $member;
        }

        $access = self::getAccessByMember($member);
        $member['admin_user_id'] = intval($access['user_id'] ?? 0);
        $member['admin_user_username'] = $access['username'] ?? '';
        $member['is_admin_user_super'] = intval($access['is_admin_super'] ?? 0);
        $member['is_mobile_admin_enabled'] = intval($access['is_admin_super'] ?? 0);
        $member['is_admin_super'] = intval($access['is_admin_super'] ?? 0);
        
        // 只有超管才有管理权限，普通商家权限清空
        if (intval($access['is_admin_super'] ?? 0) !== 1) {
            $member['admin_permissions'] = [
                'merchant_view' => 0,
                'merchant_auth' => 0,
                'order_view' => 0,
                'order_pay_auth' => 0,
                'order_writeoff' => 0,
            ];
        } else {
            $member['admin_permissions'] = $access['permissions'] ?? [];
        }

        $allowedApiUrls = self::allowedApiUrls($access);
        if (isset($member['api_urls']) && is_array($member['api_urls'])) {
            $member['api_urls'] = self::mergeUrls($member['api_urls'], $allowedApiUrls);
        }
        if (isset($member['auth_api_urls']) && is_array($member['auth_api_urls'])) {
            $member['auth_api_urls'] = self::mergeUrls($member['auth_api_urls'], $allowedApiUrls);
        }

        return $member;
    }

    public static function decorateMerchant(array $merchant = []): array
    {
        if (empty($merchant)) {
            return $merchant;
        }

        $access = self::getAccessByMember([
            'username' => $merchant['username'] ?? '',
            'phone' => $merchant['phone'] ?? '',
        ]);

        $merchant['mobile_audit_enabled'] = !empty($access['has_any_permission']) ? 1 : 0;
        $merchant['mobile_audit_user_id'] = intval($access['user_id'] ?? 0);
        $merchant['mobile_audit_username'] = strval($access['username'] ?? '');
        $merchant['mobile_audit_permissions'] = $access['permissions'] ?? [];

        return $merchant;
    }

    public static function getAccessByMember(array $member = []): array
    {
        $access = [
            'user_id' => 0,
            'username' => '',
            'is_admin_user' => 0,
            'is_admin_super' => 0,
            'has_any_permission' => 0,
            'permissions' => [
                'merchant_view' => 0,
                'merchant_auth' => 0,
                'order_view' => 0,
                'order_pay_auth' => 0,
                'order_writeoff' => 0,
            ],
        ];

        if (self::isMerchantSuperMember($member)) {
            $access['username'] = strval($member['username'] ?? ($member['phone'] ?? ''));
            $access['is_admin_super'] = 1;
            $access['has_any_permission'] = 1;
            $access['permissions'] = [
                'merchant_view' => 1,
                'merchant_auth' => 1,
                'order_view' => 1,
                'order_pay_auth' => 1,
                'order_writeoff' => 1,
            ];

            return $access;
        }

        $systemUser = self::resolveUserByMember($member);
        if (empty($systemUser)) {
            return $access;
        }

        $access['user_id'] = intval($systemUser['user_id'] ?? 0);
        $access['username'] = strval($systemUser['username'] ?? '');
        $access['is_admin_user'] = 1;

        if (intval($systemUser['is_disable'] ?? 0) === 1) {
            return $access;
        }

        $userInfo = UserService::info($access['user_id'], true, false);
        $roles = array_values(array_filter($userInfo['roles'] ?? []));
        $isAdminSuper = user_is_super($access['user_id']) || intval($userInfo['is_super'] ?? 0) === 1;

        $permissions = [
            'merchant_view' => $isAdminSuper || in_array(self::MENU_URLS['merchant_list'], $roles, true),
            'merchant_auth' => $isAdminSuper || in_array(self::MENU_URLS['merchant_auth'], $roles, true),
            'order_view' => $isAdminSuper || in_array(self::MENU_URLS['order_list'], $roles, true),
            'order_pay_auth' => $isAdminSuper || in_array(self::MENU_URLS['order_pay_auth'], $roles, true),
            'order_writeoff' => $isAdminSuper || in_array(self::MENU_URLS['order_writeoff'], $roles, true),
        ];

        if ($permissions['merchant_auth']) {
            $permissions['merchant_view'] = 1;
        }
        if ($permissions['order_pay_auth'] || $permissions['order_writeoff']) {
            $permissions['order_view'] = 1;
        }

        $access['is_admin_super'] = $isAdminSuper ? 1 : 0;
        $access['permissions'] = array_map('intval', $permissions);
        $access['has_any_permission'] = array_sum($access['permissions']) > 0 ? 1 : 0;

        return $access;
    }

    public static function hasPermission(array $access = [], string $permission = ''): bool
    {
        if ($permission === '') {
            return false;
        }

        return intval($access['permissions'][$permission] ?? 0) === 1;
    }

    public static function allowedApiUrls(array $access = []): array
    {
        $urls = [];
        if (self::hasPermission($access, 'merchant_view') || self::hasPermission($access, 'merchant_auth')) {
            $urls[] = self::API_URLS['merchant_params'];
            $urls[] = self::API_URLS['merchant_list'];
            $urls[] = self::API_URLS['merchant_info'];
        }
        if (self::hasPermission($access, 'merchant_auth')) {
            $urls[] = self::API_URLS['merchant_auth'];
        }
        if (
            self::hasPermission($access, 'order_view')
            || self::hasPermission($access, 'order_pay_auth')
            || self::hasPermission($access, 'order_writeoff')
        ) {
            $urls[] = self::API_URLS['order_params'];
            $urls[] = self::API_URLS['order_list'];
        }
        if (self::hasPermission($access, 'order_pay_auth')) {
            $urls[] = self::API_URLS['order_pay_auth'];
        }
        if (self::hasPermission($access, 'order_writeoff')) {
            $urls[] = self::API_URLS['order_writeoff'];
        }

        return self::mergeUrls([], $urls);
    }

    private static function resolveUserByMember(array $member = []): array
    {
        $candidates = [];
        foreach (['username', 'phone', 'mobile'] as $field) {
            $value = trim((string) ($member[$field] ?? ''));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }
        $candidates = array_values(array_unique($candidates));
        if (empty($candidates)) {
            return [];
        }

        $user = UserModel::where('is_delete', 0)
            ->where(function ($query) use ($candidates) {
                $query->whereIn('username', $candidates)
                    ->whereOr(function ($subQuery) use ($candidates) {
                        $subQuery->whereIn('phone', $candidates);
                    });
            })
            ->order('is_disable', 'asc')
            ->order('is_super', 'desc')
            ->order('user_id', 'desc')
            ->field('user_id,username,phone,nickname,is_super,is_disable')
            ->find();

        return $user ? $user->toArray() : [];
    }

    private static function isMerchantSuperMember(array $member = []): bool
    {
        $memberId = intval($member['member_id'] ?? 0);
        $candidates = [];

        foreach (['username', 'phone', 'mobile'] as $field) {
            $value = trim((string) ($member[$field] ?? ''));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }

        try {
            $query = MerchantModel::where('is_delete', 0)
                ->where('member_is_super', 1)
                ->where('is_disable', 0);

            $query->where(function ($where) use ($memberId, $candidates) {
                $matched = false;
                if ($memberId > 0) {
                    $where->whereOr('member_id', '=', $memberId);
                    $matched = true;
                }
                if (!empty($candidates)) {
                    $where->whereOr(function ($subQuery) use ($candidates) {
                        $subQuery->whereIn('username', $candidates)
                            ->whereOr(function ($phoneQuery) use ($candidates) {
                                $phoneQuery->whereIn('phone', $candidates);
                            });
                    });
                    $matched = true;
                }
                if (!$matched) {
                    $where->whereRaw('1 = 0');
                }
            });

            $merchant = $query->find();
            return $merchant !== null && intval($merchant['member_is_super'] ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function mergeUrls(array $baseUrls = [], array $extraUrls = []): array
    {
        $urls = array_values(array_filter(array_unique(array_merge($baseUrls, $extraUrls))));
        sort($urls);
        return $urls;
    }
}
