<?php

namespace app\admin\controller\merchant;

use app\common\controller\BaseController;
use app\common\domain\operation\BusinessOperationContextFactory;
use app\common\service\merchant\MerchantMemberBindingService;
use app\common\service\merchant\MerchantAuthorizationLogQueryService;
use app\common\service\merchant\MerchantService;
use app\common\service\merchant\MerchantSuperAuthorizationService;
use app\common\service\permission\UnifiedPermissionContextService;
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
        $where = $this->filterListWhere($this->where(where_delete($where)));
        $data = MerchantService::list($where, $this->page(), $this->limit(), $this->order(), '', [
            'expire_status' => $param['expire_status'],
        ]);
        return success($data);
    }

    private function filterListWhere(array $where): array
    {
        $allowedFields = [
            'id',
            'title',
            'username',
            'phone',
            'name',
            'remark',
            'auth_state',
            'create_time',
            'expire_time',
            'is_disable',
            'is_delete',
            'member_id',
        ];

        return array_values(array_filter($where, function ($item) use ($allowedFields) {
            if (!is_array($item) || empty($item[0])) {
                return false;
            }
            return in_array((string) $item[0], $allowedFields, true);
        }));
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
        UnifiedPermissionContextService::assertAllowed(
            UnifiedPermissionContextService::forPlatformUser(intval(user_id(true))),
            'platform.merchant.super_authorize'
        );
        $param = $this->params(['ids/a' => [], 'member_is_super/d' => 0, 'reason/s' => '']);
        validate(MerchantValidate::class)->scene('member_super')->check($param);
        $data = MerchantSuperAuthorizationService::execute(
            $param['ids'],
            intval($param['member_is_super']),
            BusinessOperationContextFactory::fromRequest(
                'admin-next', 'platform_admin', intval(user_id(true)), strval($param['reason'] ?? '')
            )
        );
        return success($data);
    }

    /**
     * @Apidoc\Title("绑定商家会员")
     * @Apidoc\Method("POST")
     */
    public function memberBind()
    {
        UnifiedPermissionContextService::assertAllowed(
            UnifiedPermissionContextService::forPlatformUser(intval(user_id(true))),
            'platform.merchant.bind'
        );
        $param = $this->params(['id/d' => 0, 'member_id/d' => 0, 'reason/s' => '']);
        validate(MerchantValidate::class)->scene('member_bind')->check($param);
        $data = MerchantMemberBindingService::execute(
            intval($param['id']),
            intval($param['member_id']),
            BusinessOperationContextFactory::fromRequest(
                'admin-next', 'platform_admin', intval(user_id(true)), strval($param['reason'] ?? '')
            )
        );
        return success($data);
    }

    /**
     * @Apidoc\Title("商家授权审计记录")
     */
    public function authorizationLogs()
    {
        UnifiedPermissionContextService::assertAllowed(
            UnifiedPermissionContextService::forPlatformUser(intval(user_id(true))),
            'platform.merchant.view'
        );
        $param = $this->params(['merchant_id/d' => 0, 'authorization_type/s' => '']);
        if (intval($param['merchant_id']) <= 0) {
            exception('请选择商家');
        }
        return success(MerchantAuthorizationLogQueryService::list(
            intval($param['merchant_id']),
            strval($param['authorization_type']),
            $this->page(),
            $this->limit()
        ));
    }

    /**
     * @Apidoc\Title("审核商家")
     * @Apidoc\Method("POST")
     */
    public function auth()
    {
        UnifiedPermissionContextService::assertAllowed(
            UnifiedPermissionContextService::forPlatformUser(intval(user_id(true))),
            'platform.merchant.review'
        );
        $param = $this->params([
            'ids/a' => [],
            'auth_state/d' => 0,
            'auth_msg/s' => '',
            'reason/s' => '',
        ]);
        validate(MerchantValidate::class)->scene('auth')->check($param);
        $data = MerchantService::auth($param['ids'], $param, BusinessOperationContextFactory::fromRequest(
            'admin-next',
            'platform_admin',
            intval(user_id(true)),
            strval($param['reason'] ?: $param['auth_msg'])
        ));
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
