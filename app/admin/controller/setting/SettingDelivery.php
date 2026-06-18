<?php
namespace app\admin\controller\setting;
use app\common\controller\BaseController;
use app\common\service\setting\SettingDeliveryService;
use app\common\validate\setting\SettingDeliveryValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("快递管理")
 * @Apidoc\Group("setting")
 * @Apidoc\Sort("250")
 */
class SettingDelivery extends BaseController
{
    /**
    * @Apidoc\Title("快递管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="快递管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\setting\SettingDeliveryModel", field="id,title,code,is_disable,create_time,update_time")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
        ]);
        $where = $this->where(where_delete($where));
        $data = SettingDeliveryService::list($where, $this->page(), $this->limit(), $this->order());
        return success($data);
    }
    /**
    * @Apidoc\Title("快递管理信息")
    * @Apidoc\Query(ref="app\common\model\setting\SettingDeliveryModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\setting\SettingDeliveryModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(SettingDeliveryValidate::class)->scene('info')->check($param);
        $data = SettingDeliveryService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("快递管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingDeliveryModel", field="title,code,is_disable")
    */
    public function add()
    {
        $param = $this->params(SettingDeliveryService::$edit_field);
        validate(SettingDeliveryValidate::class)->scene('add')->check($param);
        $data = SettingDeliveryService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("快递管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\setting\SettingDeliveryModel", field="id,title,code,is_disable")
    */
    public function edit()
    {
        $param = $this->params(SettingDeliveryService::$edit_field);
        validate(SettingDeliveryValidate::class)->scene('edit')->check($param);
        $data = SettingDeliveryService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("快递管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(SettingDeliveryValidate::class)->scene('dele')->check($param);
        $data = SettingDeliveryService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("快递管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\setting\SettingDeliveryModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(SettingDeliveryValidate::class)->scene('disable')->check($param);
        $data = SettingDeliveryService::edit($param['ids'], $param);
        return success($data);
    }
}
