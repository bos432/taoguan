<?php

namespace app\admin\controller\system;

use app\common\controller\BaseController;
use app\common\service\system\UserBootstrapService;
use app\common\service\system\UserPreferenceService;
use app\common\validate\system\UserPreferenceValidate;
use hg\apidoc\annotation as Apidoc;

/**
 * @Apidoc\Title("User preference")
 * @Apidoc\Group("system")
 * @Apidoc\Sort("850")
 */
class UserPreference extends BaseController
{
    /**
     * @Apidoc\Title("Preference bootstrap")
     */
    public function bootstrap()
    {
        return success($this->runtimeData());
    }

    /**
     * @Apidoc\Title("Preference runtime")
     */
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

        validate(UserPreferenceValidate::class)->scene('list')->check($param);

        return UserBootstrapService::bootstrap(user_id(true), $param['preference_group'], $param['preference_keys']);
    }

    /**
     * @Apidoc\Title("Preference list")
     */
    public function list()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(UserPreferenceValidate::class)->scene('list')->check($param);

        $data = UserPreferenceService::list(user_id(true), $param['preference_group'], $param['preference_keys']);
        return success($data);
    }

    /**
     * @Apidoc\Title("Preference save")
     * @Apidoc\Method("POST")
     */
    public function save()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
            'preference_key/s' => '',
            'value_type/s' => 'json',
            'remark/s' => '',
        ]);
        $param['preference_value'] = $this->request->param('preference_value');

        validate(UserPreferenceValidate::class)->scene('save')->check($param);

        $data = UserPreferenceService::save(
            user_id(true),
            $param['preference_group'],
            $param['preference_key'],
            $param['preference_value'],
            $param['value_type'],
            $param['remark']
        );

        return success($data);
    }

    /**
     * @Apidoc\Title("Preference batch save")
     * @Apidoc\Method("POST")
     */
    public function batchSave()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['items'] = $this->request->param('items/a', []);

        validate(UserPreferenceValidate::class)->scene('batchSave')->check($param);

        $data = UserPreferenceService::batchSave(user_id(true), $param['preference_group'], $param['items']);
        return success($data);
    }

    /**
     * @Apidoc\Title("Preference delete")
     * @Apidoc\Method("POST")
     */
    public function dele()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);
        $param['preference_keys'] = $this->request->param('preference_keys/a', []);

        validate(UserPreferenceValidate::class)->scene('dele')->check($param);

        $data = UserPreferenceService::dele(user_id(true), $param['preference_group'], $param['preference_keys']);
        return success($data);
    }

    /**
     * @Apidoc\Title("Preference clear")
     * @Apidoc\Method("POST")
     */
    public function clear()
    {
        $param = $this->params([
            'preference_group/s' => 'ui',
        ]);

        validate(UserPreferenceValidate::class)->scene('clear')->check($param);

        $data = UserPreferenceService::clear(user_id(true), $param['preference_group']);
        return success($data);
    }
}
