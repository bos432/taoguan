<?php


namespace app\inspection\controller\system;

use app\common\controller\BaseController;
use app\common\validate\inspection\InspectionUserCenterValidate;
use app\common\validate\inspection\InspectionUserLogValidate;
use app\common\service\inspection\InspectionUserCenterService;
use app\common\service\inspection\InspectionUserLogService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("个人中心")
 * @Apidoc\Group("system")
 * @Apidoc\Sort("800")
 */
class UserCenter extends BaseController
{
    /**
     * @Apidoc\Title("我的信息")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel", withoutField="password")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getAvatarUrlAttr", field="avatar_url")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getDeptIdsAttr", field="dept_ids")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getPostIdsAttr", field="post_ids")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel\getRoleIdsAttr", field="role_ids")
     * @Apidoc\Returned("menus", type="array", desc="菜单路由")
     * @Apidoc\Returned("roles", type="array", desc="菜单链接（权限标识）")
     */
    public function info()
    {
        $param['ins_user_id'] = ins_user_id(true);

        validate(InspectionUserCenterValidate::class)->scene('info')->check($param);

        $data = InspectionUserCenterService::info($param['ins_user_id']);
        if ($data['is_delete'] == 1) {
            exception('账号已被删除！');
        }

        return success($data);
    }

    /**
     * @Apidoc\Title("修改信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\system\UserModel", field="avatar_id,nickname,username,phone,email")
     */
    public function edit()
    {
        $param = $this->params([
            'avatar_id/d' => 0,
            'nickname/s'  => '',
            'username/s'  => '',
            'phone/s'     => '',
            'email/s'     => '',
        ]);
        $param['ins_user_id'] = ins_user_id(true);

        validate(InspectionUserCenterValidate::class)->scene('edit')->check($param);

        $data = InspectionUserCenterService::edit($param['ins_user_id'], $param);

        return success($data);
    }

    /**
     * @Apidoc\Title("修改密码")
     * @Apidoc\Method("POST")
     * @Apidoc\Param("password_old", type="string", require=true, desc="旧密码")
     * @Apidoc\Param("password_new", type="string", require=true, desc="新密码")
     */
    public function pwd()
    {
        $param = $this->params([
            'password_old/s' => '',
            'password_new/s' => '',
        ]);
        $param['ins_user_id'] = ins_user_id(true);

        validate(InspectionUserCenterValidate::class)->scene('pwd')->check($param);

        InspectionUserCenterService::pwd($param['ins_user_id'], $param);

        return success();
    }

    /**
     * @Apidoc\Title("我的日志")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="日志列表", children={
     *   @Apidoc\Returned(ref="app\common\model\system\UserLogModel"),
     *   @Apidoc\Returned(ref="app\common\model\system\MenuModel", field="menu_name,menu_url")
     * })
     */
    public function log()
    {
        $param['ins_user_id'] = ins_user_id(true);

        validate(InspectionUserCenterValidate::class)->scene('log')->check($param);

        $where = $this->where(where_delete(['ins_user_id', '=', $param['ins_user_id']]));

        $data = InspectionUserCenterService::log($where, $this->page(), $this->limit(), $this->order());

        $data['exps']  = where_exps();
        $data['where'] = $where;

        return success($data);
    }

    /**
     * @Apidoc\Title("我的日志信息")
     * @Apidoc\Query(ref="app\common\model\system\UserLogModel", field="log_id")
     * @Apidoc\Returned(ref="app\common\model\system\UserLogModel")
     * @Apidoc\Returned(ref="app\common\model\system\UserModel", field="nickname,username")
     * @Apidoc\Returned(ref="app\common\model\system\MenuModel", field="menu_name,menu_url")
     */
    public function logInfo()
    {
        $param   = $this->params(['log_id/d' => '']);
        $ins_user_id = ins_user_id(true);

        validate(InspectionUserLogValidate::class)->scene('info')->check($param);

        $data = InspectionUserLogService::info($param['log_id']);
        if ($data['ins_user_id'] != $ins_user_id) {
            $data = [];
        }

        return success($data);
    }

    /**
     * @Apidoc\Title("我的日志删除")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function logDele()
    {
        $param   = $this->params(['ids/a' => []]);
        $ins_user_id = ins_user_id(true);

        validate(InspectionUserLogValidate::class)->scene('dele')->check($param);

        $data = InspectionUserLogService::dele($param['ids'], false, $ins_user_id);

        return success($data);
    }
}
