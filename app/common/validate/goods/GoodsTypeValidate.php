<?php

namespace app\common\validate\goods;
use app\common\model\goods\GoodsTypeModel;
use think\Validate;
/**
 * 商品分类验证器
 */
class GoodsTypeValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'pid'  => ['checkPid'],
        'code' => ['require', 'checkCodeExisted'],
        'title' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'code.require' => '请输入分类编码',
        'title.require' => '请输入分类名称',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['code', 'title', ],
        'edit'    =>  ['id', 'code', 'title', ],
        'editpid'       => ['ids', 'pid'],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    // 验证场景定义：分类删除
    protected function sceneDele()
    {
        return $this->only(['ids'])
            ->append('ids', ['checkChild', 'checkContent']);
    }
    // 自定义验证规则：分类是否存在下级分类
    protected function checkChild($value, $rule, $data = [])
    {
        $where = where_delete(['pid', 'in', $data['ids']]);
        $info = GoodsTypeModel::field('pid')->where($where)->find();
        if ($info) {
            return '分类存在下级分类，无法删除：' . $info['pid'];
        }

        return true;
    }

    // 自定义验证规则：分类下是否存在内容
    protected function checkContent($value, $rule, $data = [])
    {
        // $info = AttributesModel::where('pid', 'in', $data['ids'])->find();
        // if ($info) {
        //     return '分类下存在内容，请在[内容]中解除后再删除：' . $info['pid'];
        // }

        return true;
    }
    // 自定义验证规则：分类是否已存在
    protected function checkCodeExisted($value, $rule, $data = [])
    {
        $model = new GoodsTypeModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;
        $pid = $data['pid'] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['code', '=', $data['code']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '分类编码已存在：' . $data['code'];
        }
        return true;
    }
    // 自定义验证规则：分类上级
    protected function checkPid($value, $rule, $data = [])
    {
        $ids = $data['ids'] ?? [];
        if ($data['id'] ?? 0) {
            $ids[] = $data['id'];
        }

        foreach ($ids as $id) {
            if ($data['pid'] == $id) {
                return '分类上级不能等于分类本身';
            }
        }

        return true;
    }
}
