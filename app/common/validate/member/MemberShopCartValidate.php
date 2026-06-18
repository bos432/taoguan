<?php
namespace app\common\validate\member;
use think\Validate;
/**
 * 购物车验证器
 */
class MemberShopCartValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'goods_id' => 'require',
        'member_id' => 'require',
        'cart_num' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
    ];
    // 错误信息
    protected $message = [
        'goods_id.require'=>'请选择需要添加购物车的商品',
        'member_id.require'=>'请登录',
        'cart_num.require'=>'请输入数量',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['goods_id','member_id'],
        'edit'    =>  ['id','member_id','cart_num'],
        'dele'    =>  ['ids','member_id'],
        'disable' =>  ['ids', 'is_disable'],
    ];
}
