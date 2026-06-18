<?php


namespace app\common\model\setting;

use think\Model;
use app\common\model\file\FileModel;
use hg\apidoc\annotation as Apidoc;

/**
 * 友链管理模型
 */
class LinkModel extends Model
{
    // 表名
    protected $name = 'setting_link';
    // 表主键
    protected $pk = 'link_id';

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
}
