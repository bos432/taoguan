<?php

namespace app\merchant\controller\system;

use app\common\controller\BaseController;
use app\common\service\merchant\MerchantBootstrapService;
use app\common\service\merchant\MerchantUserPreferenceService;
use app\common\validate\merchant\MerchantUserPreferenceValidate;

class UserPreference extends BaseController
{
    public function bootstrap()
    {
        return success($this->runtimeData());
    }

    public function runtime()
    {
        return success($this->runtimeData());
    }

    private function runtimeData(): array
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(MerchantUserPreferenceValidate::class)->scene('list')->check($param);

        return MerchantBootstrapService::bootstrap(mer_user_id(true), $param['preference_group'], $param['preference_keys']);
    }

    public function list()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(MerchantUserPreferenceValidate::class)->scene('list')->check($param);

        $data = MerchantUserPreferenceService::list(mer_user_id(true), $param['preference_group'], $param['preference_keys']);
        return success($data);
    }

    public function save()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
            'preference_key/s' => '',
            'value_type/s' => 'json',
            'remark/s' => '',
        ]);
        $param['preference_value'] = $this->request->param('preference_value');

        validate(MerchantUserPreferenceValidate::class)->scene('save')->check($param);

        $data = MerchantUserPreferenceService::save(
            mer_user_id(true),
            mer_id(true),
            $param['preference_group'],
            $param['preference_key'],
            $param['preference_value'],
            $param['value_type'],
            $param['remark']
        );

        return success($data);
    }

    public function batchSave()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['items'] = $this->request->param('items/a', []);

        validate(MerchantUserPreferenceValidate::class)->scene('batchSave')->check($param);

        $data = MerchantUserPreferenceService::batchSave(mer_user_id(true), mer_id(true), $param['preference_group'], $param['items']);
        return success($data);
    }

    public function dele()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(MerchantUserPreferenceValidate::class)->scene('dele')->check($param);

        $data = MerchantUserPreferenceService::dele(mer_user_id(true), $param['preference_group'], $param['preference_keys']);
        return success($data);
    }

    public function clear()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);

        validate(MerchantUserPreferenceValidate::class)->scene('clear')->check($param);

        $data = MerchantUserPreferenceService::clear(mer_user_id(true), $param['preference_group']);
        return success($data);
    }
}
