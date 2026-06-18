<?php

namespace app\common\validate\merchant;

use app\common\model\merchant\MerchantModel;
use think\Validate;

class MerchantValidate extends Validate
{
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => ['require', 'checkExisted'],
        'password' => 'require',
        'username' => ['require', 'checkAccountExisted'],
        'phone' => ['mobile', 'checkPhone'],
        'is_disable' => ['require', 'in' => '0,1'],
        'sync_goods_disable' => ['in' => '0,1'],
        'member_is_super' => ['require', 'in' => '0,1'],
        'name' => 'require',
        'images' => 'require',
        'image_id' => 'require',
        'auth_state' => ['require', 'in' => '1,2'],
        'renew_months' => ['integer'],
        'renew_days' => ['integer'],
        'renew_remind_days' => ['integer', 'egt' => 1],
    ];

    protected $message = [
        'title.require' => '请输入商家名称',
        'username.require' => '请输入商家账号',
        'password.require' => '请输入登录密码',
        'name.require' => '请输入商家联系人',
        'images.require' => '请上传资质证明',
        'image_id.require' => '请上传商家收款码',
        'phone.mobile' => '联系电话格式错误',
        'auth_state.require' => '请选择审核状态',
        'sync_goods_disable.in' => '商品联动策略参数错误',
        'member_is_super.require' => '请选择商家超管状态',
        'renew_months.integer' => '月数请输入整数，增加填正数，减少填负数',
        'renew_days.integer' => '天数请输入整数，增加填正数，减少填负数',
        'renew_remind_days.integer' => '提醒天数格式错误',
        'renew_remind_days.egt' => '提醒天数至少为 1 天',
    ];

    protected $scene = [
        'info' => ['id'],
        'add' => ['title', 'phone', 'username', 'password'],
        'edit' => ['id', 'title', 'phone'],
        'dele' => ['ids'],
        'disable' => ['ids', 'is_disable', 'sync_goods_disable'],
        'member_super' => ['ids', 'member_is_super'],
        'userAdd' => ['title', 'name', 'phone', 'images', 'image_id'],
        'auth' => ['ids', 'auth_state'],
        'renew' => ['ids', 'renew_months', 'renew_days', 'renew_remind_days'],
    ];

    protected function checkPhone($value, $rule, $data = [])
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;
        $where[] = [$pk, '<>', $id];
        $where[] = ['phone', '=', $data['phone']];
        $where[] = where_delete();
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '手机号已存在：' . $data['phone'];
        }
        return true;
    }

    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;
        $where[] = [$pk, '<>', $id];
        $where[] = ['title', '=', $data['title']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '商家已存在：' . $data['title'];
        }
        return true;
    }

    protected function checkAccountExisted($value, $rule, $data = [])
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;
        $where[] = [$pk, '<>', $id];
        $where[] = ['username', '=', $data['username']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '商家账号已存在：' . $data['username'];
        }
        return true;
    }
}
