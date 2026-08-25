<?php

declare(strict_types=1);

namespace app\common\gateway\payment;

use app\common\model\member\ThirdModel;
use app\common\service\member\SettingService as MemberSettingService;
use EasyWeChat\Factory;
use think\facade\Config;

class WechatPrepaymentGateway implements PrepaymentGatewayInterface
{
    public function prepay(array $request): array
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
            'response_type' => 'array',
            'log' => [
                'level' => Config::get('app.app_debug') ? 'debug' : 'error',
                'file' => runtime_path() . '/easywechat/' . date('Ym') . '/miniProgram.log',
            ],
        ]);
        $response = $app->order->unify([
            'body' => strval($request['description'] ?? '购买商品'),
            'out_trade_no' => strval($request['out_trade_no']),
            'total_fee' => intval(round(floatval($request['amount']) * 100)),
            'notify_url' => strval($request['notify_url']),
            'trade_type' => 'JSAPI',
            'openid' => ThirdModel::where('member_id', intval($request['member_id']))->value('openid'),
        ]);
        $success = ($response['return_code'] ?? '') === 'SUCCESS'
            && ($response['return_msg'] ?? '') === 'OK'
            && !empty($response['prepay_id']);
        return [
            'success' => $success,
            'bridge_config' => $success ? $app->jssdk->bridgeConfig($response['prepay_id'], false) : [],
            'provider_transaction_id' => strval($response['prepay_id'] ?? ''),
            'response' => $response,
            'error' => $success ? '' : strval($response['err_code_des'] ?? $response['return_msg'] ?? '微信预下单失败'),
        ];
    }
}
