<?php

namespace app\admin\controller\goods;
use app\common\controller\BaseController;
use app\common\service\content\CategoryService;
use app\common\service\goods\GoodsTypeService;
use app\common\validate\goods\GoodsTypeValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("商品分类")
 * @Apidoc\Group("goods")
 * @Apidoc\Sort("250")
 */
class GoodsType extends BaseController
{
    /**
    * @Apidoc\Title("商品分类列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="商品分类列表", children={
    *   @Apidoc\Returned(ref="app\common\model\goods\GoodsTypeModel", field="id,code,title,is_disable,create_time,update_time,image_id")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
            'pid'
        ]);
        $where = $this->where(where_delete($where));

        $data['list']  = GoodsTypeService::list('tree', $where);
        $data['tree']  = GoodsTypeService::list('tree', [where_delete()], [], 'id,pid,title');
        $data['exps']  = where_exps();
        $data['where'] = $where;

        if (count($where) > 1) {
            $list = tree_to_list($data['list']);
            $all  = tree_to_list($data['tree']);
            $pk   = 'id';
            $pid  = 'pid';
            $ids  = [];
            foreach ($list as $val) {
                $pids = children_parent_ids($all, $val[$pk], $pk, $pid);
                $cids = parent_children_ids($all, $val[$pk], $pk, $pid);
                $ids  = array_merge($ids, $pids, $cids);
            }
            $data['list'] = GoodsTypeService::list('tree', [[$pk, 'in', $ids], where_delete()]);
        }

        $type = GoodsTypeService::list('list', $where, [], 'id');
        $data['count'] = count($type);

        return success($data);
    }
    /**
    * @Apidoc\Title("商品分类信息")
    * @Apidoc\Query(ref="app\common\model\goods\GoodsTypeModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\goods\GoodsTypeModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(GoodsTypeValidate::class)->scene('info')->check($param);
        $data = GoodsTypeService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品分类添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsTypeModel", field="code,title,is_disable,image_id")
    */
    public function add()
    {
        $param = $this->params(GoodsTypeService::$edit_field);
        validate(GoodsTypeValidate::class)->scene('add')->check($param);
        $data = GoodsTypeService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品分类修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsTypeModel", field="id,code,title,is_disable,image_id")
    */
    public function edit()
    {
        $param = $this->params(GoodsTypeService::$edit_field);
        validate(GoodsTypeValidate::class)->scene('edit')->check($param);
        $data = GoodsTypeService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品分类删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(GoodsTypeValidate::class)->scene('dele')->check($param);
        $data = GoodsTypeService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品分类禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsTypeModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(GoodsTypeValidate::class)->scene('disable')->check($param);
        $data = GoodsTypeService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品分类修改上级")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsTypeModel", field="pid")
     */
    public function editpid()
    {
        $param = $this->params(['ids/a' => [], 'pid' => 0]);

        validate(GoodsTypeValidate::class)->scene('editpid')->check($param);

        $data = GoodsTypeService::edit($param['ids'], $param);

        return success($data);
    }
}
