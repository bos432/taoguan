<?php
namespace app\common\service\goods;
use app\common\cache\goods\GoodsInventoryCache;
use app\common\model\goods\GoodsInventoryModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberModel;
use app\common\service\file\MerchantFileService;
use app\common\service\member\MemberService;
use app\common\service\system\SettingService as SystemSettingService;
use think\facade\Db;

/**
 * 出入库明细
 */
class GoodsInventoryService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'is_disable' => '',
        'merchant_id' => '',
        'trace_batch_id' => '',
        'goods_id' => '',
        'goods_num' => '',
        'weighing_num' => '',
        'warehousing_num' => '',
        'is_weighing' => '',
        'weighing_uid' => '',
        'weighing_time' => '',
        'setting_call_id' => '',
        'is_warehousing' => '',
        'warehousing_uid' => '',
        'warehousing_time' => '',
        'setting_warehouse_id' => '',
        'setting_hall_id' => '',
        'inventory_type' => '',
        'member_id' => '',
        'member_order_id' => '',
    ];
    /**
     * 出入库明细列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     *
     * @return array
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        $model = new GoodsInventoryModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,merchant_id,trace_batch_id,goods_id,goods_num,weighing_num,warehousing_num,is_weighing,weighing_uid,weighing_time,setting_call_id,is_warehousing,warehousing_uid,warehousing_time,setting_warehouse_id,setting_hall_id,inventory_type,member_id,member_order_id';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }
        if ($page == 0 || $limit == 0) {
            return $model->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * 出入库明细信息
     *
     * @param int  $id   出入库明细id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = GoodsInventoryCache::get($id);
        if (empty($info)) {
            $model = new GoodsInventoryModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('出入库明细不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            GoodsInventoryCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 出入库明细添加
     *
     * @param array $param 出入库明细信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new GoodsInventoryModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();
        $model->save($param);
        $id = $model->$pk;
        if (empty($id)) {
            exception();
        }
        $param[$pk] = $id;
        return $param;
    }
     /**
     * 出入库明细修改
     *
     * @param int|array $ids   出入库明细id
     * @param array     $param 出入库明细信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new GoodsInventoryModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        GoodsInventoryCache::del($ids);
        return $param;
    }
    /**
     * 出入库明细删除
     *
     * @param array $ids  出入库明细id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new GoodsInventoryModel();
        $pk = $model->getPk();
        if ($real) {
            $res = $model->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        GoodsInventoryCache::del($ids);
        return $update;
    }

    /**
     * @title:查询待称重列表
     * @author：易军辉
     * @date：2024/12/25
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @param $source 来源：1、待称重 2、已称重
     * @return array|mixed
     */
    public static function getWeighingWaitList($where = [], $page = 1, $limit = 10,  $order = [], $field = '',$source=1)
    {
        $where[] = ['setting_call_id','in',MemberService::getMemberCallIds(member_id(true))];
        $sql = self::getListSql($where,$order,$field);
        if ($page == 0 || $limit == 0) {
            return json_decode(json_encode(Db::query($sql)), true);
        }
        $count =  Db::query("select count(*) as count from (".$sql.") as co");
        $count=$count[0]['count'];
        $pages = ceil($count / $limit);
        // 计算偏移量
        $offset = ($page - 1) * $limit;
        $list = Db::query($sql. " LIMIT $offset, $limit");
        //查询称重数量上下浮动数
        $sysSetting = SystemSettingService::info('weighing_float_num');
        foreach ($list as $k=>$v) {
            //商品缩略图
            if(isset($v['goods_image_id'])) {
                $file = MerchantFileService::info($v['goods_image_id'],false);
                if ($file && isset($file['file_url'])) {
                    $list[$k]['goods_image_url'] = $file['file_url'];
                }
            }
            if($source==1){
                $list[$k]['weighing_num'] =$v['goods_num'];
                $list[$k]['checked'] =false;
                //称重数最小
                $weighing_min = $v['goods_num'] -$sysSetting['weighing_float_num'];
                if($weighing_min>0){
                    $list[$k]['weighing_min'] =$weighing_min;
                }else{
                    $list[$k]['weighing_min'] =1;
                }
                //称重数最大
                $list[$k]['weighing_max'] =$v['goods_num'] +$sysSetting['weighing_float_num'];
            }
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    /**
     * @title:组装查询SQL
     * @author：易军辉
     * @date：2024/12/24
     * @param $where 查询条件
     * @param $order 排序
     * @param $field 字段
     * @return string
     */
    private static function getListSql($where = [], $order = [],$field = '')
    {
        /***************************查询字段***********************************************/
        if (empty($field)) {
            $field = 'mer.title as mer_title,
                    mer.phone as mer_phone,
                    goods.title as goods_title,
                    goods.spec as goods_spec,
                    goods.unit as goods_unit,
                    goods.price as goods_price,
                    goods.image_id as goods_image_id,
                    batch.id as batch_id,
                    batch.title as batch_title,
                    scale.title as scale_title,
                    warehouse.code as warehouse_code,
                    warehouse.title as warehouse_title,
                    hall.title as hall_title,
                    a.id,
                    a.goods_id,
                    a.create_time,
                    a.weighing_time,
                    a.inventory_type,
                    a.goods_num,
                    a.weighing_num,
                    a.warehousing_num';
        }
        /***************************组装条件***********************************************/
        $where_sql = "";
        foreach ($where as $key=>$val){
            $prefix = "a.";
            if(strpos($val[0], '.') !== false){
                $prefix = "";
            }else{
                $prefix = "a.";
            }
            if($where_sql!=""){
                $where_sql .=" and ";
            }
            if($val[0] == 'keyword'){
                $where_sql .= " (
                    mer.title LIKE '%".$val[2]."%' 
                    or mer.phone LIKE '%".$val[2]."%'
                    or goods.title LIKE '%".$val[2]."%'
                    or goods.code LIKE '%".$val[2]."%'
                    or batch.title LIKE '%".$val[2]."%'
                    or scale.title LIKE '%".$val[2]."%'
                    or warehouse.title LIKE '%".$val[2]."%'
                    or warehouse.code LIKE '%".$val[2]."%'
                    or hall.title LIKE '%".$val[2]."%'
                ) ";
            }else if(is_array($val[2])) {
                $where_sql .= $prefix . $val[0] . " " . $val[1] . "(" . implode(',', $val[2]) . ")";
            }else if($val[1] == 'is'){
                $where_sql .=$prefix.$val[0]." ".$val[1]." ".$val[2];
            }else{
                $where_sql .=$prefix.$val[0]." ".$val[1]." '".$val[2]."' ";
            }
        }

        /***************************组装排序***********************************************/
        $order_sql = "";
        foreach ($order as $key=>$val){
            if($order_sql!=""){
                $order_sql .=" , ";
            }
            $order_sql .="a.".$key." ".$val." ";
        }
        if($order_sql == ""){
            $order_sql = "a.merchant_id asc,a.id asc";
        }
        $sql = <<<SQL
            SELECT 
                 {$field}
                from ya_goods_inventory as a
                -- 关联商家
                LEFT JOIN ya_merchant as mer on a.merchant_id=mer.id
                -- 关联商品
                LEFT JOIN ya_goods as goods on a.goods_id=goods.id
                -- 关联批次
                LEFT JOIN ya_trace_batch as batch on a.trace_batch_id=batch.id
                -- 关联电子秤
                LEFT JOIN ya_setting_call as scale on a.setting_call_id=scale.id
                -- 关联仓库
                LEFT JOIN ya_setting_warehouse as warehouse on a.setting_warehouse_id=warehouse.id
                -- 关联大厅
                LEFT JOIN ya_setting_hall as hall on a.setting_hall_id=hall.id
            WHERE
                {$where_sql}
            ORDER BY 
                {$order_sql}
        SQL;
        return $sql;
    }

    /**
     * @title:保存称重
     * @author：易军辉
     * @date：2024/12/25
     * @param $list
     */
    public static function saveWeighingWait($list)
    {
        $model = new GoodsInventoryModel();
        // 启动事务
        $model::startTrans();
        $count  = 0;
        try {
            $member_id = member_id(true);
            foreach ($list as $key=>$val){
                //保存称重数据
                $save_data = [
                    'update_uid'=>$member_id,
                    'update_time'=>date('Y-m-d H:i:s'),
                    'weighing_num'=>$val['weighing_num'],
                    'is_weighing'=>1,
                    'weighing_uid'=>$member_id,
                    'weighing_time'=>date('Y-m-d H:i:s'),
                ];
                //若是自存，称重后即可入库
                if(isset($val['warehouse_code']) && $val['warehouse_code'] =='ZC'){
                    $save_data['warehousing_num'] = $val['weighing_num'];
                    $save_data['is_warehousing'] = 1;
                    $save_data['warehousing_uid'] = $member_id;
                    $save_data['warehousing_time'] = date('Y-m-d H:i:s');
                    //添加商品库存数
                    if(isset($val['goods_id'])){
                        $goods_edit = GoodsModel::where('id', $val['goods_id'])
                            ->inc('stock', $val['weighing_num'])    // 增加库存
                            ->update([
                                'update_time'=>datetime(),
                            ]);
                    }
                }
                $res = $model->where('id',$val['id'])->update($save_data);
                if($res){
                    //更改溯源批次数据
                    if(isset($val['batch_id'])){
                        $batch_res = Db::name('trace_batch')->where('id',$val['batch_id'])->update($save_data);
                    }
                    $count +=1;
                }
            }
            // 提交事务
            $model::commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model::rollback();
        }
        if (isset($errmsg)) {
            exception($errmsg);
        }
        return $count;
    }

    /**
     * @title:称重管理统计
     * @author：易军辉
     * @date：2024/12/25
     */
    public static function getWeighingStatistics(){
        $member_id = member_id(true);
        $model = new GoodsInventoryModel();
        $goodsModel = new GoodsModel();
        $setting_call_id = MemberService::getMemberCallIds($member_id);
        $where[] = ['setting_call_id','in',$setting_call_id];
        $where[] = ['is_delete','=',0];
        $where[] = ['is_disable','=',0];
        $where[] = ['inventory_type','=',1];
        $weighing_wait_num = $model->where($where)->where('is_weighing',0)->count();
        $weighing_success_num = $model->where($where)->where('is_weighing',1)->where('weighing_uid',$member_id)->count();
        $goodsWhere[] = ['setting_call_id','in',$setting_call_id];
        $goodsWhere[] = ['is_delete','=',0];
        $goodsWhere[] = ['is_disable','=',0];
        $goodsWhere[] = ['source','=',1];
        $offline_wait_num = $goodsModel->where($goodsWhere)->where('is_weighing',0)->count();
        $offline_success_num = $goodsModel->where($goodsWhere)->where('is_weighing',1)->where('weighing_uid',$member_id)->count();
        $data = [
            'weighing_wait_num'=>$weighing_wait_num,
            'weighing_success_num'=>$weighing_success_num,
            'offline_wait_num'=>$offline_wait_num,
            'offline_success_num'=>$offline_success_num,
        ];
        return $data;
    }
    /**
     * @title:仓库管理列表
     * @author：易军辉
     * @date：2024/12/25
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @param $source 来源：1、待入库 2、待出库 3、出入库记录
     * @return array|mixed
     */
    public static function getWarehousingList($where = [], $page = 1, $limit = 10,  $order = [], $field = '',$source=1)
    {
        $where[] = ['setting_warehouse_id','in',MemberService::getMemberWarehouseIds(member_id(true))];
        $sql = self::getListSql($where,$order,$field);
        if ($page == 0 || $limit == 0) {
            return json_decode(json_encode(Db::query($sql)), true);
        }
        $count =  Db::query("select count(*) as count from (".$sql.") as co");
        $count=$count[0]['count'];
        $pages = ceil($count / $limit);
        // 计算偏移量
        $offset = ($page - 1) * $limit;
        $list = Db::query($sql. " LIMIT $offset, $limit");
        //查询称重数量上下浮动数
        $sysSetting = SystemSettingService::info('weighing_float_num');
        foreach ($list as $k=>$v) {
            //商品缩略图
            if(isset($v['goods_image_id']) && $v['goods_image_id']) {
                $file = MerchantFileService::info($v['goods_image_id']);
                if ($file && isset($file['file_url'])) {
                    $list[$k]['goods_image_url'] = $file['file_url'];
                }
            }
            if($source==1){
                $list[$k]['warehousing_num'] =$v['weighing_num'];
                $list[$k]['checked'] =false;
                //入库数最小
                $warehousing_min = $list[$k]['warehousing_num'] -$sysSetting['weighing_float_num'];
                if($warehousing_min>0){
                    $list[$k]['warehousing_min'] =$warehousing_min;
                }else{
                    $list[$k]['warehousing_min'] =1;
                }
                //入库数最大
                $list[$k]['warehousing_max'] =$v['goods_num'] +$sysSetting['weighing_float_num'];
            }
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    /**
     * @title:入库
     * @author：易军辉
     * @date：2024/12/25
     * @param $list
     */
    public static function saveWarehousingStore($list)
    {
        $model = new GoodsInventoryModel();
        // 启动事务
        $model::startTrans();
        $count  = 0;
        try {
            $member_id = member_id(true);
            foreach ($list as $key=>$val){
                //保存入库数据
                $save_data = [
                    'update_uid'=>$member_id,
                    'update_time'=>date('Y-m-d H:i:s'),
                    'warehousing_num'=>$val['warehousing_num'],
                    'is_warehousing'=>1,
                    'warehousing_uid'=>$member_id,
                    'warehousing_time'=>date('Y-m-d H:i:s'),
                ];
                //添加商品库存数
                if(isset($val['goods_id'])){
                    $goods_edit = GoodsModel::where('id', $val['goods_id'])
                        ->inc('stock', $val['warehousing_num'])    // 增加库存
                        ->update([
                            'update_time'=>datetime(),
                        ]);
                }
                $res = $model->where('id',$val['id'])->update($save_data);
                if($res){
                    //更改溯源批次数据
                    if(isset($val['batch_id'])){
                        $batch_res = Db::name('trace_batch')->where('id',$val['batch_id'])->update($save_data);
                    }
                    $count +=1;
                }
            }
            // 提交事务
            $model::commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model::rollback();
        }
        if (isset($errmsg)) {
            exception($errmsg);
        }
        return $count;
    }
}
