<?php


namespace app\common\validate\merchant;

use think\Validate;
use app\common\model\merchant\MerchantMenuModel;
use app\common\model\merchant\MerchantRoleMenusModel;

/**
 * 菜单管理验证器
 */
class MerchantMenuValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids'       => ['require', 'array'],
        'menu_id'   => ['require'],
        'menu_pid'  => ['checkPid'],
        'menu_name' => ['require', 'checkExisted'],
        'role_ids'  => ['array'],
    ];

    // 错误信息
    protected $message = [
        'menu_name.require' => '请输入菜单名称',
    ];

    // 验证场景
    protected $scene = [
        'info'       => ['menu_id'],
        'add'        => ['menu_name'],
        'edit'       => ['menu_id', 'menu_pid', 'menu_name'],
        'dele'       => ['ids'],
        'editsort'   => ['ids'],
        'editpid'    => ['ids', 'menu_pid'],
        'unlogin'    => ['ids'],
        'unauth'     => ['ids'],
        'unrate'     => ['ids'],
        'hidden'     => ['ids'],
        'disable'    => ['ids'],
        'role'       => ['menu_id'],
        'roleRemove' => ['menu_id', 'role_ids'],
    ];

    // 验证场景定义：菜单删除
    protected function sceneDele()
    {
        return $this->only(['ids'])
            ->append('ids', ['checkChild', 'checkRole']);
    }

    // 自定义验证规则：菜单上级
    protected function checkPid($value, $rule, $data = [])
    {
        $ids = $data['ids'] ?? [];
        if ($data['menu_id'] ?? 0) {
            $ids[] = $data['menu_id'];
        }

        foreach ($ids as $id) {
            if ($data['menu_pid'] == $id) {
                return '菜单上级不能等于菜单本身';
            }
        }

        return true;
    }

    // 自定义验证规则：菜单是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new MerchantMenuModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;
        $pid = $data['menu_pid'] ?? 0;

        $where_name[] = [$pk, '<>', $id];
        $where_name[] = ['menu_pid', '=', $pid];
        $where_name[] = ['menu_name', '=', $data['menu_name']];
        $where_name = where_delete($where_name);
        $info = $model->field($pk)->where($where_name)->find();
        if ($info) {
            return '菜单名称已存在：' . $data['menu_name'];
        }

        $url = $data['menu_url'];
        if ($url) {
            $where_url[] = [$pk, '<>', $id];
            $where_url[] = ['menu_url', '=', $url];
            $where_url = where_delete($where_url);
            $info = $model->field($pk)->where($where_url)->find();
            if ($info) {
                return '菜单链接已存在：' . $data['menu_url'];
            }
        }

        return true;
    }

    // 自定义验证规则：菜单是否存在下级菜单
    protected function checkChild($value, $rule, $data = [])
    {
        $where = where_delete(['menu_pid', 'in', $data['ids']]);
        $info = MerchantMenuModel::field('menu_pid')->where($where)->find();
        if ($info) {
            return '菜单存在下级菜单，无法删除：' . $info['menu_pid'];
        }

        return true;
    }

    // 自定义验证规则：菜单是否存在角色
    protected function checkRole($value, $rule, $data = [])
    {
        // $info = MerchantRoleMenusModel::where('menu_id', 'in', $data['ids'])->find();
        // if ($info) {
        //     return '菜单存在角色，请在[角色]中解除后再删除：' . $info['menu_id'];
        // }

        return true;
    }
}
