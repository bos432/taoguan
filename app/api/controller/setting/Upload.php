<?php


namespace app\api\controller\setting;

use app\common\controller\BaseController;
use app\common\validate\file\FileValidate;
use app\common\service\file\FileService;
use app\common\service\file\GroupService;
use app\common\service\file\TagService;
use app\common\service\file\SettingService;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("上传")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("380")
 */
class Upload extends BaseController
{
    /**
     * @Apidoc\Title("上传文件")
     * @Apidoc\Method("POST")
     * @Apidoc\ParamType("formdata")
     * @Apidoc\Param("group_id", type="string", require=false, desc="分组id或标识")
     * @Apidoc\Param("tag_ids", type="string", require=false, desc="标签id或标识，多个逗号隔开")
     * @Apidoc\Param(ref="app\common\model\file\FileModel", field="file_type,file_name")
     * @Apidoc\Param(ref="fileParam")
     * @Apidoc\Returned(ref="fileReturn")
     */
    public function file()
    {
        $setting = SettingService::info();
        if (!$setting['is_upload_api']) {
            return error('文件上传未开启，无法上传文件！');
        }

        $param = $this->params([
            'group_id'    => 0,
            'tag_ids'     => '',
            'file_type/s' => 'image',
            'file_name/s' => '',
        ]);
        $param['file']     = $this->request->file('file');
        $param['is_front'] = 1;

        validate(FileValidate::class)->scene('add')->check($param);

        if ($param['group_id']) {
            $group = GroupService::info($param['group_id'], false);
            $param['group_id'] = $group['group_id'] ?? 0;
        }
        $tag_ids = [];
        if ($param['tag_ids']) {
            if (is_string($param['tag_ids'])) {
                $param['tag_ids'] = explode(',', $param['tag_ids']);
            }
            foreach ($param['tag_ids'] as $tag_id) {
                $tag = tagService::info($tag_id, false);
                if ($tag) {
                    $tag_ids[] = $tag['tag_id'] ?? 0;
                }
            }
        }
        $param['tag_ids'] = $tag_ids;

        try {
            $data = FileService::add($param);
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: '文件上传失败，请稍后重试';
            if (stripos($message, 'mkdir') !== false || stripos($message, 'permission') !== false || stripos($message, 'permitted') !== false) {
                $message = '文件上传目录权限异常，请联系管理员检查 public/storage 权限';
            }
            return error($message);
        }

        return success($data, '上传成功');
    }
}
