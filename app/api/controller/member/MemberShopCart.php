<?php
namespace app\api\controller\member;
use app\common\controller\BaseController;
use app\common\service\member\MemberShopCartService;
use app\common\validate\member\MemberShopCartValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("购物车")
 * @Apidoc\Group("member")
 * @Apidoc\Sort("250")
 */
class MemberShopCart extends BaseController
{
    /**
    * @Apidoc\Title("购物车列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="购物车列表", children={
    *   @Apidoc\Returned(ref="app\common\model\member\MemberShopCartModel", field="id,is_disable,create_time,update_time,member_id,goods_id,merchant_id,cart_num,is_pay")
    * })
    */
    public function list()
    {
        $data = MemberShopCartService::getList(member_id());
        return success($data);
    }
    /**
    * @Apidoc\Title("购物车信息")
    * @Apidoc\Query(ref="app\common\model\member\MemberShopCartModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\member\MemberShopCartModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MemberShopCartValidate::class)->scene('info')->check($param);
        $data = MemberShopCartService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("购物车添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberShopCartModel", field="is_disable,member_id,goods_id,merchant_id,cart_num,is_pay")
    */
    public function add()
    {
        $param = $this->params([
            'goods_id/d'=>0
        ]);
        $param['member_id']=member_id();
        validate(MemberShopCartValidate::class)->scene('add')->check($param);
        $data = MemberShopCartService::add($param);
        return success($data,'已加入购物车');
    }
    /**
    * @Apidoc\Title("购物车修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\member\MemberShopCartModel", field="id,is_disable,member_id,goods_id,merchant_id,cart_num,is_pay")
    */
    public function edit()
    {
        $param = $this->params([
            'id/d'=>0,
            'cart_num/d'=>0,
        ]);
        $param['member_id']=member_id(true);
        validate(MemberShopCartValidate::class)->scene('edit')->check($param);
        $data = MemberShopCartService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("购物车删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        $param['member_id']=member_id(true);
        validate(MemberShopCartValidate::class)->scene('dele')->check($param);
        $data = MemberShopCartService::dele($param['ids'],true,$param['member_id']);
        return success($data,'删除成功');
    }
    /**
     * @Apidoc\Title("购物车禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\member\MemberShopCartModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MemberShopCartValidate::class)->scene('disable')->check($param);
        $data = MemberShopCartService::edit($param['ids'], $param);
        return success($data);
    }
}
