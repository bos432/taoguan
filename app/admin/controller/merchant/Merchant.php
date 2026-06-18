<?php

namespace app\admin\controller\merchant;

use app\common\controller\BaseController;
use app\common\service\merchant\MerchantService;
use app\common\service\system\MobileAuditGrantService;
use app\common\validate\merchant\MerchantValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("商家管理")
 * @Apidoc\Group("merchant")
 * @Apidoc\Sort("250")
 */
class Merchant extends BaseController
{
    /**
     * @Apidoc\Title("商家列表")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     */
    public function list()
    {
        $param = $this->params([
            'expire_status/s' => '',
        ]);
        $where = $this->buildWhere([
            'auth_state',
        ]);
        $where = $this->where(where_delete($where));
        $data = MerchantService::list($where, $this->page(), $this->limit(), $this->order(), '', [
            'expire_status' => $param['expire_status'],
        ]);
        return success($data);
    }

    /**
     * @Apidoc\Title("商家筛选参数")
     */
    public function getParams()
    {
        $data = MerchantService::getParams(1);
        return success($data);
    }

    /**
     * @Apidoc\Title("商家详情")
     */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(MerchantValidate::class)->scene('info')->check($param);
        $data = MerchantService::info($param['id']);
        return success($data);
    }

    /**
     * @Apidoc\Title("新增商家")
     * @Apidoc\Method("POST")
     */
    public function add()
    {
        $param = $this->params(MerchantService::$edit_field);
        validate(MerchantValidate::class)->scene('add')->check($param);
        $data = MerchantService::add($param);
        return success($data);
    }

    /**
     * @Apidoc\Title("编辑商家")
     * @Apidoc\Method("POST")
     */
    public function edit()
    {
        $param = $this->params(MerchantService::$edit_field);
        validate(MerchantValidate::class)->scene('edit')->check($param);
        $data = MerchantService::edit($param['id'], $param);
        return success($data);
    }

    /**
     * @Apidoc\Title("删除商家")
     * @Apidoc\Method("POST")
     */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MerchantValidate::class)->scene('dele')->check($param);
        $data = MerchantService::dele($param['ids']);
        return success($data);
    }

    /**
     * @Apidoc\Title("禁用商家")
     * @Apidoc\Method("POST")
     */
    public function disable()
    {
        $param = $this->params([
            'ids/a' => [],
            'is_disable/d' => 0,
            'sync_goods_disable/d' => 0,
        ]);
        validate(MerchantValidate::class)->scene('disable')->check($param);
        $data = MerchantService::edit($param['ids'], $param);
        return success($data);
    }

    /**
     * @Apidoc\Title("设置商家超管")
     * @Apidoc\Method("POST")
     */
    public function memberSuper()
    {
        $param = $this->params(['ids/a' => [], 'member_is_super/d' => 0]);
        validate(MerchantValidate::class)->scene('member_super')->check($param);
        $data = MerchantService::setMemberSuper($param['ids'], intval($param['member_is_super']));
        return success($data);
    }

    /**
     * @Apidoc\Title("审核商家")
     * @Apidoc\Method("POST")
     */
    public function auth()
    {
        $param = $this->params([
            'ids/a' => [],
            'auth_state/d' => 0,
            'auth_msg/s' => '',
        ]);
        validate(MerchantValidate::class)->scene('auth')->check($param);
        $data = MerchantService::auth($param['ids'], $param);
        return success($data);
    }

    /**
     * @Apidoc\Title("商家续期")
     * @Apidoc\Method("POST")
     */
    public function renew()
    {
        $param = $this->params([
            'ids/a' => [],
            'renew_months/s' => '0',
            'renew_days/s' => '0',
            'amount/f' => 0,
            'remark/s' => '',
            'renew_remind_days/d' => MerchantService::DEFAULT_RENEW_REMIND_DAYS,
        ]);
        $param['renew_months'] = intval($param['renew_months'] ?? 0);
        $param['renew_days'] = intval($param['renew_days'] ?? 0);
        validate(MerchantValidate::class)->scene('renew')->check($param);
        $data = MerchantService::renew($param['ids'], $param);
        return success($data);
    }

    /**
     * @Apidoc\Title("续费记录列表")
     */
    public function renewRecordList()
    {
        $param = $this->params([
            'merchant_id/d' => 0,
            'ids/a' => [],
        ]);
        $where = [];
        if ($param['merchant_id'] > 0) {
            $where[] = ['r.merchant_id', '=', $param['merchant_id']];
        } elseif (!empty($param['ids'])) {
            $where[] = ['r.merchant_id', 'in', $param['ids']];
        }
        $data = MerchantService::renewRecordList($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }

    /**
     * @Apidoc\Title("开通手机审核权限")
     * @Apidoc\Method("POST")
     */
    public function grantMobileAudit()
    {
        $param = $this->params(['ids/a' => []]);
        validate(MerchantValidate::class)->scene('dele')->check($param);

        $data = MobileAuditGrantService::grant($param['ids']);
        return success($data);
    }
}
