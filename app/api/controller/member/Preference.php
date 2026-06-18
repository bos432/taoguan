<?php

namespace app\api\controller\member;

use app\common\controller\BaseController;
use app\common\service\member\MemberBootstrapService;
use app\common\service\member\MemberPreferenceService;
use app\common\validate\member\MemberPreferenceValidate;

class Preference extends BaseController
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

        validate(MemberPreferenceValidate::class)->scene('list')->check($param);

        $memberId = member_id(true);
        return MemberBootstrapService::bootstrap($memberId, $param['preference_group'], $param['preference_keys']);
    }

    public function list()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(MemberPreferenceValidate::class)->scene('list')->check($param);

        $data = MemberPreferenceService::list(member_id(true), $param['preference_group'], $param['preference_keys']);
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

        validate(MemberPreferenceValidate::class)->scene('save')->check($param);

        $data = MemberPreferenceService::save(
            member_id(true),
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

        validate(MemberPreferenceValidate::class)->scene('batchSave')->check($param);

        $data = MemberPreferenceService::batchSave(member_id(true), $param['preference_group'], $param['items']);
        return success($data);
    }

    public function dele()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(MemberPreferenceValidate::class)->scene('dele')->check($param);

        $data = MemberPreferenceService::dele(member_id(true), $param['preference_group'], $param['preference_keys']);
        return success($data);
    }

    public function clear()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);

        validate(MemberPreferenceValidate::class)->scene('clear')->check($param);

        $data = MemberPreferenceService::clear(member_id(true), $param['preference_group']);
        return success($data);
    }
}
