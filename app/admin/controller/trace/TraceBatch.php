<?php
namespace app\admin\controller\trace;
use app\common\controller\BaseController;
use app\common\service\goods\GoodsService;
use app\common\service\setting\SettingCallService;
use app\common\service\setting\SettingWarehouseService;
use app\common\service\trace\TraceBatchService;
use app\common\service\trace\TraceBatchTacheService;
use app\common\validate\trace\TraceBatchValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("批次管理")
 * @Apidoc\Group("trace")
 * @Apidoc\Sort("250")
 */
class TraceBatch extends BaseController
{
    /**
    * @Apidoc\Title("批次管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="批次管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\TraceBatchModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark,merchant_id,goods_id,goods_num")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'is_disable',
            'is_weighing_warehousing',
            'is_weighing',
            'is_warehousing',
            'setting_call_id',
            'setting_warehouse_id',
            'setting_hall_id',
            'auth_status',
            'goods_id'
        ]);
        $where = $this->where(where_delete($where));
        $data = TraceBatchService::list($where, $this->page(), $this->limit(), $this->order(),'',0,1);
        return success($data);
    }
    /**
     * @Apidoc\Title("选择批次")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="批次管理列表", children={
     *   @Apidoc\Returned(ref="app\common\model\TraceBatchModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark,merchant_id,goods_id,goods_num")
     * })
     */
    public function select()
    {
        $where = $this->where(where_delete());
        $where[] = ['auth_status','=',1];
        $data = TraceBatchService::list($where,0,0, $this->order(),'id as value,title as label,is_disable as disable');
        return success($data);
    }
    /**
     * @Apidoc\Title("查询参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="查询参数", children={
     *   @Apidoc\Returned(ref="app\common\model\TraceBatchModel", field="id,title,describe,is_disable,create_time,update_time,sort,remark,merchant_id,goods_id,goods_num")
     * })
     */
    public function getParams()
    {
        $call_list = SettingCallService::list('list', [where_delete()], [], 'id,pid,title,is_disable as disabled,setting_hall_id,address');
        $warehouse_list = SettingWarehouseService::list('list', [where_delete()], [], 'id,pid,title,is_disable as disabled,setting_hall_id,code,address');
        $where = $this->where(where_delete());
        $where[] = ['status','=',1];
        $data['goods_list']  = GoodsService::list($where,0,0, $this->order(),'id as value,title as label,is_disable as disabled,setting_hall_id');
        foreach ($data['goods_list'] as $k=>$v) {
            //仓库
            $data['goods_list'][$k]['warehouse_list'] = [];
            foreach ($warehouse_list as $k1=>$v1) {
                if($v['setting_hall_id'] == $v1['setting_hall_id'] || $v1['code']=='ZC') {
                    array_push($data['goods_list'][$k]['warehouse_list'],$v1);
                }
            }
            $data['goods_list'][$k]['warehouse_list'] =array_to_tree($data['goods_list'][$k]['warehouse_list'], 'id', 'pid');
            //电子秤
            $data['goods_list'][$k]['call_list'] = [];
            foreach ($call_list as $k2=>$v2) {
                if($v['setting_hall_id'] == $v2['setting_hall_id']) {
                    array_push($data['goods_list'][$k]['call_list'],$v2);
                }
            }
            $data['goods_list'][$k]['call_list'] =array_to_tree($data['goods_list'][$k]['call_list'], 'id', 'pid');
        }
        $data['call_list'] = array_to_tree($call_list, 'id', 'pid');
        $data['warehouse_list'] = array_to_tree($warehouse_list, 'id', 'pid');
        $data['auth_status'] =TraceBatchService::getAuthStatus();
        return success($data);
    }
    /**
    * @Apidoc\Title("批次管理信息")
    * @Apidoc\Query(ref="app\common\model\TraceBatchModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\TraceBatchModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(TraceBatchValidate::class)->scene('info')->check($param);
        $data = TraceBatchService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="title,describe,is_disable,sort,remark,merchant_id,goods_id,goods_num")
    */
    public function add()
    {
        $param = $this->params(TraceBatchService::$edit_field);
        $param['merchant_id'] = mer_id();
        validate(TraceBatchValidate::class)->scene('add')->check($param);
        $data = TraceBatchService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="id,title,describe,is_disable,sort,remark,merchant_id,goods_id,goods_num")
    */
    public function edit()
    {
        $param = $this->params(TraceBatchService::$edit_field);
        validate(TraceBatchValidate::class)->scene('edit')->check($param);
        $data = TraceBatchService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("批次管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(TraceBatchValidate::class)->scene('dele')->check($param);
        $data = TraceBatchService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("批次管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(TraceBatchValidate::class)->scene('disable')->check($param);
        $data = TraceBatchService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("批次审核")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="is_disable")
     */
    public function auth(){
        $param = $this->params([
            'ids/a' => [],
            'auth_status/d' => 0,
            'auth_msg/s'=>'',
        ]);
        validate(TraceBatchValidate::class)->scene('auth')->check($param);
        $data = TraceBatchService::auth($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("查询溯源信息")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\TraceBatchModel", field="is_disable")
     */
    public function getBatchTache()
    {
        $param = $this->params(['id/d' => '']);
        validate(TraceBatchValidate::class)->scene('getBatchTache')->check($param);
        $data = TraceBatchTacheService::getBatchTache($param['id']);
        return success($data);
    }
}
