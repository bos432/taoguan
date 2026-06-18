<?php


namespace app\admin\controller\system;

use app\common\controller\BaseController;
use app\common\service\system\ApidocService;
use hg\apidoc\annotation as Apidocs;

/**
 * @Apidocs\Title("接口文档")
 * @Apidocs\Group("system")
 * @Apidocs\Sort("900")
 */
class Apidoc extends BaseController
{
    /**
     * @Apidocs\Title("接口文档")
     * @Apidocs\Returned("apidoc_url", type="string", desc="接口文档链接")
     * @Apidocs\Returned("apidoc_pwd", type="string", desc="接口文档密码")
     */
    public function apidoc()
    {
        $data = ApidocService::apidoc();

        return success($data);
    }
}
