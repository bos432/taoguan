<?php
namespace app\api\controller\member;
use app\common\controller\BaseController;
use app\common\service\member\MemberOrderService;
use app\common\service\member\SettingService as MemberSettingService;
use app\common\service\order\WechatPaymentCallbackService;
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
class MemberOrder extends BaseController
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
        ]);
        $where = $this->where(where_delete($where));
        $where[]=['is_disable','=',0];
        $where[]=['member_id','=',member_id()];
        $data = MemberOrderService::list($where, $this->page(), $this->limit(), $this->order(),'id,pay_type,order_no,total_num,total_price,status,pay_status,merchant_id,mark,remark,create_time,delivery_type,pay_auth_msg,pay_voucher_imgs',3);
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
        $data = MemberOrderService::getParams(3);
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
        $where[] = ['member_id','=',member_id(true)];
        $data = MemberOrderService::getInfo($param['id'],3,$where);
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
     * @Apidoc\Title("确认订单")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function confirmOrder()
    {
        $param = $this->params(MemberOrderService::$edit_field);
        $param['member_id']=member_id();
        validate(MemberOrderValidate::class)->scene('confirmOrder')->check($param);
        $data = MemberOrderService::confirmOrder($param);
        return success($data);
    }
    /**
     * @Apidoc\Title("取消订单")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function cancelOrder()
    {
        $param = $this->params(['id/d' => 0]);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('cancelOrder')->check($param);
        $data = MemberOrderService::cancelOrder([$param['id']],$param);
        return success($data);
    }
    /**
     * @Apidoc\Title("微信支付回调接口")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function orderNotify()
    {
        //组装支付
        $member_setting = MemberSettingService::info('wx_miniapp_appid,wx_miniapp_mch_id,wx_miniapp_mch_key');
        $log_level = Config::get('app.app_debug') ? 'debug' : 'error';
        $config = [
            'app_id' => $member_setting['wx_miniapp_appid'],
            'mch_id' => $member_setting['wx_miniapp_mch_id'],
            'key'    => $member_setting['wx_miniapp_mch_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
            'response_type' => 'array',
            'log' => [
                'level' => $log_level,
                'file' => runtime_path() . 'easywechat/' . date('Ym') . '/miniProgram.log',
            ],
        ];
        $app = Factory::payment($config);
        $response = $app->handlePaidNotify(function($message, $fail){
            Log::record($message);
            if (($message['return_code'] ?? '') !== 'SUCCESS') {
                return $fail('通信失败，请稍后再通知我');
            }
            if (($message['result_code'] ?? '') !== 'SUCCESS') {
                return true;
            }
            WechatPaymentCallbackService::handleSuccess($message);
            return true;
        });

        $response->send(); // return $response;
    }

    /**
     * @Apidoc\Title("订单支付")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function payOrder()
    {
        $param = $this->params(['id/d' => 0]);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('payOrder')->check($param);
        $data = MemberOrderService::payOrder([$param['id']],$param);
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
        $data = MemberOrderService::logistics($param['id'],3);
        return success($data);
    }
    /**
     * @Apidoc\Title("查看取件码")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable")
     */
    public function getOrderCode(){
        $param = $this->params(['id/d' => 0]);
        validate(MemberOrderValidate::class)->scene('getOrderCode')->check($param);
        $data = MemberOrderService::getOrderCode($param['id']);
        return success($data);
    }
    /**
     * @Apidoc\Title("确认收货")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function confirmReceipt()
    {
        $param = $this->params(['id/d' => 0]);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('confirmReceipt')->check($param);
        $data = MemberOrderService::confirmReceipt([$param['id']],$param);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单评价")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function submitEvaluation()
    {
        $param = $this->params(['id/d' => 0,'evaluate_content/s'=>'','evaluate_num/d'=>0]);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('submitEvaluation')->check($param);
        $data = MemberOrderService::submitEvaluation([$param['id']],$param);
        return success($data);
    }
    /**
     * @Apidoc\Title("申请售后")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function submitService()
    {
        $param = $this->params(['id/d' => 0,'refund_reason_wap_explain/s'=>'','refund_reason_wap_imgs/a'=>[],'refund_type/d'=>2,'refund_price/f'=>0]);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('submitService')->check($param);
        $param['_operation_context'] = \app\common\domain\operation\BusinessOperationContextFactory::fromRequest(
            'uniapp-weixin', 'member', intval($param['member_id']), strval($param['refund_reason_wap_explain'] ?? '')
        );
        $data = MemberOrderService::submitService([$param['id']],$param);
        return success($data);
    }
    /**
     * @Apidoc\Title("退货")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function returnGoods()
    {
        $param = $this->params(['id/d' => 0,'refund_delivery_id/d'=>0,'refund_express/s'=>'']);
        $param['member_id']=member_id(true);
        validate(MemberOrderValidate::class)->scene('returnGoods')->check($param);
        $data = MemberOrderService::returnGoods([$param['id']],$param);
        return success($data);
    }
    /**
     * @Apidoc\Title("查询交易信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\member\MemberOrderModel", field="is_disable,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id")
     */
    public function getOrderTransaction()
    {
        $param = $this->params(['type/d' => 1]);
        $data = MemberOrderService::getOrderTransaction($param['type']);
        return success($data);
    }
}
