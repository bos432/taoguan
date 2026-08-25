<?php

declare(strict_types=1);

namespace app\common\service\merchant;

use app\common\model\merchant\MerchantAuthorizationLogModel;
use think\facade\Db;

class MerchantAuthorizationLogQueryService
{
    public static function list(int $merchantId, string $authorizationType = '', int $page = 1, int $limit = 20): array
    {
        if ($merchantId <= 0) {
            exception('请选择商家');
        }
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $query = MerchantAuthorizationLogModel::where('merchant_id', $merchantId);
        if ($authorizationType !== '') {
            $query->where('authorization_type', $authorizationType);
        }
        $count = (clone $query)->count();
        $rows = $query->page($page, $limit)->order('id', 'desc')->select()->toArray();
        $operatorIds = [];
        foreach ($rows as $row) {
            if (($row['operator_type'] ?? '') === 'platform_admin' && intval($row['operator_id'] ?? 0) > 0) {
                $operatorIds[] = intval($row['operator_id']);
            }
        }
        $operators = $operatorIds === [] ? [] : Db::name('system_user')
            ->whereIn('user_id', array_values(array_unique($operatorIds)))
            ->column('nickname', 'user_id');
        foreach ($rows as &$row) {
            $type = strval($row['authorization_type'] ?? '');
            $row['action_title'] = self::actionTitle($type, intval($row['after_value'] ?? 0));
            $row['before_title'] = self::valueTitle($type, intval($row['before_value'] ?? 0));
            $row['after_title'] = self::valueTitle($type, intval($row['after_value'] ?? 0));
            $operatorId = intval($row['operator_id'] ?? 0);
            $row['operator_title'] = strval($operators[$operatorId] ?? self::operatorTitle(strval($row['operator_type'] ?? ''), $operatorId));
            $row['source_title'] = self::sourceTitle(strval($row['source'] ?? ''));
        }
        unset($row);

        return [
            'count' => $count,
            'pages' => (int) ceil($count / $limit),
            'page' => $page,
            'limit' => $limit,
            'list' => $rows,
        ];
    }

    private static function actionTitle(string $type, int $afterValue): string
    {
        return match ($type) {
            'merchant_member_binding' => $afterValue > 0 ? '绑定会员' : '解绑会员',
            'merchant_super' => $afterValue === 1 ? '授予商家超管' : '取消商家超管',
            'merchant_review' => $afterValue === 1 ? '审核通过' : '审核拒绝',
            default => '权限变更',
        };
    }

    private static function valueTitle(string $type, int $value): string
    {
        return match ($type) {
            'merchant_member_binding' => $value > 0 ? '会员 #' . $value : '未绑定',
            'merchant_super' => $value === 1 ? '已启用' : '未启用',
            'merchant_review' => match ($value) { 1 => '审核通过', 2 => '审核拒绝', default => '待审核' },
            default => strval($value),
        };
    }

    private static function operatorTitle(string $type, int $id): string
    {
        $label = match ($type) {
            'platform_admin' => '平台管理员',
            'member' => '会员',
            'merchant_user' => '商家用户',
            'system' => '系统',
            default => $type !== '' ? $type : '未知操作人',
        };
        return $id > 0 ? $label . ' #' . $id : $label;
    }

    private static function sourceTitle(string $source): string
    {
        return match ($source) {
            'admin-next' => '平台后台',
            'uniapp-weixin' => '微信小程序',
            'uniapp-h5' => 'H5',
            'merchant-web' => '商家桌面端',
            'inspection' => '巡检端',
            'legacy' => '兼容接口',
            default => $source,
        };
    }
}
