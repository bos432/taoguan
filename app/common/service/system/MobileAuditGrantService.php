<?php

namespace app\common\service\system;

use app\common\model\merchant\MerchantModel;
use app\common\model\system\MenuModel;
use app\common\model\system\RoleModel;
use app\common\model\system\UserModel;

class MobileAuditGrantService
{
    public const ROLE_NAME = '移动审核权限';
    public const ROLE_DESC = '手机端商家入驻审核与订单审核/核销';

    public static function grant(array $merchantIds = []): array
    {
        $merchantIds = array_values(array_unique(array_filter(array_map('intval', $merchantIds))));
        if (empty($merchantIds)) {
            exception('请选择商家');
        }

        $merchantList = MerchantModel::whereIn('id', $merchantIds)
            ->where('is_delete', 0)
            ->field('id,title,username,phone,name,is_disable,member_id')
            ->select()
            ->toArray();
        if (empty($merchantList)) {
            exception('未找到可授权的商家');
        }

        $roleId = self::ensureRole();
        $result = [];
        foreach ($merchantList as $merchant) {
            $userResult = self::ensureUser($merchant);
            $result[] = self::ensureUserRole($userResult, $roleId, $merchant);
        }

        return [
            'role_id' => $roleId,
            'role_name' => self::ROLE_NAME,
            'list' => $result,
        ];
    }

    private static function ensureRole(): int
    {
        $menuUrlMap = MenuModel::where('menu_url', 'in', array_values(MobileAdminAccessService::MENU_URLS))
            ->where('is_delete', 0)
            ->column('menu_id', 'menu_url');

        $menuIds = [];
        $missingUrls = [];
        foreach (MobileAdminAccessService::MENU_URLS as $menuUrl) {
            if (!isset($menuUrlMap[$menuUrl])) {
                $missingUrls[] = $menuUrl;
                continue;
            }
            $menuIds[] = intval($menuUrlMap[$menuUrl]);
        }
        if (!empty($missingUrls)) {
            exception('缺少后台菜单权限：' . implode(', ', $missingUrls));
        }

        $roleId = intval(RoleModel::where('role_name', self::ROLE_NAME)->where('is_delete', 0)->value('role_id'));
        $payload = [
            'role_name' => self::ROLE_NAME,
            'role_desc' => self::ROLE_DESC,
            'remark' => '系统自动维护，请勿随意删除',
            'sort' => 240,
            'menu_ids' => array_values(array_unique($menuIds)),
            'is_disable' => 0,
        ];

        if ($roleId > 0) {
            RoleService::edit($roleId, $payload);
            return $roleId;
        }

        $data = RoleService::add($payload);
        return intval($data['role_id'] ?? 0);
    }

    private static function ensureUser(array $merchant = []): array
    {
        $candidateValues = [];
        foreach (['username', 'phone'] as $field) {
            $value = trim((string) ($merchant[$field] ?? ''));
            if ($value !== '') {
                $candidateValues[] = $value;
            }
        }
        $candidateValues = array_values(array_unique($candidateValues));

        if (!empty($candidateValues)) {
            $existingUser = UserModel::where('is_delete', 0)
                ->where(function ($query) use ($candidateValues) {
                    $query->whereIn('username', $candidateValues)
                        ->whereOr(function ($subQuery) use ($candidateValues) {
                            $subQuery->whereIn('phone', $candidateValues);
                        });
                })
                ->order('is_disable', 'asc')
                ->order('user_id', 'desc')
                ->field('user_id,username,phone,nickname,is_disable')
                ->find();
            if ($existingUser) {
                $user = $existingUser->toArray();
                $update = [];
                if (intval($user['is_disable'] ?? 0) === 1) {
                    $update['is_disable'] = 0;
                }
                if (empty(trim((string) ($user['phone'] ?? ''))) && !empty(trim((string) ($merchant['phone'] ?? '')))) {
                    $update['phone'] = trim((string) $merchant['phone']);
                }
                if (!empty($update)) {
                    UserService::edit(intval($user['user_id']), $update);
                }

                return [
                    'user_id' => intval($user['user_id']),
                    'created' => 0,
                    'password' => '',
                ];
            }
        }

        $username = trim((string) ($merchant['username'] ?? ''));
        if ($username === '') {
            $username = trim((string) ($merchant['phone'] ?? ''));
        }
        if ($username === '') {
            $username = 'merchant_' . intval($merchant['id'] ?? 0);
        }

        $nickname = self::resolveUniqueNickname(
            trim((string) ($merchant['title'] ?? '')),
            $username
        );
        $password = self::generatePassword();

        $user = UserService::add([
            'nickname' => $nickname,
            'username' => $username,
            'password' => $password,
            'phone' => trim((string) ($merchant['phone'] ?? '')),
            'remark' => '由商家管理自动授权手机审核权限',
            'sort' => 250,
        ]);

        return [
            'user_id' => intval($user['user_id'] ?? 0),
            'created' => 1,
            'password' => $password,
        ];
    }

    private static function ensureUserRole(array $userResult, int $roleId, array $merchant = []): array
    {
        $userId = intval($userResult['user_id'] ?? 0);
        if ($userId <= 0) {
            exception('后台用户创建失败');
        }

        $userInfo = UserService::info($userId, true);
        $roleIds = array_values(array_unique(array_merge(
            array_map('intval', $userInfo['role_ids'] ?? []),
            [$roleId]
        )));
        UserService::edit($userId, [
            'role_ids' => $roleIds,
            'is_disable' => 0,
        ]);

        $userInfo = UserService::info($userId, true);
        $merchant = MobileAdminAccessService::decorateMerchant($merchant);

        return [
            'merchant_id' => intval($merchant['id'] ?? 0),
            'merchant_title' => strval($merchant['title'] ?? ''),
            'mobile_audit_enabled' => intval($merchant['mobile_audit_enabled'] ?? 0),
            'user_id' => intval($userInfo['user_id'] ?? 0),
            'username' => strval($userInfo['username'] ?? ''),
            'nickname' => strval($userInfo['nickname'] ?? ''),
            'created' => intval($userResult['created'] ?? 0),
            'password' => strval($userResult['password'] ?? ''),
            'role_ids' => $roleIds,
        ];
    }

    private static function resolveUniqueNickname(string $merchantTitle = '', string $username = ''): string
    {
        $base = trim($merchantTitle) !== '' ? trim($merchantTitle) : ('审核员' . $username);
        $base = mb_substr($base, 0, 50, 'UTF-8');
        $candidate = $base;

        if (!self::nicknameExists($candidate)) {
            return $candidate;
        }

        $tail = $username !== '' ? '-' . substr($username, -4) : '';
        $candidate = mb_substr($base, 0, max(1, 50 - strlen($tail)), 'UTF-8') . $tail;
        if (!self::nicknameExists($candidate)) {
            return $candidate;
        }

        for ($i = 2; $i < 1000; $i++) {
            $suffix = '-' . $i;
            $candidate = mb_substr($base, 0, max(1, 50 - strlen($suffix)), 'UTF-8') . $suffix;
            if (!self::nicknameExists($candidate)) {
                return $candidate;
            }
        }

        return '审核员-' . time();
    }

    private static function nicknameExists(string $nickname = ''): bool
    {
        if ($nickname === '') {
            return false;
        }

        return UserModel::where('nickname', $nickname)->where('is_delete', 0)->count() > 0;
    }

    private static function generatePassword(): string
    {
        try {
            return substr(bin2hex(random_bytes(8)), 0, 12);
        } catch (\Throwable $exception) {
            return 'tg' . mt_rand(100000, 999999);
        }
    }
}
