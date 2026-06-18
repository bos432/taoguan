<?php
namespace app\admin\controller\order;
use app\common\controller\BaseController;
use app\common\model\member\MemberOrderModel;
use app\common\service\member\MemberBillService;
use app\common\service\member\MemberOrderService;
use app\common\service\member\SettingService as MemberSettingService;
use app\common\validate\member\MemberOrderValidate;
use EasyWeChat\Factory;
use hg\apidoc\annotation as Apidoc;
use think\facade\Config;
use think\facade\Log;

/**
 * @Apidoc\Title("订单管理")
 * @Apidoc\Group("member")
 * @Apidoc\Sort("250")
 */
class Order extends BaseController
{
    /**
    * @Apidoc\Title("订单管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="订单管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\member\MemberOrderModel", field="id,is_disable,create_time,update_time,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'status',
            'pay_type'
        ]);
        $where = $this->where(where_delete($where));
        $where[]=['is_disable','=',0];

        $data = MemberOrderService::list($where, $this->page(), $this->limit(), $this->order(),'id,is_disable,create_time,update_time,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,pay_common_on,status,refund_status,refund_type,refund_delivery_id,refund_express,refund_express_name,refund_express_code,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,refund_consignee,refund_address,refund_phone,setting_delivery_id,delivery_name,delivery_code,delivery_type,delivery_time,kuaidi_order_no,mark,remark,merchant_id,take_name,take_phone,take_region,take_address,self_name,self_phone,pick_up_code,receipt_time,success_time,pay_voucher_imgs,pay_auth_msg',1);
        return success($data);
    }
    /**
     * @Apidoc\Title("查询订单列表参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="订单管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\member\MemberOrderModel", field="id,is_disable,create_time,update_time,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     * })
     */
    public function getParams()
    {
        $data = MemberOrderService::getParams(1);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单管理信息")
    * @Apidoc\Query(ref="app\common\model\member\MemberOrderModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\member\MemberOrderModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MemberOrderValidate::class)->scene('info')->check($param);
        $data = MemberOrderService::getInfo($param['id'],2,[],'id,create_time,order_no,member_id,total_num,total_price,pay_price,pay_status,pay_time,pay_type,pay_common_on,status,refund_status,refund_type,refund_delivery_id,refund_express,refund_express_name,refund_express_code,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,refund_consignee,refund_phone,refund_address,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id,take_name,take_phone,take_region,take_address,self_name,self_phone,delivery_time,pay_voucher_imgs,pay_auth_msg');
        return success($data);
    }
    /**
    * @Apidoc\Title("订单管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
    */
    public function add()
    {
        $param = $this->params(MemberOrderService::$edit_field);
        validate(MemberOrderValidate::class)->scene('add')->check($param);
        $data = MemberOrderService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="id,is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
    */
    public function edit()
    {
        $param = $this->params(MemberOrderService::$edit_field);
        validate(MemberOrderValidate::class)->scene('edit')->check($param);
        $data = MemberOrderService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MemberOrderValidate::class)->scene('dele')->check($param);
        $data = MemberOrderService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MemberOrderValidate::class)->scene('disable')->check($param);
        $data = MemberOrderService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("确认订单参数")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function getConfirmOrder()
    {
        $param = $this->params(['source/s' => null, 'goods_ids/s' => null]);
        $param['member_id']=member_id();
        validate(MemberOrderValidate::class)->scene('getConfirmOrder')->check($param);
        $data = MemberOrderService::getConfirmOrder($param);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单发货")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function delivery()
    {
        $param = $this->params(['ids/a' => [], 'setting_delivery_id/d' => 0,'kuaidi_order_no/s'=>'']);
        validate(MemberOrderValidate::class)->scene('delivery')->check($param);
        $data = MemberOrderService::delivery($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("物流查询")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function logistics(){
        $param = $this->params(['id/d' => 0]);
        validate(MemberOrderValidate::class)->scene('logistics')->check($param);
        $data = MemberOrderService::logistics($param['id']);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单提货")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function takeDelivery()
    {
        $param = $this->params(['ids/a' => [], 'pick_up_code/s' => '']);
        validate(MemberOrderValidate::class)->scene('takeDelivery')->check($param);
        $data = MemberOrderService::takeDelivery($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("处理售后")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function serviceOrder()
    {
        $param = $this->params([
            'id/d' =>0,
            'refund_type/d' =>0,
            'refund_price/f' =>0,
            'refund_status/d' =>0,
            'refund_consignee/s' =>'',
            'refund_phone/s' =>'',
            'refund_address/s' =>'',
            'refund_reason/s' =>'',
        ]);
        validate(MemberOrderValidate::class)->scene('serviceOrder')->check($param);
        $data = MemberOrderService::serviceOrder($param['id'], $param,1);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单支付审核")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function orderPayAuth()
    {
        $param = $this->params([
            'ids/a' =>0,
            'pay_price/f' =>0,
            'pay_status/d' =>0,
            'pay_auth_msg/s' =>0,
        ]);
        validate(MemberOrderValidate::class)->scene('orderPayAuth')->check($param);
        $data = MemberOrderService::orderPayAuth($param['ids'], $param);
        return success($data);
    }


}
