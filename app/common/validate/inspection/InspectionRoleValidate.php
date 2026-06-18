<?php


namespace app\common\validate\inspection;

use think\Validate;
use app\common\model\inspection\InspectionRoleModel;
use app\common\model\inspection\InspectionRoleMenusModel;
use app\common\model\inspection\InspectionUserAttributesModel;

/**
 * 角色管理验证器
 */
class InspectionRoleValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids'       => ['require', 'array'],
        'role_id'   => ['require'],
        'role_name' => ['require', 'checkExisted'],
        'menu_ids'  => ['array'],
        'user_ids'  => ['array'],
    ];

    // 错误信息
    protected $message = [
        'role_name.require' => '请输入名称',
    ];

    // 验证场景
    protected $scene = [
        'info'       => ['role_id'],
        'add'        => ['role_name'],
        'edit'       => ['role_id', 'role_name'],
        'editmenu'   => ['ids', 'menu_ids'],
        'dele'       => ['ids'],
        'disable'    => ['ids'],
        'user'       => ['role_id'],
        'userRemove' => ['role_id', 'user_ids'],
    ];

    // 验证场景定义：删除
    protected function sceneDele()
    {
        return $this->only(['ids'])
            ->append('ids', 'checkMenuUser');
    }

    // 自定义验证规则：角色是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new InspectionRoleModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['ins_id', '=', ins_id()];
        $where[] = ['role_name', '=', $data['role_name']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '名称已存在：' . $data['role_name'];
        }

        return true;
    }

    // 自定义验证规则：角色是否存在菜单或用户
    protected function checkMenuUser($value, $rule, $data = [])
    {
        // $menu = InspectionRoleMenusModel::where('role_id', 'in', $data['ids'])->find();
        // if ($menu) {
        //     return '角色下存在菜单，请在[菜单]或[修改]中解除后再删除：' . $menu['role_id'];
        // }

        // $user = InspectionUserAttributesModel::where('role_id', 'in', $data['ids'])->find();
        // if ($user) {
        //     return '角色下存在用户，请在[用户]中解除后再删除：' . $user['role_id'];
        // }

        return true;
    }
}
