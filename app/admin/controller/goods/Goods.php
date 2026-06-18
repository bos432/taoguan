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
        if (empty($param['ids']) || !is_array($param['ids'])) {
            exception('请选择商品');
        }
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
        if (empty($param['ids']) || !is_array($param['ids'])) {
            exception('请选择商品');
        }
        if (intval($param['target_merchant_id']) <= 0) {
            exception('请选择目标商家');
        }
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
        if (empty($param['ids']) || !is_array($param['ids'])) {
            exception('请选择商品');
        }
        if (intval($param['image_id']) <= 0) {
            exception('请选择缩略图');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $param['ids']))));
        $imageId = intval($param['image_id']);

        if (method_exists(GoodsService::class, 'batchUpdateThumbnail')) {
            $data = GoodsService::batchUpdateThumbnail($ids, $imageId);
        } else {
            $model = new \app\common\model\goods\GoodsModel();
            $affected = $model->whereIn('id', $ids)->where('is_delete', 0)->update([
                'image_id' => $imageId,
                'update_uid' => user_id(),
                'update_time' => datetime(),
            ]);
            if ($affected === false) {
                exception('批量更换缩略图失败');
            }
            $data = [
                'ids' => $ids,
                'image_id' => $imageId,
                'count' => intval($affected),
            ];
        }
        return success($data);
    }

    /**
     * @Apidoc\Title("商品批量更新标签")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param("goods_label_id", type="array", require=false, desc="商品标签ID数组")
     */
    public function batchUpdateLabels()
    {
        $param = $this->params([
            'ids/a' => [],
            'goods_label_id/a' => [],
        ]);
        if (empty($param['ids']) || !is_array($param['ids'])) {
            exception('请选择商品');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $param['ids']))));
        $labelIds = array_values(array_unique(array_filter(array_map('intval', $param['goods_label_id'] ?? []))));

        $data = GoodsService::edit($ids, [
            'goods_label_id' => $labelIds,
        ]);
        return success($data);
    }
}
