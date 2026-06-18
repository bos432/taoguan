<?php
namespace app\common\validate\trace;
use app\common\model\trace\TraceBatchModel;
use think\Validate;
/**
 * 批次管理验证器
 */
class TraceBatchValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => ['require', 'checkExisted'],
        'describe' => 'require',
        'goods_id' => 'require',
        'goods_num' => 'require',
        'merchant_id'=> 'require',
        'is_disable' => ['require', 'in' => '0,1'],
        'auth_status' => ['require', 'in' => '1,2'],
        'code'=> 'require',
    ];
    // 错误信息
    protected $message = [
        'title.require' => '请输入批次号',
        'goods_id.require' => '请选择商品',
        'goods_num.require' => '请输入数量',
        'merchant_id.require' => '未获取到商家信息',
        'auth_status.require' => '请选择审核状态',
        'code.require' => '请重新扫码',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title', 'goods_id', 'goods_num','merchant_id'],
        'edit'    =>  ['id', 'title', 'goods_id', 'goods_num','merchant_id' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'auth' =>  ['ids', 'auth_status'],
        'getBatchTache'    =>  ['id'],
        'getBatchTacheByCode'=>  ['code'],
    ];
    // 自定义验证规则：名称是否已存在
    protected function checkExisted($value, $rule, $data = [])
    {
        $model = new TraceBatchModel();
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
            return '批次号已存在：' . $data['title'];
        }

        return true;
    }
}
