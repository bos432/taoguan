<?php

namespace app\api\controller\admin;

use app\common\controller\BaseController;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberOrderService;
use app\common\service\member\MemberService;
use app\common\service\merchant\MerchantService;
use app\common\service\system\MobileAdminAccessService;
use app\common\validate\member\MemberOrderValidate;
use app\common\validate\merchant\MerchantValidate;

class MobileAdmin extends BaseController
{
    private function getAccess(): array
    {
        $memberId = member_id(true);
        $member = MemberService::getMemberInfo($memberId, true, 'member_id,username,phone,nickname');
        return MobileAdminAccessService::getAccessByMember($member);
    }

    private function assertPermission(string $permission): array
    {
        $access = $this->getAccess();
        if (!MobileAdminAccessService::hasPermission($access, $permission)) {
            exception('暂无权限');
        }

        return $access;
    }

    private function assertAnyPermission(array $permissions = []): array
    {
        $access = $this->getAccess();
        foreach ($permissions as $permission) {
            if (MobileAdminAccessService::hasPermission($access, $permission)) {
                return $access;
            }
        }

        exception('暂无权限');
    }

    public function merchantParams()
    {
        $this->assertAnyPermission(['merchant_view', 'merchant_auth']);
        return success(MerchantService::getParams(1));
    }

    public function merchantList()
    {
        $this->assertAnyPermission(['merchant_view', 'merchant_auth']);

        $where = $this->buildWhere([
            'auth_state',
        ]);
        $where = $this->where(where_delete($where));

        $data = MerchantService::list(
            $where,
            $this->page(),
            $this->limit(),
            $this->order(),
            'id,title,username,address,phone,auth_state,auth_msg,sort,remark,name,image_id,member_id,create_time,update_time'
        );

        return success($data);
    }

    public function merchantInfo()
    {
        $this->assertAnyPermission(['merchant_view', 'merchant_auth']);

        $param = $this->params(['id/d' => 0]);
        validate(MerchantValidate::class)->scene('info')->check($param);

        return success(MerchantService::info($param['id']));
    }

    public function merchantAuth()
    {
        $this->assertPermission('merchant_auth');

        $param = $this->params([
            'ids/a' => [],
            'auth_state/d' => 0,
            'auth_msg/s' => '',
        ]);
        validate(MerchantValidate::class)->scene('auth')->check($param);

        return success(MerchantService::auth($param['ids'], $param));
    }

    public function orderParams()
    {
        $this->assertAnyPermission(['order_view', 'order_pay_auth', 'order_writeoff']);

        $reviewScenes = [
            ['value' => 'all', 'label' => '全部订单'],
            ['value' => 'pending_pay_auth', 'label' => '待审核付款'],
            ['value' => 'pending_writeoff', 'label' => '待核销订单'],
        ];

        return success([
            'review_scenes' => $reviewScenes,
            'order_status' => MemberOrderService::getMerParams(3)['order_status'] ?? [],
        ]);
    }

    public function orderList()
    {
        $this->assertAnyPermission(['order_view', 'order_pay_auth', 'order_writeoff']);

        $reviewScene = $this->param('review_scene/s', 'all');

        $where = $this->buildWhere([
            'status',
            'pay_type',
            'merchant_id',
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['is_disable', '=', 0];

        if ($reviewScene === 'pending_pay_auth') {
            $where[] = ['pay_type', '=', MemberOrderModel::getPayType('voucher', 1)];
            $where[] = ['pay_status', '=', 0];
        } elseif ($reviewScene === 'pending_writeoff') {
            $where[] = ['delivery_type', '=', 2];
            $where[] = ['status', '=', MemberOrderModel::getStatus('p_shipment', 1)];
        }

        $field = 'id,is_disable,create_time,update_time,order_no,member_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id,take_name,take_phone,take_region,take_address,self_name,self_phone,pick_up_code,receipt_time,success_time,pay_voucher_imgs,pay_auth_msg';
        $data = MemberOrderService::list($where, $this->page(), $this->limit(), $this->order(), $field, 1);

        $data['review_scene'] = $reviewScene;

        return success($data);
    }

    public function orderPayAuth()
    {
        $this->assertPermission('order_pay_auth');

        $param = $this->params([
            'ids/a' => [],
            'pay_price/f' => 0,
            'pay_status/d' => 0,
            'pay_auth_msg/s' => '',
        ]);
        validate(MemberOrderValidate::class)->scene('orderPayAuth')->check($param);

        return success(MemberOrderService::orderPayAuth($param['ids'], $param));
    }

    public function orderWriteoff()
    {
        $this->assertPermission('order_writeoff');

        $param = $this->params([
            'id/d' => 0,
            'pick_up_code/s' => '',
        ]);
        validate(MemberOrderValidate::class)->scene('confirmReceipt')->check(['id' => $param['id']]);

        $order = MemberOrderModel::where('id', $param['id'])
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->field('id,pick_up_code,delivery_type,status')
            ->find();
        if (!$order) {
            exception('订单不存在');
        }

        $order = $order->toArray();
        if (intval($order['delivery_type'] ?? 0) !== 2) {
            exception('该订单不是自提订单');
        }
        if (intval($order['status'] ?? -1) !== MemberOrderModel::getStatus('p_shipment', 1)) {
            exception('该订单当前不可核销');
        }

        $pickUpCode = trim((string) ($param['pick_up_code'] ?? ''));
        if ($pickUpCode === '') {
            $pickUpCode = strval($order['pick_up_code'] ?? '');
        }
        if ($pickUpCode === '') {
            exception('订单缺少提货码');
        }

        MemberOrderService::takeDelivery([$order['id']], [
            'pick_up_code' => $pickUpCode,
        ]);

        return success([
            'id' => intval($order['id'] ?? 0),
        ]);
    }
}
