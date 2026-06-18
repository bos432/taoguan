<?php


namespace app\common\model\member;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 会员分组模型
 */
class GroupModel extends Model
{
    // 表名
    protected $name = 'member_group';
    // 表主键
    protected $pk = 'group_id';

    // 关联接口
    public function apis()
    {
        return $this->belongsToMany(ApiModel::class, GroupApisModel::class, 'api_id', 'group_id');
    }
    /**
     * 获取接口id
     * @Apidoc\Field("")
     * @Apidoc\AddField("api_ids", type="array", desc="接口id")
     */
    public function getApiIdsAttr()
    {
        return relation_fields($this['apis'], 'api_id');
    }
}
