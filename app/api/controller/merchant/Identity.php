<?php

namespace app\api\controller\merchant;

use app\common\controller\BaseController;
use app\common\service\merchant\MerchantIdentityService;
use app\common\service\permission\UnifiedPermissionContextService;

class Identity extends BaseController
{
    public function list()
    {
        return success([
            'list' => MerchantIdentityService::list(member_id(true)),
        ]);
    }

    public function current()
    {
        $memberId = member_id(true);
        $merchantUserId = MerchantIdentityService::requestedMerUserId();
        $result = MerchantIdentityService::current($memberId, $merchantUserId);
        $result['permission_context'] = UnifiedPermissionContextService::forMember($memberId, $merchantUserId);
        return success($result);
    }

    public function switch()
    {
        $param = $this->params([
            'mer_user_id/d' => 0,
        ]);

        $memberId = member_id(true);
        $merchantUserId = intval($param['mer_user_id'] ?? 0);
        $result = MerchantIdentityService::switch($memberId, $merchantUserId);
        $result['permission_context'] = UnifiedPermissionContextService::forMember($memberId, $merchantUserId);
        return success($result);
    }

    public function permissions()
    {
        return success([
            'permissions' => MerchantIdentityService::permissions(member_id(true), MerchantIdentityService::requestedMerUserId()),
        ]);
    }

    public function context()
    {
        return success(UnifiedPermissionContextService::forMember(
            member_id(true),
            MerchantIdentityService::requestedMerUserId()
        ));
    }
}
