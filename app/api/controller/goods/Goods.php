<?php


namespace app\api\controller\goods;

use app\common\controller\BaseController;
use app\common\model\trace\TraceQrCodeModel;
use app\common\service\goods\GoodsLabelService;
use app\common\service\goods\GoodsBuyLockService;
use app\common\service\goods\GoodsService;
use app\common\service\goods\GoodsTypeService;
use app\common\service\member\MemberShopCartService;
use app\common\service\merchant\MerchantService;
use app\common\service\setting\SettingHallService;
use app\common\service\system\GoodsReleaseSwitchService;
use app\common\service\trace\TraceBatchTacheService;
use app\common\validate\goods\GoodsValidate;
use app\common\validate\trace\TraceBatchValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("商品管理")
 * @Apidoc\Group("goods")
 * @Apidoc\Sort("250")
 */
class Goods extends BaseController
{
    /**
     * @Apidoc\Title("商品管理列表参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商品管理列表参数", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     * })
     */
    public function getParams()
    {
        $where[] = ['is_disable','=',0];
        $where[] = ['is_delete','=',0];
        $data['goods_types'] = GoodsTypeService::list('tree', $where, [], 'id,pid,title,id as value,title as label');
        $data['goods_labels'] = GoodsLabelService::list($where, 0, 0, [], 'id,title');
        $data['hall_list'] = SettingHallService::list('tree', $where, [], 'id,pid,title');
        return success($data);
    }
    /**
     * @Apidoc\Title("商品管理列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商品管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     * })
     */
    public function list()
    {
        $params = $this->params([
                'goods_type_id/d'=>0,
                'setting_hall_id/d'=>0,
                'source/d'=>0,
                'goods_label_id/a'=>[],
                'keyword/s'=>"",
                'exact_price/f'=>0,
                'min_price/f'=>0,
                'max_price/f'=>0,
                'merchant_id/d'=>0,
            ]);
        $where = $this->where(where_delete());
        $where[] =['status','=',1];
        $where[] =['is_disable','=',0];
        $where[] =['source','=',$params['source']];
        $where[] =['stock','>',0];
        //商品分类
        if($params['goods_type_id']>0){
            $where[] =['goods_type_id','in',GoodsTypeService::getSubIds($params['goods_type_id'],true)];
        }
        //商家
        if($params['merchant_id']>0){
            $where[] =['merchant_id','=',$params['merchant_id']];
        }
        if($params['keyword']){
            $where[] =['title','like','%'.$params['keyword'].'%'];
        }
        if($params['exact_price'] > 0){
            $where[] = ['price', '=', $params['exact_price']];
        } else {
            if($params['min_price'] > 0){
                $where[] = ['price', '>=', $params['min_price']];
            }
            if($params['max_price'] > 0){
                $where[] = ['price', '<=', $params['max_price']];
            }
        }
        $data = GoodsService::list($where, $this->page(), $this->limit(), $this->order(),'id,goods_label_id,image_id,title,original_price,price,sales_sum,click_count,spec,unit,stock,merchant_id,source,member_id,is_weighing,is_transaction',$params['goods_label_id'],3);
        return success($data);
    }

    /**
     * @Apidoc\Title("商品管理信息")
     * @Apidoc\Query(ref="app\common\model\goods\GoodsModel", field="id")
     * @Apidoc\Returned(ref="app\common\model\goods\GoodsModel")
     */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(GoodsValidate::class)->scene('info')->check($param);
        $data = GoodsService::info($param['id'],true,3);
        $data['shop_cart_num'] = MemberShopCartService::getCartNum(member_id());
        $lockState = GoodsBuyLockService::getViewState(intval($param['id']), intval(member_id() ?: 0));
        $data = array_merge($data, $lockState);
        return success($data);
    }

    /**
     * @Apidoc\Title("线下商品发布参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="线下商品发布参数", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     * })
     */
    public function getReleaseParams(){
        $merchantInfo = MerchantService::getInfoByMemberID() ?: [];
        $data['goods_release_enabled'] = GoodsReleaseSwitchService::enabled() ? 1 : 0;
        $data['goods_release_allowed'] = intval($merchantInfo['auth_state'] ?? 0) === 1 ? 1 : 0;
        $where[] = ['is_disable','=',0];
        $where[] = ['is_delete','=',0];
        $data['goods_types'] = GoodsTypeService::list('tree', $where, [], 'id,pid,title');
        return success($data);
    }
    /**
     * @Apidoc\Title("商品发布")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsModel", field="title,code,is_disable,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     */
    public function saveRelease(){
        if (!GoodsReleaseSwitchService::enabled()) {
            exception('商品发布功能暂未开放');
        }
        MerchantService::assertMemberMerchantReleaseAllowed();
        $param = $this->params(GoodsService::$edit_field);
        validate(GoodsValidate::class)->scene('saveRelease')->check($param);
        $param['member_id'] = member_id();
        // 发布商品直接进入商城商品池，确保“发现好物”页可见
        $param['merchant_id'] = 0;
        $param['source'] = 0;
        $param['status'] = 1;
        $param['stock'] = 1;
        $param['is_disable'] = 0;
        foreach ($param['images'] as $k=>$v){
            if($k==0){
                $param['image_id'] = $v['file_id'];
            }
        }
        $data = GoodsService::add($param);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品管理删除")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function memberDele()
    {
        MerchantService::assertMemberMerchantAvailableIfExists();
        $param = $this->params(['id/d' => 0]);
        validate(GoodsValidate::class)->scene('memberDele')->check($param);
        $data = GoodsService::memberDele($param['id']);
        return success($data);
    }
    /**
     * @Apidoc\Title("线下交易")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function transaction(){
        MerchantService::assertMemberMerchantAvailableIfExists();
        $param = $this->params(['id/d' => 0]);
        validate(GoodsValidate::class)->scene('memberDele')->check($param);
        $data = GoodsService::transaction($param['id']);
        return success($data);
    }

    /**
     * @Apidoc\Title("查询溯源信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="is_disable")
     */
    public function getBatchTacheByCode()
    {
        $param = $this->params(['code/s' => '']);
        validate(TraceBatchValidate::class)->scene('getBatchTacheByCode')->check($param);
        $trace_batch_id = TraceQrCodeModel::query()->where('code',$param['code'])->value('trace_batch_id');
        if(!$trace_batch_id){
            exception("该二维码无效");
        }
        $data = TraceBatchTacheService::getBatchTache($trace_batch_id);
        return success($data);
    }
}
