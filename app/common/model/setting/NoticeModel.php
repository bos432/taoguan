<?php


namespace app\common\model\setting;

use think\Model;
use app\common\model\file\FileModel;
use app\common\service\setting\SettingService;
use hg\apidoc\annotation as Apidoc;

/**
 * 通告管理模型
 */
class NoticeModel extends Model
{
    // 表名
    protected $name = 'setting_notice';
    // 表主键
    protected $pk = 'notice_id';

    // 关联图片
    public function image()
    {
        return $this->hasOne(FileModel::class, 'file_id', 'image_id')->append(['file_url'])->where(where_disdel());
    }
    /**
     * 获取图片链接
     * @Apidoc\Field("")
     * @Apidoc\AddField("image_url", type="string", desc="图片链接")
     */
    public function getImageUrlAttr($value, $data)
    {
        return $this['image']['file_url'] ?? '';
    }

    /**
     * 获取类型名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("type_name", type="string", desc="类型名称")
     */
    public function getTypeNameAttr($value, $data)
    {
        return SettingService::noticeTypes($data['type']);
    }

    public function getPopupFrequencyNameAttr($value, $data)
    {
        return SettingService::popupFrequencies($data['popup_frequency'] ?? '');
    }

    public function getPopupScopeNameAttr($value, $data)
    {
        return SettingService::popupScopes($data['popup_scope'] ?? '');
    }

    public function getPopupJumpTypeNameAttr($value, $data)
    {
        return SettingService::popupJumpTypes($data['popup_jump_type'] ?? '');
    }
}
