<?php
namespace app\common\service\trace;
use app\common\cache\goods\GoodsCache;
use app\common\model\goods\GoodsInventoryModel;
use app\common\model\goods\GoodsModel;
use app\common\model\trace\TraceBatchModel;
use app\common\cache\trace\TraceBatchCache;
use think\facade\Db;

/**
 * 批次管理
 */
class TraceBatchService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
        'id'=>'',//主键ID
        'title'=>'',//批次号
        'describe'=>'',//描述
        'is_disable/d'=>0,//是否禁用，1是0否
        'sort'=>'',//排序
        'remark'=>'',//备注
        'merchant_id'=>'',//商家id
        'goods_id'=>'',//商品id
        'goods_num'=>'',//商品数量
        'is_weighing_warehousing/d'=>0,//是否称重入库
        'is_weighing/d'=>0,//是否已称重：0、待称重 1、已称重
        'is_warehousing/d'=>0,//是否已入库：0、待入库 1、已入库
        'setting_call_id'=>'',//电子秤id
        'setting_warehouse_id'=>'',//仓库id
        'setting_hall_id'=>'',//大厅id
        'auth_status/d'=>0,//审核状态：0、待审核 1、已审核 2、审核失败
        'auth_msg'=>'',//审核原因
        'auth_time/s'=>null,
        'auth_uid/d'=>null,
    ];
    /**
     * 批次管理列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * @param string $mer_id 商家id
     * @param string $source 来源：1、总后端、2、商家端 3、移动端
     *
     * @return array
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '',$mer_id=0,$source=1)
    {
        $model = new TraceBatchModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,describe,is_disable,is_delete,create_uid,update_uid,delete_uid,weighing_uid,warehousing_uid,create_time,update_time,delete_time,weighing_time,warehousing_time,sort,remark,merchant_id,goods_id,goods_num,is_weighing_warehousing,is_weighing,is_warehousing,setting_call_id,setting_warehouse_id,setting_hall_id,auth_status,auth_msg,auth_uid,auth_time,weighing_num,warehousing_num';
        }
        if (empty($order)) {
            $order = ['merchant_id'=>'asc','auth_status'=>'asc','sort'=>'asc',$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];

        if (strpos($field, 'goods_id') !== false) {
            $with[]   = $hidden[]   = 'goods';
            $append[] = 'goods_title';
        }
        if (strpos($field, 'setting_call_id') !== false) {
            $with[]   = $hidden[]   = 'call';
            $append[] = 'call_title';
        }
        if (strpos($field, 'setting_warehouse_id') !== false) {
            $with[]   = $hidden[]   = 'warehouse';
            $append[] = 'warehouse_title';
        }
        if (strpos($field, 'merchant_id') !== false) {
            $with[]   = $hidden[]   = 'merchant';
            $append[] = 'merchant_title';
        }
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();

        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        //最大批次号
        $max_no="";
        $statistics=[];
        if($source==1){
            $statistics =self::getAuthStatusNum($source);
        }
        if($source==2){
            $max_no = TraceBatchModel::createNo([['is_delete','=',0]],$mer_id);
        }

        return compact('count', 'pages', 'page', 'limit', 'list','max_no','statistics');
    }
    /**
     * 查询审核状态数量
     * @Author: 易军辉
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getAuthStatusNum($source=1){
        $merchantId = mer_id();
        $where = " where is_delete=0";
        if($merchantId>0){
            $where .=" and merchant_id=".$merchantId;
        }
        //根据状态查询数量
        $status_num = Db::query("SELECT auth_status,count(id) as num from ya_trace_batch ".$where." GROUP BY auth_status");
        $status_nums = array();

        $status_nums['all'] = Db::name('trace_batch')
            ->where('is_delete',0)
            ->when($merchantId>0, function($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId);
            })->count();
        foreach (TraceBatchModel::AUTH_STATUS as $k => $v) {
            $status_nums[$v['code']] =0;
            foreach ($status_num as $k1 => $v1) {
                if($v1['auth_status'] == $v['value']) {
                    $status_nums[$v['code']] = $v1['num'];
                    break;
                }
            }
        }
        return $status_nums;
    }
    /**
     * 批次管理信息
     *
     * @param int  $id   批次管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = TraceBatchCache::get($id);
        if (empty($info)) {
            $model = new TraceBatchModel();
            $mer_id = mer_id();
            $info = $model->when($mer_id>0,function($query)use($mer_id){
                $query->where('merchant_id',$mer_id);
            })->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('批次管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            TraceBatchCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 批次管理添加
     *
     * @param array $param 批次管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new TraceBatchModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = mer_user_id();
        $param['create_time'] = datetime();
        //查询大厅
        if(isset($param['goods_id'])){
            $param['setting_hall_id'] = GoodsModel::where('id',$param['goods_id'])->value('setting_hall_id');
        }
        $model->save($param);
        $id = $model->$pk;
        if (empty($id)) {
            exception();
        }
        $param[$pk] = $id;
        return $param;
    }
     /**
     * 批次管理修改
     *
     * @param int|array $ids   批次管理id
     * @param array     $param 批次管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new TraceBatchModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = mer_user_id();
        $param['update_time'] = datetime();
        $mer_id = mer_id();
        $res = $model->when($mer_id>0,function($query)use($mer_id){
            $query->where('merchant_id',$mer_id);
        })->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        TraceBatchCache::del($ids);
        return $param;
    }
    /**
     * 批次管理删除
     *
     * @param array $ids  批次管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new TraceBatchModel();
        $pk = $model->getPk();
        $mer_id = mer_id();
        if ($real) {
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                $query->where('merchant_id',$mer_id);
            })->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                $query->where('merchant_id',$mer_id);
            })->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        TraceBatchCache::del($ids);
        return $update;
    }

    /**
     * @title:查询审核状态
     * @author：易军辉
     * @date：2024/12/21
     * @return array[]
     */
    public static function getAuthStatus(){
        return TraceBatchModel::AUTH_STATUS;
    }

    /**
     * @title:批次审核
     * @author：易军辉
     * @date：2024/12/21
     * @param $ids
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public static function auth($ids,$param)
    {
        $model = new TraceBatchModel();
        $goodsModel=new GoodsModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $list = $model
            ->where('is_delete',0)
            ->where('auth_status',0)
            ->where($pk, 'in', $ids)
            ->select();
        if(count($list)<=0){
            exception("未有符合条件的批次需要审核");
        }
        // 启动事务
        $model->startTrans();
        try {
            $inventorys = [];
            foreach ($list as $k=>$v){
                $v->auth_time=datetime();
                $v->auth_uid=user_id();
                switch (intval($param['auth_status'])){
                    case 0://待审核
                        $v->auth_status=0;
                        break;
                    case 1://审核通过
                        $v->auth_status=1;
                        /**********************判断是否入库***************************/
                        if($v['is_weighing_warehousing']==1){
                            array_push($inventorys,[
                                'merchant_id'=>$v['merchant_id'],//商家id
                                'trace_batch_id'=>$v['id'],//批次id
                                'goods_id'=>$v['goods_id'],//商品id
                                'goods_num'=>$v['goods_num'],//申请数量
                                'setting_call_id'=>$v['setting_call_id'],//电子秤id
                                'setting_warehouse_id'=>$v['setting_warehouse_id'],//仓库id
                                'setting_hall_id'=>$v['setting_hall_id'],//大厅id
                                'inventory_type'=>1,//出入库类型：1、入库 2、出库
                                'create_time'=>datetime(),
                                'create_uid'=>operate_user_id()
                            ]);
                        }else{
                            //修改商品库存数量
                            $goods_edit = $goodsModel->where('id', $v['goods_id'])->inc('stock', $v['goods_num'])->update();
                            GoodsCache::del($v['goods_id']);
                        }
                        break;
                    case 2://审核失败
                        $v->auth_status=2;
                        $v->auth_msg=$param['auth_msg'];
                        break;
                }
                $v->save();
            }
            if(count($inventorys)>0){
                $inventory_add = GoodsInventoryModel::insertAll($inventorys);
            }
            // 提交事务
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        $param['ids'] = $ids;
        TraceBatchCache::del($ids);
        return $param;
    }
}
