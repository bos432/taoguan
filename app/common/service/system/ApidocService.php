<?php


namespace app\common\service\system;

/**
 * 接口文档
 */
class ApidocService
{
    /**
     * 接口文档
     *
     * @return array
     */
    public static function apidoc()
    {
        $data['apidoc_url'] = server_url() . '/apidoc';
        $data['apidoc_pwd'] = config('apidoc.auth.password');
        return $data;
    }
}
