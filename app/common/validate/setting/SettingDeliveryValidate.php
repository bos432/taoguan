<?php
namespace app\common\validate\setting;
use app\common\model\setting\SettingDeliveryModel;
use think\Validate;
/**
 * 快递管理验证器
 */
class SettingDeliveryValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => 'require',
        'code' => ['require', 'checkCodeExisted'],
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'code.require' => '请输入编码',
        'title.require' => '请输入名称',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title', 'code', ],
        'edit'    =>  ['id', 'title', 'code', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    protected function checkCodeExisted($value, $rule, $data = [])
    {
        $model = new SettingDeliveryModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['code', '=', $data['code']];
        $where = where_delete($where);
        $info = $model->field($pk)->where($where)->find();
        if ($info) {
            return '编码已存在：' . $data['code'];
        }
        return true;
    }
}
