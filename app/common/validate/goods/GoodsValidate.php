<?php
namespace app\common\validate\goods;
use app\common\model\goods\GoodsModel;
use think\Validate;
/**
 * 商品管理验证器
 */
class GoodsValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'ids' => ['require', 'array'],
        'id' => 'require',
        'title' => 'require',
        'goods_type_id'=> 'require',
        'target_merchant_id' => ['require', 'integer', 'gt' => 0],
        'code' => ['require', 'checkCodeExisted'],
        'is_disable' => ['require', 'in' => '0,1'],
        'goods_status' => ['require', 'in' => '1,2'],
        'original_price'=> 'require',
        'price'=> 'require',
        'images' => ['require', 'array'],
    ];
    // 错误信息
    protected $message = [
        'code.require' => '请输入编码',
        'goods_type_id.require' => '请选择商品分类',
        'target_merchant_id.require' => '请选择目标商家',
        'target_merchant_id.integer' => '目标商家参数错误',
        'target_merchant_id.gt' => '目标商家参数错误',
        'goods_status.require' => '请选择审核状态',
        'original_price.require' => '请输入商品原价',
        'price.require' => '请输入商品价格',
        'images.require' => '请上传商品图片',
    ];
    // 验证场景
    protected $scene = [
        'info'    =>  ['id'],
        'add'     =>  ['title', 'code','goods_type_id' ],
        'edit'    =>  ['id', 'title', 'code','goods_type_id' ],
        'dele'    =>  ['ids'],
        'disable' =>  ['ids', 'is_disable'],
        'getCode'=>['goods_type_id'],
        'auth' =>  ['ids', 'goods_status'],
        'transferToPlatform' => ['ids'],
        'transferToMerchant' => ['ids', 'target_merchant_id'],
        'saveRelease'     =>  ['title', 'goods_type_id','original_price','price','images' ],
        'memberDele'=>['id'],
        'transaction'=>['id'],
    ];
    // 自定义验证规则：分类是否已存在
    protected function checkCodeExisted($value, $rule, $data = [])
    {
        $model = new GoodsModel();
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
