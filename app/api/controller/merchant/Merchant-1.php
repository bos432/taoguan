<?php

namespace app\api\controller\merchant;
use app\common\controller\BaseController;
use app\common\model\merchant\MerchantModel;
use app\common\service\goods\GoodsService;
use app\common\service\goods\GoodsTypeService;
use app\common\service\member\MemberOrderService;
use app\common\service\merchant\MerchantService;
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

    /**
    * @Apidoc\Title("商家管理信息")
    * @Apidoc\Query(ref="app\common\model\MerchantModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\MerchantModel")
    */
    public function info()
    {
        $data = MerchantService::getInfoByMemberID();
        return success($data);
    }
    /**
    * @Apidoc\Title("商家管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\MerchantModel", field="title,content,is_disable,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
    */
    public function add()
    {
        $param = $this->params([
          'id'=>'',//id
          'title'=>'',//商户名称
          'name'=>'',//姓名
          'phone'=>'',//联系电话
          'images'=>[],
          'image_id'=>''
        ]);
        validate(MerchantValidate::class)->scene('userAdd')->check($param);
        $data = MerchantService::userAdd($param);
        return success($data);
    }
    /**
     * @Apidoc\Title("商家管理列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商家管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\MerchantModel", field="id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
     * })
     */
    public function list()
    {
        $where = $this->where(where_disdel());
        $settingInfo = \app\common\service\system\SettingService::info('wx_approved');
        if($settingInfo['wx_approved']==1){
            $where[] =['status','=',1];
            $where[] =['is_disable','=',0];
            $where[] =['source','=',0];
            $where[] =['stock','>',0];
            $data = GoodsService::list($where, $this->page(), $this->limit(), ['sales_sum'=>'desc','id'=>'desc'],'id,goods_label_id,image_id,title,original_price,price,sales_sum,click_count,spec,unit,stock,merchant_id,source,member_id,is_weighing,is_transaction',[],3);
            $data['title']="商品排行榜";
        }else{
            $where[] =['auth_state','=',1];
            $data = MerchantService::getList($where, $this->page(), $this->limit(), $this->order());
            $data['title']="商家信息";
        }
        return success($data);
    }

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
    public function getOrderlist()
    {
        //判断是否是商家
        $merchant_id = MerchantModel::where('member_id', member_id(true))
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->where('auth_state',1)
            ->value('id');
        if(!$merchant_id){
            exception('对不起，未查询到您商家信息');
        }
        $where = $this->buildWhere([
            'status',
        ]);
        $where = $this->where(where_delete($where));
        $where[]=['is_disable','=',0];
        $where[]=['merchant_id','=',$merchant_id];
        $data = MemberOrderService::list($where, $this->page(), $this->limit(), $this->order(),'id,pay_type,order_no,total_num,total_price,status,pay_status,member_id,mark,remark,create_time,delivery_type,pay_auth_msg,pay_voucher_imgs',3);
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
    public function getMerParams()
    {
        $data = MemberOrderService::getMerParams(3);
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
        //判断是否是商家
        $merchant_id = MerchantModel::where('member_id', member_id(true))
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->where('auth_state',1)
            ->value('id');
        if(!$merchant_id){
            exception('对不起，未查询到您商家信息');
        }
        $param = $this->params([
            'ids/a' =>0,
            'pay_price/f' =>0,
            'pay_status/d' =>1,
            'pay_auth_msg/s' =>0,
            'merchant_id'=>$merchant_id
        ]);
        validate(MemberOrderValidate::class)->scene('orderPayAuth')->check($param);
        $data = MemberOrderService::orderPayAuth($param['ids'], $param);
        return success($data);
    }
}
