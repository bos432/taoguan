<?php

namespace app\admin\controller\inspection;
use app\common\controller\BaseController;
use app\common\service\inspection\InspectionService;
use app\common\validate\inspection\InspectionValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("检测机构管理")
 * @Apidoc\Group("inspection")
 * @Apidoc\Sort("250")
 */
class Inspection extends BaseController
{
    /**
    * @Apidoc\Title("检测机构管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="检测机构管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\InspectionModel", field="id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
    * })
    */
    public function list()
    {
        $where = $this->where(where_delete());
        $data = InspectionService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("检测机构管理信息")
    * @Apidoc\Query(ref="app\common\model\InspectionModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\InspectionModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(InspectionValidate::class)->scene('info')->check($param);
        $data = InspectionService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测机构管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\InspectionModel", field="title,content,is_disable,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
    */
    public function add()
    {
        $param = $this->params(InspectionService::$edit_field);
        validate(InspectionValidate::class)->scene('add')->check($param);
        $data = InspectionService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测机构管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\InspectionModel", field="id,title,content,is_disable,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark")
    */
    public function edit()
    {
        $param = $this->params(InspectionService::$edit_field);
        validate(InspectionValidate::class)->scene('edit')->check($param);
        $data = InspectionService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("检测机构管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(InspectionValidate::class)->scene('dele')->check($param);
        $data = InspectionService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("检测机构管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\InspectionModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(InspectionValidate::class)->scene('disable')->check($param);
        $data = InspectionService::edit($param['ids'], $param);
        return success($data);
    }
}
