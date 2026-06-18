<?php
namespace app\common\validate\goods;
use app\common\model\goods\GoodsLabelModel;
use think\Validate;
/**
 * 商品标签验证器
 */
class GoodsLabelValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => ['require', 'checkExisted'],
        'remark' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'title.require'   => '请输入名称',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title' ],
        'edit'    =>  ['id', 'title' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    // 自定义验证规则：协议是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new GoodsLabelModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['title', '=', $data['title']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '标签已存在：' . $data['title'];
        }

        return true;
    }
}
