<?php
namespace app\common\validate\trace;
use think\Validate;
/**
 * 二维码管理验证器
 */
class TraceQrCodeValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'merchant_id' => 'require',
        'goods_id' => 'require',
        'trace_batch_id' => 'require',
        'apple_num'=> 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'trace_batch_id.require' => '请选择批次号',
        'apple_num.require' => '请输入生成二维码数量',
        'goods_id.require' => '未获取到商品信息',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['merchant_id', 'goods_id','trace_batch_id','apple_num' ],
        'edit'    =>  ['id', 'merchant_id', 'goods_id','trace_batch_id' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'download'    =>  ['ids'],
    ];
}
