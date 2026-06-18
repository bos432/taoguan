<?php

namespace app\admin\controller\report;

use app\common\controller\BaseController;
use app\common\service\report\PlatformExportService;
use think\Response;

class PlatformExport extends BaseController
{
    public function orders()
    {
        return $this->downloadCsv(PlatformExportService::exportOrders($this->exportParams()));
    }

    public function merchants()
    {
        return $this->downloadCsv(PlatformExportService::exportMerchants($this->exportParams()));
    }

    public function renewRecords()
    {
        return $this->downloadCsv(PlatformExportService::exportRenewRecords($this->exportParams()));
    }

    public function analytics()
    {
        return $this->downloadCsv(PlatformExportService::exportAnalytics($this->exportParams()));
    }

    private function downloadCsv($payload = [])
    {
        $filename = $payload['filename'] ?? ('export_' . date('Ymd_His') . '.csv');
        $content = $payload['content'] ?? '';

        return Response::create($content, 'html', 200)
            ->header([
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
                'Cache-Control' => 'max-age=0',
            ]);
    }

    private function exportParams()
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
