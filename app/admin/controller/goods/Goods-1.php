<?php
namespace app\admin\controller\goods;
use app\common\controller\BaseController;
use app\common\service\goods\GoodsLabelService;
use app\common\service\goods\GoodsService;
use app\common\service\goods\GoodsTypeService;
use app\common\model\merchant\MerchantModel;
use app\common\service\setting\SettingHallService;
use app\common\validate\goods\GoodsValidate;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("商品管理")
 * @Apidoc\Group("goods")
 * @Apidoc\Sort("250")
 */
class Goods extends BaseController
{
    /**
    * @Apidoc\Title("商品管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="商品管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'goods_label_id',
            'goods_type_id',
            'status',
            'is_disable',
            'setting_hall_id'
        ]);
        $where = $this->where(where_delete($where));
        // 手动处理 merchant_id 参数
        $merchantId = $this->request->param('merchant_id');
        if ($merchantId !== null && $merchantId !== '' && $merchantId != -1) {
            $where[] = ['merchant_id', '=', intval($merchantId)];
        }
        $where[] = ['source','=',0];
        $data = GoodsService::list($where, $this->page(), $this->limit(), $this->order(),'id,merchant_id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg,setting_hall_id',[],1);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品管理列表参数")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="商品管理列表参数", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     * })
     */
    public function getParams()
    {
        $data['goods_types'] = GoodsTypeService::list('tree', [where_delete()], [], 'id,pid,title,is_disable as disabled');
        $data['goods_labels']      = GoodsLabelService::list([where_delete()], 0, 0, [], 'id,title');
        $data['hall_list'] = SettingHallService::list('tree', [where_delete()], [], 'id,pid,title,is_disable as disabled');
        $data['merchant_list'] = MerchantModel::where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('auth_state', 1)
            ->field('id,title,member_id')
            ->order('id', 'desc')
            ->select()
            ->toArray();
        $data['goods_status'] = GoodsService::getStatus();
        return success($data);
    }
    /**
     * @Apidoc\Title("获取商品编码")
     * @Apidoc\Query(ref="pagingQuery")
     * @Apidoc\Query(ref="sortQuery")
     * @Apidoc\Query(ref="searchQuery")
     * @Apidoc\Query(ref="dateQuery")
     * @Apidoc\Returned(ref="expsReturn")
     * @Apidoc\Returned(ref="pagingReturn")
     * @Apidoc\Returned("list", type="array", desc="获取商品编码", children={
     *   @Apidoc\Returned(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
     * })
     */
    public function getCode(){
        $param = $this->params(['goods_type_id/d' => '']);
        validate(GoodsValidate::class)->scene('getCode')->check($param);
        $data = GoodsService::getCode($param['goods_type_id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品管理信息")
    * @Apidoc\Query(ref="app\common\model\goods\GoodsModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\goods\GoodsModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(GoodsValidate::class)->scene('info')->check($param);
        $data = GoodsService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsModel", field="title,code,is_disable,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
    */
    public function add()
    {
        $param = $this->params(GoodsService::$edit_field);
        validate(GoodsValidate::class)->scene('add')->check($param);
        $param['merchant_id'] = mer_id();
        $data = GoodsService::add($param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\goods\GoodsModel", field="id,title,code,is_disable,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg")
    */
    public function edit()
    {
        $param = $this->params(GoodsService::$edit_field);
        validate(GoodsValidate::class)->scene('edit')->check($param);
        $data = GoodsService::edit([$param['id']], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("商品管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(GoodsValidate::class)->scene('dele')->check($param);
        $data = GoodsService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(GoodsValidate::class)->scene('disable')->check($param);
        $data = GoodsService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("商品审核")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\goods\GoodsModel", field="status")
     */
    public function auth(){
        $param = $this->params([
            'ids/a' => [],
            'goods_status/d' => 0,
            'auth_msg/s'=>'',
            'stock/d' => 0,
            'goods_label_id/a'=>[]
        ]);
        validate(GoodsValidate::class)->scene('auth')->check($param);
        $data = GoodsService::auth($param['ids'], $param);
        return success($data);
    }

    /**
     * @Apidoc\Title("商品批量迁移到平台自营")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function transferToPlatform()
    {
        $param = $this->params(['ids/a' => []]);
        validate(GoodsValidate::class)->scene('transferToPlatform')->check($param);
        $data = GoodsService::transferToPlatform($param['ids']);
        return success($data);
    }

    /**
     * @Apidoc\Title("商品批量迁移到指定商家")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param("target_merchant_id", type="int", require=true, desc="目标商家ID")
     */
    public function transferToMerchant()
    {
        $param = $this->params([
            'ids/a' => [],
            'target_merchant_id/d' => 0,
        ]);
        validate(GoodsValidate::class)->scene('transferToMerchant')->check($param);
        $data = GoodsService::transferToMerchant($param['ids'], intval($param['target_merchant_id']));
        return success($data);
    }

    /**
     * @Apidoc\Title("商品批量更换缩略图")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param("image_id", type="int", require=true, desc="新缩略图文件ID")
     */
    public function batchUpdateThumbnail()
    {
        $param = $this->params([
            'ids/a' => [],
            'image_id/d' => 0,
        ]);
        validate(GoodsValidate::class)->scene('batchUpdateThumbnail')->check($param);
        $data = GoodsService::batchUpdateThumbnail($param['ids'], intval($param['image_id']));
        return success($data);
    }
}
