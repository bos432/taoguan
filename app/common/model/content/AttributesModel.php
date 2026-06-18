<?php


namespace app\common\model\content;

use think\model\Pivot;

/**
 * 内容属性关联模型
 */
class AttributesModel extends Pivot
{
    // 表名
    protected $name = 'content_attributes';

    // 关联内容
    public function content()
    {
        return $this->hasMany(ContentModel::class, 'content_id', 'content_id')->where([where_delete()]);
    }

    // 关联分类
    public function category()
    {
        return $this->hasOne(CategoryModel::class, 'category_id', 'category_id')->where([where_delete()]);
    }
    // 获取分类名称
    public function getCategoryNameAttr()
    {
        return $this['category']['category_name'] ?? '';
    }
}
