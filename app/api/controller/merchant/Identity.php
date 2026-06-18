<?php

namespace app\api\controller\merchant;

use app\common\controller\BaseController;
use app\common\service\merchant\MerchantIdentityService;

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
        return success(
            MerchantIdentityService::current(member_id(true), MerchantIdentityService::requestedMerUserId())
        );
    }

    public function switch()
    {
        $param = $this->params([
            'mer_user_id/d' => 0,
        ]);

        return success(
            MerchantIdentityService::switch(member_id(true), intval($param['mer_user_id'] ?? 0))
        );
    }

    public function permissions()
    {
        return success([
            'permissions' => MerchantIdentityService::permissions(member_id(true), MerchantIdentityService::requestedMerUserId()),
        ]);
    }
}
