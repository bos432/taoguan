<?php

namespace app\admin\controller\report;

use app\common\controller\BaseController;
use app\common\service\report\PlatformAnalyticsService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("平台数据中心")
 * @Apidoc\Group("report")
 * @Apidoc\Sort("350")
 */
class PlatformAnalytics extends BaseController
{
    public function filters()
    {
        return success(PlatformAnalyticsService::getFilters());
    }

    public function summary()
    {
        return success(PlatformAnalyticsService::summary($this->analyticsParams()));
    }

    public function trend()
    {
        return success(PlatformAnalyticsService::trend($this->analyticsParams()));
    }

    public function ranking()
    {
        return success(PlatformAnalyticsService::ranking($this->analyticsParams()));
    }

    public function alerts()
    {
        return success(PlatformAnalyticsService::alerts($this->analyticsParams()));
    }

    public function merchantDetail()
    {
        $params = $this->analyticsParams();
        $merchantId = intval($this->param('merchant_id/d', 0));
        if ($merchantId <= 0) {
            return error('缺少商家ID');
        }

        $params['merchant_id'] = $merchantId;
        return success(PlatformAnalyticsService::merchantDetail($params));
    }

    private function analyticsParams()
    {
        return $this->params([
            'quick_date/s' => '',
            'start_date/s' => '',
            'end_date/s' => '',
            'month/s' => '',
            'granularity/s' => '',
            'merchant_id/d' => -1,
            'auth_state/d' => -1,
            'expire_status/s' => '',
            'goods_type_id/d' => -1,
            'order_status/d' => -1,
            'pay_status/d' => -1,
            'source/d' => -1,
            'amount_min/s' => '',
            'amount_max/s' => '',
        ]);
    }
}
