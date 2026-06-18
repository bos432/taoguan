<?php
namespace app\common\validate\trace;
use app\common\model\trace\TraceTacheModel;
use think\Validate;
/**
 * 环节模板验证器
 */
class TraceTacheValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => ['require', 'checkExisted'],
        'describe' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'title.require' => '请输入环节模板名称',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title' ],
        'edit'    =>  ['id', 'title' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    // 自定义验证规则：名称是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new TraceTacheModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['title', '=', $data['title']];
        $where = where_delete($where);
        $mer_id = mer_id();
        $info = $model->when($mer_id>0,function($query)use($mer_id){
                    $query->where('merchant_id',$mer_id);
                })->field($pk)->where($where)->find();
        if ($info) {
            return '环节模板名称已存在：' . $data['title'];
        }

        return true;
    }
}
