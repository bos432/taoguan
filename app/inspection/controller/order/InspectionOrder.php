<?php
namespace app\inspection\controller\order;
use app\common\controller\BaseController;
use app\common\service\order\InspectionOrderService;
use app\common\validate\order\InspectionOrderValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("检测订单")
 * @Apidoc\Group("order")
 * @Apidoc\Sort("250")
 */
class InspectionOrder extends BaseController
{
    /**
    * @Apidoc\Title("检测订单列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="检测订单列表", children={
    *   @Apidoc\Returned(ref="app\common\model\order\InspectionOrderModel", field="id,inspection_id,is_disable,create_time,update_time,merchant_id,goods_id,trace_batch_id,trace_batch_tache_id,trace_batch_tache_value_id,title,remark,inspection_state,inspection_remark,inspection_time,inspection_uid,inspection_reports_ids,price,settlement_status,inspection_result")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
            'merchant_id',
            'goods_id',
            'trace_batch_id',
            'inspection_state'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['inspection_id','=',ins_id()];
        $data = InspectionOrderService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
     * @Apidoc\Title("查询参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="查询参数", children={
     *   @Apidoc\Returned(ref="app\common\model\order\InspectionOrderModel", field="id,inspection_id,is_disable,create_time,update_time,merchant_id,goods_id,trace_batch_id,trace_batch_tache_id,trace_batch_tache_value_id,title,remark,inspection_state,inspection_remark,inspection_time,inspection_uid,inspection_reports_ids,price,settlement_status,inspection_result")
     * })
     */
    public function getParams()
    {
        $data = InspectionOrderService::getParams();
        return success($data);
    }
    /**
    * @Apidoc\Title("检测订单信息")
    * @Apidoc\Query(ref="app\common\model\order\InspectionOrderModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\order\InspectionOrderModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(InspectionOrderValidate::class)->scene('info')->check($param);
        $data = InspectionOrderService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测订单添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\order\InspectionOrderModel", field="inspection_id,is_disable,merchant_id,goods_id,trace_batch_id,trace_batch_tache_id,trace_batch_tache_value_id,title,remark,inspection_state,inspection_remark,inspection_time,inspection_uid,inspection_reports_ids,price,settlement_status,inspection_result")
    */
    public function add()
    {
        $param = $this->params(InspectionOrderService::$edit_field);
        validate(InspectionOrderValidate::class)->scene('add')->check($param);
        $data = InspectionOrderService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测订单修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\order\InspectionOrderModel", field="id,inspection_id,is_disable,merchant_id,goods_id,trace_batch_id,trace_batch_tache_id,trace_batch_tache_value_id,title,remark,inspection_state,inspection_remark,inspection_time,inspection_uid,inspection_reports_ids,price,settlement_status,inspection_result")
    */
    public function edit()
    {
        $param = $this->params(InspectionOrderService::$edit_field);
        validate(InspectionOrderValidate::class)->scene('edit')->check($param);
        $data = InspectionOrderService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测订单删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(InspectionOrderValidate::class)->scene('dele')->check($param);
        $data = InspectionOrderService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("检测订单禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\order\InspectionOrderModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(InspectionOrderValidate::class)->scene('disable')->check($param);
        $data = InspectionOrderService::edit($param['ids'], $param);
        return success($data);
    }
}
