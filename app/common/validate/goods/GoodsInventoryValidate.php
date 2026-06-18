<?php
namespace app\common\validate\goods;
use think\Validate;
/**
 * 出入库明细验证器
 */
class GoodsInventoryValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'is_disable' => ['require', 'in' => '0,1'],
        'list' => ['require', 'array'],
        'type_id' => ['require', 'in' => '1,2,3'],
        'store_list' => ['require', 'array'],
    ];
    // 错误信息
    protected $message = [
        'list.require'=>'请选择已完成称重的商品',
        'type_id.require'=>'请选择查询类型',
        'store_list.require'=>'请选择需入库的商品',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  [],
        'edit'    =>  ['id', ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'saveWeighingWait'=>  ['list', ],
        'getWarehousingList'=>  ['type_id'],
        'saveWarehousingStore'=>  ['store_list', ],
        'saveOfflineWait'=>  ['list', ],
    ];
}
