<?php
namespace app\merchant\controller\finance;
use app\common\controller\BaseController;
use app\common\service\finance\MerchantAccountService;
use app\common\validate\finance\MerchantAccountValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("商家银行卡管理")
 * @Apidoc\Group("finance")
 * @Apidoc\Sort("250")
 */
class MerchantAccount extends BaseController
{
    /**
     * @Apidoc\Title("商家银行卡管理列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商家银行卡管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\MerchantAccountModel", field="id,is_disable,create_time,update_time,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort")
     * })
     */
    public function list()
    {
        $where = $this->buildWhere([
            'type',
            'source',
            'is_disable'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['merchant_id','=',mer_id()];
        $data = MerchantAccountService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
     * @Apidoc\Title("查询银行卡")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商家银行卡管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\MerchantAccountModel", field="id,is_disable,create_time,update_time,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort")
     * })
     */
    public function select()
    {
        $where = $this->where(where_delete());
        $where[] = ['merchant_id','=',mer_id()];
        $where[] = ['is_disable','=',0];
        $where[] = ['source','=',1];
        $data = MerchantAccountService::list($where,0,0, $this->order(),'id as value,card_no as label,is_disable as disable,alipay_account,type');
        return success($data);
    }
    /**
     * @Apidoc\Title("商家银行卡管理信息")
     * @Apidoc\Query(ref="app\common\model\MerchantAccountModel", field="id")
     * @Apidoc\Returned(ref="app\common\model\MerchantAccountModel")
     */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MerchantAccountValidate::class)->scene('info')->check($param);
        $data = MerchantAccountService::info($param['id']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商家银行卡管理添加")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\MerchantAccountModel", field="is_disable,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort")
     */
    public function add()
    {
        $param = $this->params(MerchantAccountService::$edit_field);
        if($param['source'] == 2){
            validate(MerchantAccountValidate::class)->scene('add'.$param['type'])->check($param);
        }else{
            validate(MerchantAccountValidate::class)->scene('add')->check($param);
        }
        $param['merchant_id'] = mer_id();
        $data = MerchantAccountService::add($param);
        return success($data);
    }
    /**
     * @Apidoc\Title("新增支付宝授权账号")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\MerchantAccountModel", field="is_disable,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort")
     */
    public function addAlipay()
    {
        $param = $this->params(['alipay_account/s' =>'','type/d'=>2]);
        validate(MerchantAccountValidate::class)->scene('addAlipay')->check($param);
        $param['merchant_id'] = mer_id();
        $param['source'] =1;
        $data = MerchantAccountService::add($param);
        return success($data);
    }

    /**
     * @Apidoc\Title("商家银行卡管理修改")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="app\common\model\MerchantAccountModel", field="id,is_disable,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort")
     */
    public function edit()
    {
        $param = $this->params(MerchantAccountService::$edit_field);
        if($param['source'] == 2){
            validate(MerchantAccountValidate::class)->scene('edit'.$param['type'])->check($param);
        }else{
            validate(MerchantAccountValidate::class)->scene('edit')->check($param);
        }
        $data = MerchantAccountService::edit($param['id'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("商家银行卡管理删除")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MerchantAccountValidate::class)->scene('dele')->check($param);
        $data = MerchantAccountService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商家银行卡管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\MerchantAccountModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MerchantAccountValidate::class)->scene('disable')->check($param);
        $data = MerchantAccountService::edit($param['ids'], $param);
        return success($data);
    }
}
