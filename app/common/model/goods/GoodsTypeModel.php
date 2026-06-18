<?php

namespace app\common\model\goods;
use app\common\model\file\FileModel;
use app\common\service\content\SettingService;
use think\Model;
use hg\apidoc\annotation as Apidoc;
class GoodsTypeModel extends Model
{
    // 表名
    protected $name = 'goods_type';
    // 表主键
    protected $pk = 'id';
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
    public function getImageUrlAttr()
    {
        $file_url = $this['image']['file_url'] ?? '';
        if (empty($file_url)) {
            $setting = SettingService::info();
            if ($setting['category_default_img_open']) {
                $file_url = $setting['category_default_img_url'] ?? '';
            }
        }
        return $file_url;
    }

    /**
     * @title:查询所有子集
     * @author：易军辉
     * @date：2024/12/11
     * @param $id
     * @return array
     */
    public function getSubIds($id)
    {
        // 获取当前 ID 的直接子级
        $subIds = $this->where('pid', $id)->column($this->pk);

        // 如果存在子级，递归查找其子级
        foreach ($subIds as $subId) {
            $subIds = array_merge($subIds, $this->getSubIds($subId));
        }

        return $subIds;
    }
}
