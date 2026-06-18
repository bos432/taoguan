<?php


namespace app\merchant\controller\system;

use app\common\controller\BaseController;
use app\common\validate\merchant\MerchantUserCenterValidate;
use app\common\validate\merchant\MerchantUserLogValidate;
use app\common\service\merchant\MerchantUserCenterService;
use app\common\service\merchant\MerchantUserLogService;
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
        $param['mer_user_id'] = mer_user_id(true);

        validate(MerchantUserCenterValidate::class)->scene('info')->check($param);

        $data = MerchantUserCenterService::info($param['mer_user_id']);
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
        $param['mer_user_id'] = mer_user_id(true);

        validate(MerchantUserCenterValidate::class)->scene('edit')->check($param);

        $data = MerchantUserCenterService::edit($param['mer_user_id'], $param);

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
        $param['mer_user_id'] = mer_user_id(true);

        validate(MerchantUserCenterValidate::class)->scene('pwd')->check($param);

        MerchantUserCenterService::pwd($param['mer_user_id'], $param);

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
        $param['mer_user_id'] = mer_user_id(true);

        validate(MerchantUserCenterValidate::class)->scene('log')->check($param);

        $where = $this->where(where_delete(['mer_user_id', '=', $param['mer_user_id']]));

        $data = MerchantUserCenterService::log($where, $this->page(), $this->limit(), $this->order());

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
        $mer_user_id = mer_user_id(true);

        validate(MerchantUserLogValidate::class)->scene('info')->check($param);

        $data = MerchantUserLogService::info($param['log_id']);
        if ($data['mer_user_id'] != $mer_user_id) {
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
        $mer_user_id = mer_user_id(true);

        validate(MerchantUserLogValidate::class)->scene('dele')->check($param);

        $data = MerchantUserLogService::dele($param['ids'], false, $mer_user_id);

        return success($data);
    }

    /**
     * @Apidoc\Title("鎴戠殑璁剧疆")
     */
    public function setting()
    {
        return success($this->runtimeData());
    }

    public function runtime()
    {
        return success($this->runtimeData());
    }

    private function runtimeData(): array
    {
        $param['mer_user_id'] = mer_user_id(true);

        validate(MerchantUserCenterValidate::class)->scene('info')->check($param);

        return MerchantUserCenterService::setting($param['mer_user_id']);
    }
}
