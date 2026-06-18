<?php

namespace app\common\validate\inspection;
use app\common\model\inspection\InspectionModel;
use think\Validate;
/**
 * 检测机构管理验证器
 */
class InspectionValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => ['require', 'checkExisted'],
        'password' => 'require',
        'username' => ['require', 'checkAccountExisted'],
        'phone'    => ['mobile', 'checkPhone'],
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'title.require' => '请输入检测机构名称',
        'username.require' => '请输入登录密码',
        'password.require' => '请输入检测机构账号',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title','phone', 'username','password' ],
        'edit'    =>  ['id', 'title','phone', 'content','password' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    // 自定义验证规则：手机是否已存在
    protected function checkPhone($value, $rule, $data = [])
    {
        $model = new InspectionModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['phone', '=', $data['phone']];
        $where[] = where_delete();
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '手机已存在：' . $data['phone'];
        }
        return true;
    }
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new InspectionModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['title', '=', $data['title']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '检测机构已存在：' . $data['title'];
        }

        return true;
    }
    protected function checkAccountExisted($value, $rule, $data = [])
    {
        $model = new InspectionModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['username', '=', $data['username']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '检测机构账号已存在：' . $data['username'];
        }

        return true;
    }
}
