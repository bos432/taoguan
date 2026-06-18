<?php

namespace app\admin\controller\system;

use app\common\controller\BaseController;
use app\common\service\system\MerchantPurchaseLimitService;
use app\common\validate\system\MerchantPurchaseLimitValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("商家购买限制")
 * @Apidoc\Group("system")
 * @Apidoc\Sort("1001")
 */
class MerchantPurchaseLimit extends BaseController
{
    /**
     * @Apidoc\Title("商家购买限制信息")
     */
    public function info()
    {
        return success(MerchantPurchaseLimitService::info());
    }

    /**
     * @Apidoc\Title("商家购买限制修改")
     * @Apidoc\Method("POST")
     */
    public function edit()
    {
        $param = $this->params([
            'enabled/d' => 1,
            'daily_quantity_limit/d' => 100,
            'daily_amount_limit/f' => 50000,
        ]);

        validate(MerchantPurchaseLimitValidate::class)->scene('edit')->check($param);

        $data = MerchantPurchaseLimitService::edit($param);

        return success($data);
    }
}
