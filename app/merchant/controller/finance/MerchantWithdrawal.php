<?php
namespace app\merchant\controller\finance;
use app\common\controller\BaseController;
use app\common\service\finance\MerchantWithdrawalService;
use app\common\validate\finance\MerchantWithdrawalValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("商家提现")
 * @Apidoc\Group("finance")
 * @Apidoc\Sort("250")
 */
class MerchantWithdrawal extends BaseController
{
    /**
    * @Apidoc\Title("商家提现列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="商家提现列表", children={
    *   @Apidoc\Returned(ref="app\common\model\finance\MerchantWithdrawalModel", field="id,title,is_disable,create_time,update_time,amount,commission,total_amount,name,bank,bank_branch,card_no,alipay_account,auth_status,auth_msg,voucher_id,auth_uid,auth_time")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'auth_status',
            'code',
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['merchant_id','=',mer_id(true)];
        $data = MerchantWithdrawalService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("商家提现信息")
    * @Apidoc\Query(ref="app\common\model\finance\MerchantWithdrawalModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\finance\MerchantWithdrawalModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MerchantWithdrawalValidate::class)->scene('info')->check($param);
        $data = MerchantWithdrawalService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("商家提现添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\finance\MerchantWithdrawalModel", field="title,is_disable,amount,commission,total_amount,name,bank,bank_branch,card_no,alipay_account,auth_status,auth_msg,voucher_id,auth_uid,auth_time")
    */
    public function add()
    {
        $param = $this->params(['amount/f'=>null]);
        validate(MerchantWithdrawalValidate::class)->scene('add')->check($param);
        $param['merchant_id'] = mer_id();
        $data = MerchantWithdrawalService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商家提现修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\finance\MerchantWithdrawalModel", field="id,title,is_disable,amount,commission,total_amount,name,bank,bank_branch,card_no,alipay_account,auth_status,auth_msg,voucher_id,auth_uid,auth_time")
    */
    public function edit()
    {
        $param = $this->params(MerchantWithdrawalService::$edit_field);
        validate(MerchantWithdrawalValidate::class)->scene('edit')->check($param);
        $data = MerchantWithdrawalService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商家提现删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MerchantWithdrawalValidate::class)->scene('dele')->check($param);
        $data = MerchantWithdrawalService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商家提现禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\finance\MerchantWithdrawalModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(MerchantWithdrawalValidate::class)->scene('disable')->check($param);
        $data = MerchantWithdrawalService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("查询提现页面参数")
     * @Apidoc\Query(ref="app\common\model\MerchantWithdrawalModel", field="id")
     * @Apidoc\Returned(ref="app\common\model\MerchantWithdrawalModel")
     */
    public function getParams()
    {
        $data = MerchantWithdrawalService::getParams();
        return success($data);
    }
}
