<?php
namespace app\common\validate\trace;
use app\common\model\trace\TraceBatchTacheModel;
use think\Validate;
/**
 * 批次环节录入验证器
 */
class TraceBatchTacheValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'merchant_id' => 'require',
        'trace_batch_id' => 'require',
        'tacheValue' => ['require', 'array'],
        'trace_tache_id' => ['require', 'checkExisted'],
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'merchant_id.require' => '未获取到商家信息',
        'trace_batch_id.require' => '请选择批次',
        'trace_tache_id.require' => '请选择环节',
        'tacheValue.require' => '请完善该环节下的字段',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['merchant_id','trace_batch_id','trace_tache_id','tacheValue'],
        'edit'    =>  ['id', 'merchant_id','trace_batch_id','trace_tache_id','tacheValue'],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
    ];
    // 自定义验证规则：名称是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new TraceBatchTacheModel();
        $pk = $model->getPk();
        $id = $data[$pk] ?? 0;

        $where[] = [$pk, '<>', $id];
        $where[] = ['trace_batch_id', '=', $data['trace_batch_id']];
        $where[] = ['trace_tache_id', '=', $data['trace_tache_id']];
        $where = where_delete($where);
        $mer_id = mer_id();
        $info = $model->when($mer_id>0,function($query)use($mer_id){
                    $query->where('merchant_id',$mer_id);
                })->field($pk)->where($where)->find();
        if ($info) {
            return '该批次与该环节已存在';
        }

        return true;
    }
}
