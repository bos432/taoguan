<?php
namespace app\api\controller\member;
use app\common\controller\BaseController;
use app\common\service\member\MemberOrderLogService;
use app\common\validate\member\MemberOrderLogValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("订单日志")
 * @Apidoc\Group("member")
 * @Apidoc\Sort("250")
 */
class MemberOrderLog extends BaseController
{
    /**
    * @Apidoc\Title("订单日志列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="订单日志列表", children={
    *   @Apidoc\Returned(ref="app\common\model\order\MemberOrderLogModel", field="id,title,content,is_disable,create_time,update_time,member_order_id,role_type")
    * })
    */
    public function list()
    {
        $where = $this->where(where_delete());
        $data = MemberOrderLogService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("订单日志信息")
    * @Apidoc\Query(ref="app\common\model\order\MemberOrderLogModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\order\MemberOrderLogModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MemberOrderLogValidate::class)->scene('info')->check($param);
        $data = MemberOrderLogService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单日志添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\order\MemberOrderLogModel", field="title,content,is_disable,member_order_id,role_type")
    */
    public function add()
    {
        $param = $this->params(MemberOrderLogService::$edit_field);
        validate(MemberOrderLogValidate::class)->scene('add')->check($param);
        $data = MemberOrderLogService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单日志修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\order\MemberOrderLogModel", field="id,title,content,is_disable,member_order_id,role_type")
    */
    public function edit()
    {
        $param = $this->params(MemberOrderLogService::$edit_field);
        validate(MemberOrderLogValidate::class)->scene('edit')->check($param);
        $data = MemberOrderLogService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("订单日志删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MemberOrderLogValidate::class)->scene('dele')->check($param);
        $data = MemberOrderLogService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("订单日志禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\order\MemberOrderLogModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MemberOrderLogValidate::class)->scene('disable')->check($param);
        $data = MemberOrderLogService::edit($param['ids'], $param);
        return success($data);
    }
}
