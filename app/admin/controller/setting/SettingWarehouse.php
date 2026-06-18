<?php
namespace app\admin\controller\setting;
use app\common\controller\BaseController;
use app\common\service\setting\SettingWarehouseService;
use app\common\validate\setting\SettingWarehouseValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("仓库管理")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("250")
 */
class SettingWarehouse extends BaseController
{
    /**
    * @Apidoc\Title("仓库管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="仓库管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\setting\SettingWarehouseModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
            'setting_hall_id',
            'pid'
        ]);
        $where = $this->where(where_delete($where));

        $data['list']  = SettingWarehouseService::list('tree', $where);
        $data['tree']  = SettingWarehouseService::list('tree', [where_delete()], [], 'id,pid,title');
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
            $data['list'] = SettingWarehouseService::list('tree', [[$pk, 'in', $ids], where_delete()]);
        }

        $type = SettingWarehouseService::list('list', $where, [], 'id');
        $data['count'] = count($type);

        return success($data);
    }
    /**
     * @Apidoc\Title("查询仓库")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="仓库管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\setting\SettingWarehouseModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark")
     * })
     */
    public function select()
    {
        $data = SettingWarehouseService::list('tree', [where_delete()], [], 'id,pid,title,is_disable as disabled');
        return success($data);
    }
    /**
    * @Apidoc\Title("仓库管理信息")
    * @Apidoc\Query(ref="app\common\model\setting\SettingWarehouseModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\setting\SettingWarehouseModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(SettingWarehouseValidate::class)->scene('info')->check($param);
        $data = SettingWarehouseService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("仓库管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingWarehouseModel", field="pid,code,is_disable,title,sort,remark")
    */
    public function add()
    {
        $param = $this->params(SettingWarehouseService::$edit_field);
        validate(SettingWarehouseValidate::class)->scene('add')->check($param);
        $data = SettingWarehouseService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("仓库管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingWarehouseModel", field="id,pid,code,is_disable,title,sort,remark")
    */
    public function edit()
    {
        $param = $this->params(SettingWarehouseService::$edit_field);
        validate(SettingWarehouseValidate::class)->scene('edit')->check($param);
        $data = SettingWarehouseService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("仓库管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(SettingWarehouseValidate::class)->scene('dele')->check($param);
        $data = SettingWarehouseService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("仓库管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingWarehouseModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(SettingWarehouseValidate::class)->scene('disable')->check($param);
        $data = SettingWarehouseService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("修改上级")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingWarehouseModel", field="is_disable")
     */
    public function editpid()
    {
        $param = $this->params(['ids/a' => [], 'pid' => 0]);

        validate(SettingWarehouseValidate::class)->scene('editpid')->check($param);

        $data = SettingWarehouseService::edit($param['ids'], $param);

        return success($data);
    }
}
