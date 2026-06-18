<?php

namespace app\admin\controller\report;

use app\common\controller\BaseController;
use app\common\service\report\InternalTakeoverReportService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("内部接盘对账")
 * @Apidoc\Group("report")
 * @Apidoc\Sort("360")
 */
class InternalTakeover extends BaseController
{
    public function filters()
    {
        return success(InternalTakeoverReportService::filters());
    }

    public function summary()
    {
        return success(InternalTakeoverReportService::summary($this->reportParams()));
    }

    public function list()
    {
        $params = array_merge($this->reportParams(), [
            'page' => $this->page(),
            'limit' => $this->limit(),
        ]);
        return success(InternalTakeoverReportService::list($params));
    }

    public function detail()
    {
        $id = intval($this->param('id/d', 0));
        return success(InternalTakeoverReportService::detail($id));
    }

    private function reportParams(): array
    {
        return $this->params([
            'id/d' => 0,
            'quick_date/s' => '',
            'start_date/s' => '',
            'end_date/s' => '',
            'internal_merchant_id/d' => 0,
            'source_merchant_id/d' => 0,
            'pay_status/d' => -1,
            'stage_code/s' => '',
            'keyword/s' => '',
        ]);
    }
}
