<?php

namespace app\api\controller\merchant;

use app\common\controller\BaseController;
use app\common\model\merchant\MerchantModel;
use app\common\service\goods\GoodsService;
use app\common\service\member\MemberOrderService;
use app\common\service\merchant\MerchantService;
use app\common\service\system\SettingService;
use app\common\validate\member\MemberOrderValidate;
use app\common\validate\merchant\MerchantValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("商家管理")
 * @Apidoc\Group("merchant")
 * @Apidoc\Sort("250")
 */
class Merchant extends BaseController
{
    private function maskMerchantTitle(string $title = ''): string
    {
        $title = trim($title);
        $length = mb_strlen($title);
        if ($length <= 1) {
            return $title;
        }

        return mb_substr($title, 0, 1) . '***' . mb_substr($title, -1, 1);
    }

    public function info()
    {
        $data = MerchantService::getInfoByMemberID();
        if (!empty($data['title'])) {
            $data['title'] = MerchantModel::formatDisplayTitle((string) $data['title']);
        }
        return success($data);
    }

    public function add()
    {
        $param = $this->params([
            'id' => '',
            'title' => '',
            'name' => '',
            'phone' => '',
            'images' => [],
            'image_id' => ''
        ]);
        validate(MerchantValidate::class)->scene('userAdd')->check($param);
        $data = MerchantService::userAdd($param);
        return success($data);
    }

    public function list()
    {
        $where = $this->where(where_disdel());
        $settingInfo = SettingService::info('wx_approved');
        if ($settingInfo['wx_approved'] == 1) {
            $where[] = ['status', '=', 1];
            $where[] = ['is_disable', '=', 0];
            $where[] = ['source', '=', 0];
            $where[] = ['stock', '>', 0];
            $data = GoodsService::list(
                $where,
                $this->page(),
                $this->limit(),
                ['sales_sum' => 'desc', 'id' => 'desc'],
                'id,goods_label_id,image_id,title,original_price,price,sales_sum,click_count,spec,unit,stock,merchant_id,source,member_id,is_weighing,is_transaction',
                [],
                3
            );
            $data['title'] = '商品排行榜';
        } else {
            $where[] = ['auth_state', '=', 1];
            $data = MerchantService::getList($where, $this->page(), $this->limit(), $this->order());
            $data['title'] = '商家信息';
            if (!empty($data['list']) && is_array($data['list'])) {
                foreach ($data['list'] as $index => $item) {
                    $data['list'][$index]['title'] = $this->maskMerchantTitle(strval($item['title'] ?? ''));
                }
            }
        }
        return success($data);
    }

    public function getOrderlist()
    {
        $merchant = MerchantService::getCurrentMemberMerchant(false, true, 'id,title,auth_state,create_time,member_id,expire_time,renew_remind_days');
        $where = $this->buildWhere([
            'status',
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['is_disable', '=', 0];
        $where[] = ['merchant_id', '=', $merchant['id']];
        $data = MemberOrderService::list(
            $where,
            $this->page(),
            $this->limit(),
            $this->order(),
            'id,pay_type,order_no,total_num,total_price,status,pay_status,member_id,mark,remark,create_time,delivery_type,pay_auth_msg,pay_voucher_imgs',
            3
        );
        return success($data);
    }

    public function getMerParams()
    {
        $data = MemberOrderService::getMerParams(3);
        return success($data);
    }

    public function getAnalytics()
    {
        $param = $this->params([
            'filter_type/s' => 'days',
            'days/d' => 7,
            'month/s' => '',
            'start_date/s' => '',
            'end_date/s' => '',
        ]);
        $merchant = $this->getCurrentMerchant();
        $data = MerchantService::getAnalytics($merchant['id'], $param);
        return success($data);
    }

    public function getRenewRecords()
    {
        $merchant = MerchantService::getCurrentMemberMerchant(true, true, 'id,title,auth_state,create_time,member_id,expire_time,renew_remind_days');
        $where = [
            ['r.merchant_id', '=', $merchant['id']],
        ];
        $data = MerchantService::renewRecordList($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }

    public function orderPayAuth()
    {
        $merchant = MerchantService::getCurrentMemberMerchant(false, true, 'id,title,auth_state,create_time,member_id,expire_time,renew_remind_days');
        $param = $this->params([
            'ids/a' => 0,
            'pay_price/f' => 0,
            'pay_status/d' => 1,
            'pay_auth_msg/s' => 0,
            'merchant_id' => $merchant['id']
        ]);
        validate(MemberOrderValidate::class)->scene('orderPayAuth')->check($param);
        $data = MemberOrderService::orderPayAuth($param['ids'], $param);
        return success($data);
    }

    private function getCurrentMerchant()
    {
        return MerchantService::getCurrentMemberMerchant(false, true, 'id,title,auth_state,create_time,member_id,expire_time,renew_remind_days');
    }
}
