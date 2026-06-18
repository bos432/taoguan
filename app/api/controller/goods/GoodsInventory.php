<?php
namespace app\api\controller\goods;
use app\common\controller\BaseController;
use app\common\service\goods\GoodsInventoryService;
use app\common\service\goods\GoodsService;
use app\common\service\goods\GoodsTypeService;
use app\common\service\member\MemberService;
use app\common\validate\goods\GoodsInventoryValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("出入库明细")
 * @Apidoc\Group("goods")
 * @Apidoc\Sort("250")
 */
class GoodsInventory extends BaseController
{
    /**
    * @Apidoc\Title("出入库明细列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="出入库明细列表", children={
    *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
    * })
    */
    public function list()
    {
        $where = $this->where(where_delete());
        $data = GoodsInventoryService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
     * @Apidoc\Title("待称重列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="待称重列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getWeighingWaitList(){
        $where = $this->buildWhere([
            'keyword'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['is_disable','=',0];
        $where[] = ['is_weighing','=',0];
        $where[] = ['inventory_type','=',1];
        $data = GoodsInventoryService::getWeighingWaitList($where, $this->page(), $this->limit(), $this->order(),'',1);
        return success($data);
    }
    /**
     * @Apidoc\Title("已称重列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="已称重列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getWeighingSuccessList(){
        $where = $this->buildWhere([
            'keyword'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['is_disable','=',0];
        $where[] = ['is_weighing','=',1];
        $where[] = ['inventory_type','=',1];
        $where[] = ['weighing_uid','=',member_id(true)];
        $data = GoodsInventoryService::getWeighingWaitList($where, $this->page(), $this->limit(), $this->order(),'',2);
        return success($data);
    }
    /**
     * @Apidoc\Title("确认称重")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="确认称重", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function saveWeighingWait(){
        $param = $this->params([
            'list/a' => [],
        ]);
        validate(GoodsInventoryValidate::class)->scene('saveWeighingWait')->check($param);
        $data = GoodsInventoryService::saveWeighingWait($param['list']);
        return success($data,'完成称重');
    }
    /**
     * @Apidoc\Title("称重管理统计")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="称重管理统计", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getWeighingStatistics(){
        $data = GoodsInventoryService::getWeighingStatistics();
        return success($data);
    }

    /**
     * @Apidoc\Title("库存列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="库存列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getWarehousingList(){
        $param = $this->params(['type_id']);
        validate(GoodsInventoryValidate::class)->scene('getWarehousingList')->check($param);
        $where = $this->buildWhere([
            'keyword',
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['is_disable','=',0];
        switch ($param['type_id']){
            case 1://待入库
                $where[] = ['is_warehousing','=',0];
                $where[] = ['inventory_type','=',1];
                break;
            case 2://待出库
                $where[] = ['inventory_type','=',2];
                break;
            case 3://出入库记录
                $where[] = ['warehousing_uid','=',member_id(true)];
                break;
        }
        $where[] = ['is_weighing','=',1];
        $where[] = ['warehouse.code','<>','ZC'];
        $data = GoodsInventoryService::getWarehousingList($where, $this->page(), $this->limit(), $this->order(),'',$param['type_id']);
        return success($data);
    }
    /**
     * @Apidoc\Title("确认入库")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="确认入库", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function saveWarehousingStore(){
        $param = $this->params([
            'store_list/a' => [],
        ]);
        validate(GoodsInventoryValidate::class)->scene('saveWarehousingStore')->check($param);
        $data = GoodsInventoryService::saveWarehousingStore($param['store_list']);
        return success($data,'入库成功');
    }

    /**
     * @Apidoc\Title("线下商品待称重列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="线下商品待称重列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getOfflineWaitList(){
        $where = $this->where(where_delete());
        $where[] =['status','=',1];
        $where[] =['is_disable','=',0];
        $where[] =['source','=',1];
        $where[] =['is_weighing','=',0];
        $setting_call_id = MemberService::getMemberCallIds(member_id(true));
        $where[] = ['setting_call_id','in',$setting_call_id];
        $data = GoodsService::list($where, $this->page(), $this->limit(), $this->order(),'id,goods_label_id,image_id,title,original_price,price,sales_sum,click_count,spec,unit,stock,merchant_id,source,member_id,is_weighing,is_transaction,setting_call_id,setting_hall_id,create_time',[],3);
        return success($data);
    }
    /**
     * @Apidoc\Title("确认称重线下商品")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="确认称重", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function saveOfflineWait(){
        $param = $this->params([
            'list/a' => [],
        ]);
        validate(GoodsInventoryValidate::class)->scene('saveOfflineWait')->check($param);
        $data = GoodsService::saveOfflineWait($param['list']);
        return success($data,'完成称重');
    }
    /**
     * @Apidoc\Title("线下商品已称重列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="线下商品已称重列表", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
     * })
     */
    public function getOfflineSuccessList(){
        $where = $this->where(where_delete());
        $where[] =['status','=',1];
        $where[] =['is_disable','=',0];
        $where[] =['source','=',1];
        $where[] =['is_weighing','=',1];
        $where[] = ['weighing_uid','=',member_id(true)];
        $data = GoodsService::list($where, $this->page(), $this->limit(), $this->order(),'id,goods_label_id,image_id,title,original_price,price,sales_sum,click_count,spec,unit,stock,merchant_id,source,member_id,is_weighing,is_transaction,setting_call_id,setting_hall_id,create_time',[],3);
        return success($data);
    }

}
