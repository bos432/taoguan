<?php

declare(strict_types=1);

namespace app\admin\controller\report;

use app\common\controller\BaseController;
use app\common\service\payment\PaymentGatewayAttemptQueryService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("支付网关异常")
 * @Apidoc\Group("report")
 * @Apidoc\Sort("360")
 */
class PaymentGatewayAttempt extends BaseController
{
    public function list()
    {
        return success(PaymentGatewayAttemptQueryService::list($this->params([
            'page/d' => 1, 'limit/d' => 20, 'status/s' => '', 'business_type/s' => '',
            'provider/s' => '', 'keyword/s' => '', 'start_time/s' => '', 'end_time/s' => '',
        ])));
    }

    public function summary()
    {
        return success(PaymentGatewayAttemptQueryService::summary());
    }

    public function info()
    {
        $id = intval($this->param('id/d', 0));
        if ($id <= 0) {
            return error('缺少记录ID');
        }
        return success(PaymentGatewayAttemptQueryService::info($id));
    }
}
