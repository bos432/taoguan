<?php

declare(strict_types=1);

namespace app\common\service\merchant;

use app\common\cache\merchant\MerchantCache;
use app\common\domain\operation\BusinessOperationContext;
use app\common\model\merchant\MerchantAuthorizationLogModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\operation\BusinessOperationRequestService;
use think\facade\Db;

class MerchantReviewService
{
    public static function execute(array $ids, array $param, array $context): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $targetState = intval($param['auth_state'] ?? 0);
        if ($ids === [] || !in_array($targetState, [1, 2], true)) {
            exception('商家或审核状态参数错误');
        }
        $context = BusinessOperationContext::normalize($context);

        Db::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin('merchant.review', $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    Db::commit();
                    return json_decode(strval($operation['result_json'] ?? '{}'), true) ?: [];
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该审核请求正在处理中' : '该审核请求已失败，请更换请求编号');
            }

            $merchants = MerchantModel::whereIn('id', $ids)->where('is_delete', 0)
                ->where('auth_state', 0)->lock(true)->select()->toArray();
            if ($merchants === []) {
                exception('No merchants matched the audit conditions');
            }

            $authMessage = strval($param['auth_msg'] ?? '');
            $now = date('Y-m-d H:i:s');
            foreach ($merchants as $merchant) {
                $merchantId = intval($merchant['id']);
                if ($targetState === 1) {
                    self::ensureDefaultMerchantAdmin($merchant);
                }
                $update = [
                    'auth_state' => $targetState,
                    'auth_uid' => intval($context['operator_id']),
                    'auth_time' => $now,
                    'update_uid' => intval($context['operator_id']),
                    'update_time' => $now,
                ];
                if ($targetState === 2) {
                    $update['auth_msg'] = $authMessage;
                }
                MerchantModel::where('id', $merchantId)->update($update);
                MerchantAuthorizationLogModel::insert([
                    'operation_request_id' => intval($operation['id']),
                    'merchant_id' => $merchantId,
                    'member_id' => intval($merchant['member_id'] ?? 0),
                    'authorization_type' => 'merchant_review',
                    'before_value' => 0,
                    'after_value' => $targetState,
                    'operator_type' => $context['operator_type'],
                    'operator_id' => intval($context['operator_id']),
                    'source' => $context['source'],
                    'request_id' => $context['request_id'],
                    'reason' => $context['reason'] !== '' ? $context['reason'] : null,
                    'create_time' => $now,
                ]);
            }

            $result = [
                'auth_state' => $targetState,
                'auth_msg' => $authMessage,
                'reason' => strval($param['reason'] ?? ''),
                'ids' => $ids,
            ];
            BusinessOperationRequestService::complete(intval($operation['id']), $result);
            Db::commit();
        } catch (\Throwable $throwable) {
            Db::rollback();
            throw $throwable;
        }

        MerchantCache::clear();
        return $result;
    }

    private static function ensureDefaultMerchantAdmin(array $merchant): void
    {
        $merchantId = intval($merchant['id']);
        $existingUserId = Db::name('merchant_user')->where('mer_id', $merchantId)
            ->where('is_admin', 1)->where('is_delete', 0)->lock(true)->value('mer_user_id');
        if ($existingUserId) {
            return;
        }

        $roleId = intval(Db::name('merchant_role')->where('mer_id', $merchantId)
            ->where('is_admin', 1)->where('is_delete', 0)->where('is_disable', 0)
            ->lock(true)->value('role_id'));
        if ($roleId <= 0) {
            $menuIds = Db::name('merchant_menu')->where('is_delete', '<>', 1)->column('menu_id');
            $role = MerchantRoleService::add([
                'mer_id' => $merchantId,
                'role_name' => 'Super Admin',
                'role_desc' => 'Super Admin',
                'remark' => 'System default',
                'sort' => 1,
                'is_admin' => 1,
                'menu_ids' => $menuIds,
            ]);
            $roleId = intval($role['role_id']);
        }

        MerchantUserService::add([
            'mer_id' => $merchantId,
            'number' => '001',
            'nickname' => strval($merchant['username'] ?? $merchant['title'] ?? ''),
            'username' => strval($merchant['username'] ?? ''),
            'phone' => strval($merchant['phone'] ?? ''),
            'remark' => 'System default',
            'sort' => 1,
            'is_super' => 1,
            'is_admin' => 1,
            'role_ids' => [$roleId],
            'password' => 123456,
        ]);
    }
}
