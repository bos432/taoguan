<?php
namespace app\merchant\controller\trace;
use app\common\controller\BaseController;
use app\common\service\trace\TraceTacheService;
use app\common\validate\trace\TraceTacheValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("环节模板")
 * @Apidoc\Group("trace")
 * @Apidoc\Sort("250")
 */
class TraceTache extends BaseController
{
    /**
    * @Apidoc\Title("环节模板列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="环节模板列表", children={
    *   @Apidoc\Returned(ref="app\common\model\TraceTacheModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['merchant_id','=',mer_id()];
        $data = TraceTacheService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
     * @Apidoc\Title("选择环节")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="环节模板列表", children={
     *   @Apidoc\Returned(ref="app\common\model\TraceTacheModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark")
     * })
     */
    public function select()
    {
        $where = $this->where(where_delete());
        $where[] = ['merchant_id','=',mer_id()];
        $data = TraceTacheService::list($where,0,0, $this->order(),'id,id as value,title as label,is_disable as disable');
        return success($data);
    }
    /**
    * @Apidoc\Title("环节模板信息")
    * @Apidoc\Query(ref="app\common\model\TraceTacheModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\TraceTacheModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(TraceTacheValidate::class)->scene('info')->check($param);
        $data = TraceTacheService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("环节模板添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceTacheModel", field="title,describe,is_disable,sort,remark")
    */
    public function add()
    {
        $param = $this->params(TraceTacheService::$edit_field);
        $param['merchant_id'] = mer_id();
        validate(TraceTacheValidate::class)->scene('add')->check($param);
        $data = TraceTacheService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("环节模板修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceTacheModel", field="id,title,describe,is_disable,sort,remark")
    */
    public function edit()
    {
        $param = $this->params(TraceTacheService::$edit_field);
        validate(TraceTacheValidate::class)->scene('edit')->check($param);
        $data = TraceTacheService::edit([$param['id']], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("环节模板删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(TraceTacheValidate::class)->scene('dele')->check($param);
        $data = TraceTacheService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("环节模板禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceTacheModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(TraceTacheValidate::class)->scene('disable')->check($param);
        $data = TraceTacheService::edit($param['ids'], $param);
        return success($data);
    }
}
