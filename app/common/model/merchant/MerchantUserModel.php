<?php


namespace app\common\model\merchant;

use think\Model;
use app\common\model\file\MerchantFileModel;
use hg\apidoc\annotation as Apidoc;

/**
 * 用户管理模型
 */
class MerchantUserModel extends Model
{
    // 表名
    protected $name = 'merchant_user';
    // 表主键
    protected $pk = 'mer_user_id';

    // 关联头像文件
    public function avatar()
    {
        return $this->hasOne(MerchantFileModel::class, 'file_id', 'avatar_id')->append(['file_url'])->where(where_disdel());
    }
    // 关联商家
    public function merchant()
    {
        return $this->hasOne(MerchantModel::class, 'id', 'mer_id')->where(where_disdel());
    }
    /**
     * 获取商家名称
     * @Apidoc\Field("")
     * @Apidoc\AddField("avatar_url", type="string", desc="头像链接")
     */
    public function getMerTitleAttr()
    {
        return $this['merchant']['title'] ?? '';
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
        return $this->belongsToMany(MerchantRoleModel::class, MerchantUserAttributesModel::class, 'role_id', 'mer_user_id');
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
