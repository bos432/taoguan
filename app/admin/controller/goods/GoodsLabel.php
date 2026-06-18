<?php
namespace app\admin\controller\goods;
use app\common\controller\BaseController;
use app\common\service\goods\GoodsLabelService;
use app\common\validate\goods\GoodsLabelValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("商品标签")
 * @Apidoc\Group("goods")
 * @Apidoc\Sort("250")
 */
class GoodsLabel extends BaseController
{
    /**
    * @Apidoc\Title("商品标签列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="商品标签列表", children={
    *   @Apidoc\Returned(ref="app\common\model\GoodsLabelModel", field="id,title,remark,is_disable,create_time,update_time,sort")
    * })
    */
    public function list()
    {
        $where = $this->where(where_delete());
        $data = GoodsLabelService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("商品标签信息")
    * @Apidoc\Query(ref="app\common\model\GoodsLabelModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\GoodsLabelModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(GoodsLabelValidate::class)->scene('info')->check($param);
        $data = GoodsLabelService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品标签添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\GoodsLabelModel", field="title,remark,is_disable,sort")
    */
    public function add()
    {
        $param = $this->params(GoodsLabelService::$edit_field);
        validate(GoodsLabelValidate::class)->scene('add')->check($param);
        $data = GoodsLabelService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品标签修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\GoodsLabelModel", field="id,title,remark,is_disable,sort")
    */
    public function edit()
    {
        $param = $this->params(GoodsLabelService::$edit_field);
        validate(GoodsLabelValidate::class)->scene('edit')->check($param);
        $data = GoodsLabelService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品标签删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(GoodsLabelValidate::class)->scene('dele')->check($param);
        $data = GoodsLabelService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品标签禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\GoodsLabelModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(GoodsLabelValidate::class)->scene('disable')->check($param);
        $data = GoodsLabelService::edit($param['ids'], $param);
        return success($data);
    }
}
