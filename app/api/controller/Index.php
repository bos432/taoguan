<?php


namespace app\api\controller;

use app\common\controller\BaseController;
use app\api\service\IndexService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("首页")
 * @Apidoc\Group("index")
 * @Apidoc\Sort("100")
 */
class Index extends BaseController
{
    /**
     * @Apidoc\Title("首页")
     * @Apidoc\NotHeaders()
     * @Apidoc\NotQuerys()
     * @Apidoc\NotParams()
     */
    public function index()
    {
        $data = IndexService::index();
        $msg  = '后端安装成功，欢迎使用，如有帮助，敬请Star！';

        return success($data, $msg);
    }
}
