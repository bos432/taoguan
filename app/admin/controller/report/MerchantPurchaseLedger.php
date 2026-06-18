<?php
namespace app\admin\controller\report;

use app\common\controller\BaseController;
use app\common\service\report\MerchantPurchaseLedgerReportService;
use think\Response;

class MerchantPurchaseLedger extends BaseController
{
    public function filters()
    {
        return success(MerchantPurchaseLedgerReportService::filters());
    }

    public function summary()
    {
        return success(MerchantPurchaseLedgerReportService::summary($this->reportParams()));
    }

    public function list()
    {
        $params = array_merge($this->reportParams(), [
            'page' => $this->page(),
            'limit' => $this->limit(),
        ]);
        return success(MerchantPurchaseLedgerReportService::list($params));
    }

    public function tradeDiffOrders()
    {
        $params = array_merge($this->reportParams(), $this->params([
            'merchant_id/d' => 0,
            'direction/s' => '',
            'target_amount/f' => 0,
        ]));
        return success(MerchantPurchaseLedgerReportService::tradeDiffOrders($params));
    }

    public function export()
    {
        $payload = MerchantPurchaseLedgerReportService::export($this->reportParams());
        return Response::create($payload['content'] ?? '', 'html', 200)
            ->header([
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . urlencode($payload['filename'] ?? 'merchant_purchase_ledger.csv') . '"',
                'Cache-Control' => 'max-age=0',
            ]);
    }

    private function reportParams(): array
    {
        return $this->params([
            'quick_date/s' => '',
            'start_date/s' => '',
            'end_date/s' => '',
            'buyer_merchant_id/d' => 0,
            'source_type/s' => '',
            'source_merchant_id/d' => -1,
            'order_no/s' => '',
            'keyword/s' => '',
            'reconciliation_status/s' => '',
        ]);
    }
}
