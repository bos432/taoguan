<?php

declare(strict_types=1);

namespace app\common\service\merchant;

use app\common\cache\merchant\MerchantCache;
use app\common\domain\operation\BusinessOperationContext;
use app\common\model\merchant\MerchantAuthorizationLogModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\operation\BusinessOperationRequestService;
use think\facade\Db;

class MerchantSuperAuthorizationService
{
    public static function execute(array $ids, int $enabled, array $context): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) exception('请选择商家');
        $enabled = $enabled === 1 ? 1 : 0;
        $context = BusinessOperationContext::normalize($context);
        $operationType = $enabled === 1 ? 'merchant.super_grant' : 'merchant.super_revoke';

        Db::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin($operationType, $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    Db::commit();
                    return json_decode(strval($operation['result_json'] ?? '{}'), true) ?: ['ids' => $ids, 'member_is_super' => $enabled];
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该授权请求正在处理中' : '该授权请求已失败，请更换请求编号');
            }
            $merchants = MerchantModel::whereIn('id', $ids)->where('is_delete', 0)
                ->field('id,title,member_id,member_is_super,auth_state,is_disable')->lock(true)->select()->toArray();
            if (count($merchants) !== count($ids)) exception('部分商家不存在或已删除');

            $changedIds = [];
            foreach ($merchants as $merchant) {
                if ($enabled === 1) self::assertGrantable($merchant);
                $before = intval($merchant['member_is_super'] ?? 0);
                if ($before === $enabled) continue;
                MerchantModel::where('id', intval($merchant['id']))->update([
                    'member_is_super' => $enabled,
                    'update_uid' => intval($context['operator_id']),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                MerchantAuthorizationLogModel::insert([
                    'operation_request_id' => intval($operation['id']),
                    'merchant_id' => intval($merchant['id']),
                    'member_id' => intval($merchant['member_id'] ?? 0),
                    'authorization_type' => 'merchant_super',
                    'before_value' => $before,
                    'after_value' => $enabled,
                    'operator_type' => $context['operator_type'],
                    'operator_id' => intval($context['operator_id']),
                    'source' => $context['source'],
                    'request_id' => $context['request_id'],
                    'reason' => $context['reason'] !== '' ? $context['reason'] : null,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $changedIds[] = intval($merchant['id']);
            }
            $result = ['ids' => $ids, 'changed_ids' => $changedIds, 'member_is_super' => $enabled];
            BusinessOperationRequestService::complete(intval($operation['id']), $result);
            Db::commit();
        } catch (\Throwable $throwable) {
            Db::rollback();
            throw $throwable;
        }
        MerchantCache::del($ids);
        return $result;
    }

    private static function assertGrantable(array $merchant): void
    {
        if (intval($merchant['member_id'] ?? 0) <= 0) exception('商家尚未绑定会员，无法设置超管');
        if (intval($merchant['auth_state'] ?? 0) !== 1) exception('商家尚未审核通过，无法设置超管');
        if (intval($merchant['is_disable'] ?? 0) === 1) exception('商家已禁用，无法设置超管');
    }
}
