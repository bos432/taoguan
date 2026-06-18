<?php


namespace app\common\model\inspection;

use think\Model;
use app\common\model\file\InspectionFileModel;
use hg\apidoc\annotation as Apidoc;

/**
 * 用户管理模型
 */
class InspectionUserModel extends Model
{
    // 表名
    protected $name = 'inspection_user';
    // 表主键
    protected $pk = 'ins_user_id';

    // 关联头像文件
    public function avatar()
    {
        return $this->hasOne(InspectionFileModel::class, 'file_id', 'avatar_id')->append(['file_url'])->where(where_disdel());
    }
    // 关联检测机构
    public function inspection()
    {
        return $this->hasOne(InspectionModel::class, 'id', 'ins_id')->where(where_disdel());
    }
    /**
     * 获取检测机构名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("avatar_url", type="string", desc="头像链接")
     */
    public function getInsTitleAttr()
    {
        return $this['inspection']['title'] ?? '';
    }
    /**
     * 获取头像链接
     * @Apidoc\Field("")
     * @Apidoc\AddField("avatar_url", type="string", desc="头像链接")
     */
    public function getAvatarUrlAttr()
    {
        return $this['avatar']['file_url'] ?? '';
    }


    // 关联角色
    public function roles()
    {
        return $this->belongsToMany(InspectionRoleModel::class, InspectionUserAttributesModel::class, 'role_id', 'ins_user_id');
    }
    /**
     * 获取角色id
     * @Apidoc\Field("")
     * @Apidoc\AddField("role_ids", type="array", desc="角色id")
     */
    public function getRoleIdsAttr()
    {
        return relation_fields($this['roles'], 'role_id');
    }
    /**
     * 获取角色名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("role_names", type="string", desc="角色名称")
     */
    public function getRoleNamesAttr()
    {
        return relation_fields($this['roles'], 'role_name', true);
    }
}
