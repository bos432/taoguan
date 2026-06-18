<?php


namespace app\inspection\controller\system;

use app\common\controller\BaseController;
use app\common\validate\inspection\InspectionUserValidate;
use app\common\service\inspection\InspectionUserService;
use app\common\service\inspection\InspectionRoleService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("用户管理")
 * @Apidoc\Group("system")
 * @Apidoc\Sort("500")
 */
class User extends BaseController
{
    /**
     * @Apidoc\Title("用户列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="用户列表", children={
     *   @Apidoc\Returned(ref="app\common\model\system\UserModel", field="user_id,nickname,username,sort,is_super,is_disable,create_time,update_time"),
     *   @Apidoc\Returned(ref="app\common\model\system\UserModel\getAvatarUrlAttr", field="avatar_url"),
     *   @Apidoc\Returned(ref="app\common\model\system\UserModel\getDeptNamesAttr", field="dept_names"),
     *   @Apidoc\Returned(ref="app\common\model\system\UserModel\getPostNamesAttr", field="post_names"),
     *   @Apidoc\Returned(ref="app\common\model\system\UserModel\getRoleNamesAttr", field="role_names"),
     * })
     * @Apidoc\Returned("dept", ref="app\common\model\system\DeptModel", type="tree", desc="部门树形", field="dept_id,dept_pid,dept_name")
     * @Apidoc\Returned("post", ref="app\common\model\system\PostModel", type="tree", desc="职位树形", field="post_id,post_pid,post_name")
     * @Apidoc\Returned("role", ref="app\common\model\system\RoleModel", type="array", desc="角色列表", field="role_id,role_name")
     */
    public function list()
    {
        $where = $this->buildWhere([
            'role_ids',
            'is_super',
            'is_disable'
        ]);
        $where = $this->where(where_delete($where));

        $data = InspectionUserService::list($where, $this->page(), $this->limit(), $this->order());

        $data['role']  = InspectionRoleService::list([where_delete()], 0, 0, [], 'role_id,role_name')['list'] ?? [];
        $data['exps']  = where_exps();
        $data['where'] = $where;

        return success($data);
    }

    /**
     * @Apidoc\Title("用户信息")
     * @Apidoc\Query(ref="app\common\model\system\UserModel", field="user_id")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getAvatarUrlAttr", field="avatar_url")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getDeptIdsAttr", field="dept_ids")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getPostIdsAttr", field="post_ids")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getRoleIdsAttr", field="role_ids")
     */
    public function info()
    {
        $param = $this->params(['ins_user_id/d' => '']);

        validate(InspectionUserValidate::class)->scene('info')->check($param);

        $data = InspectionUserService::info($param['ins_user_id'], true, true);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户添加")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="avatar_id,nickname,username,password,phone,email,remark,sort")
     */
    public function add()
    {
        $param = $this->params(InspectionUserService::$edit_field);
        $param['password'] = $this->param('password');

        validate(InspectionUserValidate::class)->scene('add')->check($param);

        $data = InspectionUserService::add($param);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="user_id,avatar_id,nickname,username,password,phone,email,remark,sort")
     */
    public function edit()
    {
        $param = $this->params(InspectionUserService::$edit_field);

        validate(InspectionUserValidate::class)->scene('edit')->check($param);

        $data = InspectionUserService::edit($param['ins_user_id'], $param);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户删除")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);

        validate(InspectionUserValidate::class)->scene('dele')->check($param);

        $data = InspectionUserService::dele($param['ids']);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户修改角色")
     * @Apidoc\Method("GET,POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\system\UserModel\getRoleIdsAttr", field="role_ids")
     */
    public function editrole()
    {
        $param = $this->params(['ids/a' => [], 'role_ids/a' => []]);

        validate(InspectionUserValidate::class)->scene('editrole')->check($param);

        $data = InspectionUserService::edit($param['ids'], $param);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户重置密码")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="password")
     */
    public function repwd()
    {
        $param = $this->params(['ids/a' => [], 'password/s' => '']);

        validate(InspectionUserValidate::class)->scene('repwd')->check($param);

        InspectionUserService::edit($param['ids'], $param);

        return success();
    }

    /**
     * @Apidoc\Title("用户是否超管")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="is_super")
     */
    public function super()
    {
        $param = $this->params(['ids/a' => [], 'is_super/d' => 0]);

        validate(InspectionUserValidate::class)->scene('super')->check($param);

        $data = InspectionUserService::edit($param['ids'], $param);

        return success($data);
    }

    /**
     * @Apidoc\Title("用户是否禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);

        validate(InspectionUserValidate::class)->scene('disable')->check($param);

        $data = InspectionUserService::edit($param['ids'], $param);

        return success($data);
    }
}
