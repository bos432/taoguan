<?php


namespace app\api\controller\member;

use app\api\service\LoginService;
use app\common\controller\BaseController;
use app\common\validate\member\MemberValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("退出")
 * @Apidoc\Group("member")
 * @Apidoc\Sort("210")
 */
class Logout extends BaseController
{
    /**
     * @Apidoc\Title("退出")
     * @Apidoc\Method("POST")
     * @Apidoc\Before(event="clearGlobalHeader", key="ApiToken")
     * @Apidoc\Before(event="clearGlobalQuery", key="ApiToken")
     * @Apidoc\Before(event="clearGlobalBody", key="ApiToken")
     */
    public function logout()
    {
        $param['member_id'] = member_id();

        validate(MemberValidate::class)->scene('logout')->check($param);

        $data = LoginService::logout($param['member_id']);

        return success($data, '退出成功');
    }
}
