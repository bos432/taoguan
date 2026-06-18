<?php
namespace app\admin\controller\setting;
use app\common\controller\BaseController;
use app\common\service\setting\SettingCallService;
use app\common\validate\setting\SettingCallValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("称管理")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("250")
 */
class SettingCall extends BaseController
{
    /**
    * @Apidoc\Title("称管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="称管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\setting\SettingCallModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark,setting_hall_id")
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

        $data['list']  = SettingCallService::list('tree', $where);
        $data['tree']  = SettingCallService::list('tree', [where_delete()], [], 'id,pid,title');
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
            $data['list'] = SettingCallService::list('tree', [[$pk, 'in', $ids], where_delete()]);
        }

        $type = SettingCallService::list('list', $where, [], 'id');
        $data['count'] = count($type);

        return success($data);
    }
    /**
     * @Apidoc\Title("查询秤")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="称管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\setting\SettingCallModel", field="id,pid,code,is_disable,create_time,update_time,title,sort,remark,setting_hall_id")
     * })
     */
    public function select()
    {
        $data = SettingCallService::list('tree', [where_delete()], [], 'id,pid,title,is_disable as disabled');
        return success($data);
    }
    /**
    * @Apidoc\Title("称管理信息")
    * @Apidoc\Query(ref="app\common\model\setting\SettingCallModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\setting\SettingCallModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(SettingCallValidate::class)->scene('info')->check($param);
        $data = SettingCallService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("称管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingCallModel", field="pid,code,is_disable,title,sort,remark,setting_hall_id")
    */
    public function add()
    {
        $param = $this->params(SettingCallService::$edit_field);
        validate(SettingCallValidate::class)->scene('add')->check($param);
        $data = SettingCallService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("称管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingCallModel", field="id,pid,code,is_disable,title,sort,remark,setting_hall_id")
    */
    public function edit()
    {
        $param = $this->params(SettingCallService::$edit_field);
        validate(SettingCallValidate::class)->scene('edit')->check($param);
        $data = SettingCallService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("称管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(SettingCallValidate::class)->scene('dele')->check($param);
        $data = SettingCallService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("称管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingCallModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(SettingCallValidate::class)->scene('disable')->check($param);
        $data = SettingCallService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("修改上级")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingCallModel", field="is_disable")
     */
    public function editpid()
    {
        $param = $this->params(['ids/a' => [], 'pid' => 0]);

        validate(SettingCallValidate::class)->scene('editpid')->check($param);

        $data = SettingCallService::edit($param['ids'], $param);

        return success($data);
    }
}
