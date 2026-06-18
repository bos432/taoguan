<?php
namespace app\merchant\controller\finance;
use app\common\controller\BaseController;
use app\common\service\finance\MerchantBillService;
use app\common\validate\finance\MerchantBillValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("账单明细")
 * @Apidoc\Group("finance")
 * @Apidoc\Sort("250")
 */
class MerchantBill extends BaseController
{
    /**
    * @Apidoc\Title("账单明细列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="账单明细列表", children={
    *   @Apidoc\Returned(ref="app\common\model\finance\MerchantBillModel", field="id,is_disable,create_time,update_time,mer_id,money,type,data_id,title,remark")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'type'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['mer_id','=',mer_id(true)];
        $data = MerchantBillService::list($where, $this->page(), $this->limit(), $this->order());
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
     * @Apidoc\Returned("list", type="array", desc="账单明细列表", children={
     *   @Apidoc\Returned(ref="app\common\model\finance\MerchantBillModel", field="id,is_disable,create_time,update_time,mer_id,money,type,data_id,title,remark")
     * })
     */
    public function getParams()
    {
        $data = MerchantBillService::getParams();
        return success($data);
    }
    /**
    * @Apidoc\Title("账单明细信息")
    * @Apidoc\Query(ref="app\common\model\finance\MerchantBillModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\finance\MerchantBillModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MerchantBillValidate::class)->scene('info')->check($param);
        $data = MerchantBillService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("账单明细添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\finance\MerchantBillModel", field="is_disable,mer_id,money,type,data_id,title,remark")
    */
    public function add()
    {
        $param = $this->params(MerchantBillService::$edit_field);
        validate(MerchantBillValidate::class)->scene('add')->check($param);
        $data = MerchantBillService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("账单明细修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\finance\MerchantBillModel", field="id,is_disable,mer_id,money,type,data_id,title,remark")
    */
    public function edit()
    {
        $param = $this->params(MerchantBillService::$edit_field);
        validate(MerchantBillValidate::class)->scene('edit')->check($param);
        $data = MerchantBillService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("账单明细删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MerchantBillValidate::class)->scene('dele')->check($param);
        $data = MerchantBillService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("账单明细禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\finance\MerchantBillModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MerchantBillValidate::class)->scene('disable')->check($param);
        $data = MerchantBillService::edit($param['ids'], $param);
        return success($data);
    }
}
