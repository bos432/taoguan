<?php


namespace app\common\model\merchant;

use think\Model;
use hg\apidoc\annotation as Apidoc;

/**
 * 用户日志模型
 */
class MerchantUserLogModel extends Model
{
    // 表名
    protected $name = 'merchant_user_log';
    // 表主键
    protected $pk = 'log_id';

    // 修改请求参数
    public function setRequestParamAttr($value)
    {
        return serialize($value);
    }
    // 获取请求参数
    public function getRequestParamAttr($value)
    {
        return unserialize($value);
    }

    // 关联用户
    public function user()
    {
        return $this->hasOne(MerchantUserModel::class, 'mer_user_id', 'mer_user_id');
    }
    // 获取用户昵称
    public function getNicknameAttr()
    {
        return $this['user']['nickname'] ?? '';
    }
    // 获取用户账号
    public function getUsernameAttr()
    {
        return $this['user']['username'] ?? '';
    }

    // 关联菜单
    public function menu()
    {
        return $this->hasOne(MerchantMenuModel::class, 'menu_id', 'menu_id');
    }
    // 获取菜单名称
    public function getMenuNameAttr()
    {
        return $this['menu']['menu_name'] ?? '';
    }
    // 获取菜单链接
    public function getMenuUrlAttr($value, $data)
    {
        return $this['menu']['menu_url'] ?? $data['request_url'] ?? '';
    }
}
