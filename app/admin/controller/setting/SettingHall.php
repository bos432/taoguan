<?php
namespace app\admin\controller\setting;
use app\common\controller\BaseController;
use app\common\service\setting\SettingHallService;
use app\common\validate\setting\SettingHallValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("大厅管理")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("250")
 */
class SettingHall extends BaseController
{
    /**
    * @Apidoc\Title("大厅管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="大厅管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\setting\SettingHallModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
            'pid'
        ]);
        $where = $this->where(where_delete($where));

        $data['list']  = SettingHallService::list('tree', $where);
        $data['tree']  = SettingHallService::list('tree', [where_delete()], [], 'id,pid,title');
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
            $data['list'] = SettingHallService::list('tree', [[$pk, 'in', $ids], where_delete()]);
        }

        $type = SettingHallService::list('list', $where, [], 'id');
        $data['count'] = count($type);

        return success($data);
    }
    /**
     * @Apidoc\Title("大厅管理列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="大厅管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\setting\SettingHallModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark")
     * })
     */
    public function select()
    {
        $data = SettingHallService::list('tree', [where_delete()], [], 'id,pid,title,is_disable as disabled');
        return success($data);
    }
    /**
    * @Apidoc\Title("大厅管理信息")
    * @Apidoc\Query(ref="app\common\model\setting\SettingHallModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\setting\SettingHallModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(SettingHallValidate::class)->scene('info')->check($param);
        $data = SettingHallService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("大厅管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingHallModel", field="pid,code,is_disable,title,sort,remark")
    */
    public function add()
    {
        $param = $this->params(SettingHallService::$edit_field);
        validate(SettingHallValidate::class)->scene('add')->check($param);
        $data = SettingHallService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("大厅管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingHallModel", field="id,pid,code,is_disable,title,sort,remark")
    */
    public function edit()
    {
        $param = $this->params(SettingHallService::$edit_field);
        validate(SettingHallValidate::class)->scene('edit')->check($param);
        $data = SettingHallService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("大厅管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(SettingHallValidate::class)->scene('dele')->check($param);
        $data = SettingHallService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("大厅管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingHallModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(SettingHallValidate::class)->scene('disable')->check($param);
        $data = SettingHallService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("修改上级")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingHallModel", field="is_disable")
     */
    public function editpid()
    {
        $param = $this->params(['ids/a' => [], 'pid' => 0]);

        validate(SettingHallValidate::class)->scene('editpid')->check($param);

        $data = SettingHallService::edit($param['ids'], $param);

        return success($data);
    }
}
