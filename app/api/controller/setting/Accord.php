<?php

namespace app\api\controller\setting;

use app\common\controller\BaseController;
use app\common\service\setting\AccordAcceptService;
use app\common\service\setting\AccordService;
use app\common\validate\setting\AccordValidate;

class Accord extends BaseController
{
    public function list()
    {
        $name = $this->param('name/s', '');
        $unique = $this->param('unique/s', '');

        return success(AccordService::frontendList($name, $unique));
    }

    public function info()
    {
        $param = $this->params(['accord_id/s' => '']);

        validate(AccordValidate::class)->scene('info')->check($param);

        $data = AccordService::frontendInfo($param['accord_id']);
        if (empty($data)) {
            return error('协议不存在');
        }

        return success($data);
    }

    public function accept()
    {
        $param = $this->params([
            'scene/s' => '',
            'accord_uniques/a' => [],
        ]);

        $data = AccordAcceptService::acceptMemberAccords(member_id(true), $param['accord_uniques'], $param['scene']);

        return success($data, '记录成功');
    }

    public function status()
    {
        $param = $this->params([
            'accord_uniques/a' => [],
        ]);

        $data = AccordAcceptService::statusMemberAccords(member_id(false), $param['accord_uniques']);

        return success($data);
    }
}
