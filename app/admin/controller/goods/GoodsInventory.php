<?php
namespace app\admin\controller\goods;
use app\common\controller\BaseController;
use app\common\service\goods\GoodsInventoryService;
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
    * @Apidoc\Title("出入库明细信息")
    * @Apidoc\Query(ref="app\common\model\goods\GoodsInventoryModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\goods\GoodsInventoryModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(GoodsInventoryValidate::class)->scene('info')->check($param);
        $data = GoodsInventoryService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("出入库明细添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsInventoryModel", field="is_disable,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
    */
    public function add()
    {
        $param = $this->params(GoodsInventoryService::$edit_field);
        validate(GoodsInventoryValidate::class)->scene('add')->check($param);
        $data = GoodsInventoryService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("出入库明细修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsInventoryModel", field="id,is_disable,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id")
    */
    public function edit()
    {
        $param = $this->params(GoodsInventoryService::$edit_field);
        validate(GoodsInventoryValidate::class)->scene('edit')->check($param);
        $data = GoodsInventoryService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("出入库明细删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(GoodsInventoryValidate::class)->scene('dele')->check($param);
        $data = GoodsInventoryService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("出入库明细禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsInventoryModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(GoodsInventoryValidate::class)->scene('disable')->check($param);
        $data = GoodsInventoryService::edit($param['ids'], $param);
        return success($data);
    }
}
