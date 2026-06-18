<?php
namespace app\merchant\controller\trace;
use app\common\controller\BaseController;
use app\common\service\trace\TraceBatchTacheService;
use app\common\validate\trace\TraceBatchTacheValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("批次环节录入")
 * @Apidoc\Group("trace")
 * @Apidoc\Sort("250")
 */
class TraceBatchTache extends BaseController
{
    /**
    * @Apidoc\Title("批次环节录入列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="批次环节录入列表", children={
    *   @Apidoc\Returned(ref="app\common\model\TraceBatchTacheModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark,merchant_id,trace_batch_id,goods_id,trace_tache_id,value")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'trace_batch_id',
            'goods_id',
            'trace_tache_id',
            'is_disable'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['merchant_id','=',mer_id()];
        $data = TraceBatchTacheService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("批次环节录入信息")
    * @Apidoc\Query(ref="app\common\model\TraceBatchTacheModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\TraceBatchTacheModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(TraceBatchTacheValidate::class)->scene('info')->check($param);
        $data = TraceBatchTacheService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次环节录入添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceBatchTacheModel", field="title,describe,is_disable,sort,remark,merchant_id,trace_batch_id,goods_id,trace_tache_id,value")
    */
    public function add()
    {
        $param = $this->params(TraceBatchTacheService::$edit_field);
        $param['merchant_id'] = mer_id();
        validate(TraceBatchTacheValidate::class)->scene('add')->check($param);
        $data = TraceBatchTacheService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次环节录入修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceBatchTacheModel", field="id,title,describe,is_disable,sort,remark,merchant_id,trace_batch_id,goods_id,trace_tache_id,value")
    */
    public function edit()
    {
        $param = $this->params(TraceBatchTacheService::$edit_field);
        validate(TraceBatchTacheValidate::class)->scene('edit')->check($param);
        $data = TraceBatchTacheService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次环节录入删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(TraceBatchTacheValidate::class)->scene('dele')->check($param);
        $data = TraceBatchTacheService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("批次环节录入禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceBatchTacheModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(TraceBatchTacheValidate::class)->scene('disable')->check($param);
        $data = TraceBatchTacheService::edit($param['ids'], $param);
        return success($data);
    }
}
