<?php

declare(strict_types=1);

namespace app\common\gateway\payment;

use app\common\service\member\SettingService as MemberSettingService;
use EasyWeChat\Factory;
use think\facade\Config;

class WechatRefundGateway implements RefundGatewayInterface
{
    public function refund(array $request): array
    {
        $setting = MemberSettingService::info('wx_miniapp_appid,wx_miniapp_mch_id,wx_miniapp_mch_key');
        foreach (['wx_miniapp_appid', 'wx_miniapp_mch_id', 'wx_miniapp_mch_key'] as $field) {
            if (empty($setting[$field])) {
                throw new \RuntimeException('微信支付配置不完整');
            }
        }
        $app = Factory::payment([
            'app_id' => $setting['wx_miniapp_appid'],
            'mch_id' => $setting['wx_miniapp_mch_id'],
            'key' => $setting['wx_miniapp_mch_key'],
            'cert_path' => config_path('cert/') . 'apiclient_cert.pem',
            'key_path' => config_path('cert/') . 'apiclient_key.pem',
            'response_type' => 'array',
            'log' => [
                'level' => Config::get('app.app_debug') ? 'debug' : 'error',
                'file' => runtime_path() . '/easywechat/' . date('Ym') . '/miniProgram.log',
            ],
        ]);
        $response = $app->refund->byOutTradeNumber(
            (string) $request['out_trade_no'],
            (string) $request['merchant_request_no'],
            (int) round(floatval($request['total_amount']) * 100),
            (int) round(floatval($request['amount']) * 100),
            ['refund_desc' => (string) ($request['description'] ?? '用户申请退款')]
        );
        $success = ($response['return_code'] ?? '') === 'SUCCESS' && ($response['result_code'] ?? '') === 'SUCCESS';
        return [
            'success' => $success,
            'provider_transaction_id' => (string) ($response['refund_id'] ?? ''),
            'response' => $response,
            'error' => $success ? '' : (string) ($response['err_code_des'] ?? $response['return_msg'] ?? '微信退款失败'),
        ];
    }
}
