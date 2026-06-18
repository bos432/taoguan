<?php
namespace app\api\controller\member;
use app\common\controller\BaseController;
use app\common\service\member\MemberBillService;
use app\common\validate\member\MemberBillValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("会员账单")
 * @Apidoc\Group("member")
 * @Apidoc\Sort("250")
 */
class MemberBill extends BaseController
{
    /**
    * @Apidoc\Title("会员账单列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="会员账单列表", children={
    *   @Apidoc\Returned(ref="app\common\model\member\MemberBillModel", field="id,is_disable,create_time,update_time,member_id,title,in_out,money,bill_type_id,order_id,trans_id,remark")
    * })
    */
    public function list()
    {
        $where = $this->where(where_delete());
        $data = MemberBillService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("会员账单信息")
    * @Apidoc\Query(ref="app\common\model\member\MemberBillModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\member\MemberBillModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MemberBillValidate::class)->scene('info')->check($param);
        $data = MemberBillService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("会员账单添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberBillModel", field="is_disable,member_id,title,in_out,money,bill_type_id,order_id,trans_id,remark")
    */
    public function add()
    {
        $param = $this->params(MemberBillService::$edit_field);
        validate(MemberBillValidate::class)->scene('add')->check($param);
        $data = MemberBillService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("会员账单修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberBillModel", field="id,is_disable,member_id,title,in_out,money,bill_type_id,order_id,trans_id,remark")
    */
    public function edit()
    {
        $param = $this->params(MemberBillService::$edit_field);
        validate(MemberBillValidate::class)->scene('edit')->check($param);
        $data = MemberBillService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("会员账单删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MemberBillValidate::class)->scene('dele')->check($param);
        $data = MemberBillService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("会员账单禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberBillModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MemberBillValidate::class)->scene('disable')->check($param);
        $data = MemberBillService::edit($param['ids'], $param);
        return success($data);
    }
}
