<?php

declare(strict_types=1);

namespace app\common\service\merchant;

use app\common\cache\merchant\MerchantCache;
use app\common\domain\operation\BusinessOperationContext;
use app\common\model\member\MemberModel;
use app\common\model\merchant\MerchantAuthorizationLogModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\operation\BusinessOperationRequestService;
use think\facade\Db;

class MerchantMemberBindingService
{
    public static function execute(int $merchantId, int $memberId, array $context): array
    {
        if ($merchantId <= 0 || $memberId < 0) {
            exception('商家或会员参数错误');
        }
        $context = BusinessOperationContext::normalize($context);
        $operationType = $memberId > 0 ? 'merchant.member_bind' : 'merchant.member_unbind';

        Db::startTrans();
        try {
            $operation = BusinessOperationRequestService::begin($operationType, $context);
            if (!empty($operation['duplicate'])) {
                if (intval($operation['status'] ?? 0) === 1) {
                    Db::commit();
                    return json_decode(strval($operation['result_json'] ?? '{}'), true) ?: [];
                }
                exception(intval($operation['status'] ?? 0) === 0 ? '该绑定请求正在处理中' : '该绑定请求已失败，请更换请求编号');
            }

            $merchant = MerchantModel::where('id', $merchantId)->where('is_delete', 0)
                ->field('id,title,member_id,member_is_super')->lock(true)->find();
            if (!$merchant) {
                exception('商家不存在或已删除');
            }
            $merchant = $merchant->toArray();
            if ($memberId > 0) {
                self::assertMemberAvailable($merchantId, $memberId);
            }

            $beforeMemberId = intval($merchant['member_id'] ?? 0);
            $beforeSuper = intval($merchant['member_is_super'] ?? 0);
            $changed = $beforeMemberId !== $memberId;
            $afterSuper = $changed ? 0 : $beforeSuper;
            if ($changed) {
                MerchantModel::where('id', $merchantId)->update([
                    'member_id' => $memberId,
                    'member_is_super' => $afterSuper,
                    'update_uid' => intval($context['operator_id']),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                self::writeLog($operation, $merchantId, $memberId, 'merchant_member_binding', $beforeMemberId, $memberId, $context);
                if ($beforeSuper === 1) {
                    self::writeLog($operation, $merchantId, $beforeMemberId, 'merchant_super', 1, 0, $context);
                }
            }

            $result = [
                'id' => $merchantId,
                'member_id' => $memberId,
                'before_member_id' => $beforeMemberId,
                'member_is_super' => $afterSuper,
                'changed' => $changed,
            ];
            BusinessOperationRequestService::complete(intval($operation['id']), $result);
            Db::commit();
        } catch (\Throwable $throwable) {
            Db::rollback();
            throw $throwable;
        }

        MerchantCache::del([$merchantId]);
        return $result;
    }

    private static function assertMemberAvailable(int $merchantId, int $memberId): void
    {
        $member = MemberModel::where('member_id', $memberId)->where('is_delete', 0)->lock(true)->find();
        if (!$member) {
            exception('会员不存在或已删除');
        }
        $conflict = MerchantModel::where('member_id', $memberId)->where('id', '<>', $merchantId)
            ->where('is_delete', 0)->lock(true)->value('id');
        if ($conflict) {
            exception('该会员已绑定其他商家');
        }
    }

    private static function writeLog(
        array $operation,
        int $merchantId,
        int $memberId,
        string $authorizationType,
        int $beforeValue,
        int $afterValue,
        array $context
    ): void {
        MerchantAuthorizationLogModel::insert([
            'operation_request_id' => intval($operation['id']),
            'merchant_id' => $merchantId,
            'member_id' => $memberId,
            'authorization_type' => $authorizationType,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'operator_type' => $context['operator_type'],
            'operator_id' => intval($context['operator_id']),
            'source' => $context['source'],
            'request_id' => $context['request_id'],
            'reason' => $context['reason'] !== '' ? $context['reason'] : null,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
