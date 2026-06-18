<?php
namespace app\common\service\order;
use app\common\cache\order\InspectionOrderCache;
use app\common\model\order\InspectionOrderModel;
use app\common\service\file\SettingService;
use think\facade\Db;

/**
 * 检测订单
 */
class InspectionOrderService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'inspection_id' => '',
        'is_disable' => '',
        'merchant_id' => '',
        'goods_id' => '',
        'trace_batch_id' => '',
        'trace_batch_tache_id' => '',
        'trace_batch_tache_value_id' => '',
        'title' => '',
        'remark' => '',
        'inspection_state' => '',
        'inspection_remark' => '',
        'inspection_time' => '',
        'inspection_uid' => '',
        'inspection_reports_ids' => '',
        'price' => '',
        'settlement_status' => '',
        'inspection_result' => '',
        'inspection_reports/a'=>[]
    ];
    /**
     * 检测订单列表
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
        $model = new InspectionOrderModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,inspection_id,is_disable,create_time,update_time,merchant_id,goods_id,trace_batch_id,trace_batch_tache_id,trace_batch_tache_value_id,title,remark,inspection_state,inspection_remark,inspection_time,inspection_uid,inspection_reports_ids,price,settlement_status,inspection_result';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        //关联商品
        if (strpos($field, 'goods_id') !== false) {
            $with[]   = $hidden[]   = 'goods';
            $append[] = 'goods_title';
        }
        //关联批次号
        if (strpos($field, 'trace_batch_id') !== false) {
            $with[]   = $hidden[]   = 'batch';
            $append[] = 'batch_title';
        }
        //关联商家
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
        foreach ($list as $k => $v) {
            if (strpos($field, 'inspection_state') !== false) {
                $list[$k]['inspection_state_title'] = InspectionOrderModel::getState($v['inspection_state'], 2);
            }
        }
        $status_nums = self::getStateNum();
        return compact('count', 'pages', 'page', 'limit', 'list','status_nums');
    }
    /**
     * 查询状态数量
     * @Author: 易军辉
     * @DateTime:2024-07-05 21:04
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getStateNum(){
        $ins_id = ins_id();
        $where = " where is_delete=0";
        if($ins_id>0){
            $where .=" and inspection_id=".$ins_id;
        }
        //根据状态查询数量
        $status_num = Db::query("SELECT inspection_state,count(id) as num from ya_inspection_order ".$where." GROUP BY inspection_state");
        $status_nums = array();
        $status_nums['all'] = Db::name('inspection_order')
            ->where('is_delete',0)
            ->when($ins_id>0, function($query) use ($ins_id) {
                $query->where('inspection_id', $ins_id);
            })->count();
        foreach (InspectionOrderModel::STATE as $k => $v) {
            $status_nums[$v['code']] =0;
            foreach ($status_num as $k1 => $v1) {
                if($v1['inspection_state'] == $v['value']) {
                    $status_nums[$v['code']] = $v1['num'];
                    break;
                }
            }
        }
        return $status_nums;
    }
    /**
     * @title:查询参数
     * @author：易军辉
     * @date：2024/12/6
     */
    public static function getParams(){
        $order_status = InspectionOrderModel::STATE;
        $ins_id = ins_id();
        $merchant_list = Db::query("SELECT id as value,title as label,is_disable as disable from ya_merchant
where is_delete=0 and id in (
	SELECT merchant_id from ya_inspection_order where is_delete=0 and inspection_id=".$ins_id." GROUP BY merchant_id
)
ORDER BY sort asc,id desc");
        $goods_list =Db::query("SELECT id as value,title as label,is_disable as disable from ya_goods
where is_delete=0 and id in (
	SELECT goods_id from ya_inspection_order where is_delete=0 and inspection_id=".$ins_id." GROUP BY goods_id
)
ORDER BY sort asc,id desc");
        $batch_list =Db::query("SELECT id as value,title as label,is_disable as disable from ya_trace_batch
where is_delete=0 and id in (
	SELECT trace_batch_id from ya_inspection_order where is_delete=0 and inspection_id=".$ins_id." GROUP BY trace_batch_id
)
ORDER BY sort asc,id desc");
        return compact('order_status','merchant_list','goods_list','batch_list');
    }
    /**
     * 检测订单信息
     *
     * @param int  $id   检测订单id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = InspectionOrderCache::get($id);
        if (empty($info)) {
            $model = new InspectionOrderModel();
            $with     = ['goods','batch','merchant'=>function($query){
                $query->field('id,title,address,phone');
            }];
            $append   = ['goods_title','batch_title','merchant_title'];
            $hidden   = ['goods','batch'];
            $info = $model->with($with)->append($append)->hidden($hidden)->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('检测订单不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            $info['inspection_reports'] = [];
            $inspection_id = mer_id();
            if (isset($info['inspection_reports_ids'])){
                $inspection_reports_files = Db::name('inspection_file')
                    ->where('is_disable',0)
                    ->where('is_delete',0)
                    ->where('file_id','in',explode(',',$info['inspection_reports_ids']))
                    ->when($inspection_id>0, function($query) use ($inspection_id) {
                        $query->where('ins_id', $inspection_id);
                    })
                    ->select()
                    ->toArray();

                foreach ($inspection_reports_files as $file_k=>$file_v){
                    $inspection_reports_files[$file_k]['file_url'] = SettingService::fileUrl($file_v);

                }
                $info['inspection_reports'] =$inspection_reports_files;
            }
            $info['inspection_state_title'] = InspectionOrderModel::getState($info['inspection_state'], 2);
            InspectionOrderCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 检测订单添加
     *
     * @param array $param 检测订单信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new InspectionOrderModel();
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
     * 检测订单修改
     *
     * @param int|array $ids   检测订单id
     * @param array     $param 检测订单信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new InspectionOrderModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $ins_id=ins_id();
        $list = $model
            ->where($pk, 'in', $ids)
            ->when($ins_id>0, function($query) use ($ins_id) {
                $query->where('inspection_id', $ins_id);
            })
            ->select()
            ->toArray();
        if(count($list)<=0){
            exception('未查询到需要检测的订单');
        }
        // 启动事务
        $model->startTrans();
        try {
            $update_data = [
                'inspection_state' => $param['inspection_state'],
                'update_time'=>datetime(),
                'update_uid'=>ins_user_id(),
                'inspection_time'=>datetime(),
                'inspection_uid'=>ins_user_id(),
            ];
            $inspection_state = $param['inspection_state'];
            switch ($inspection_state) {
                case 0:
                    $update_data['inspection_remark'] = null;
                    $update_data['inspection_result'] = null;
                    $update_data['inspection_time'] = null;
                    $update_data['inspection_uid'] = null;
                    $update_data['inspection_reports_ids'] = null;
                    break;
                case 1:
                    if(isset($param['inspection_reports'])){
                        $update_data['inspection_reports_ids'] =implode(",",array_column($param['inspection_reports'],'file_id'));
                    }
                    $update_data['inspection_result'] = $param['inspection_result'];
                    break;
                case 2:
                    $update_data['inspection_result'] = null;
                    $update_data['inspection_remark'] = $param['inspection_remark'];
                    break;
            }
            $res = $model->where($pk, 'in', array_column($list,$pk))->update($update_data);
            if (empty($res)) {
                exception();
            }
            foreach ($list as $k=>$v){
                $tache_value_res = Db::name('trace_batch_tache_value')
                    ->where('id',$v['trace_batch_tache_value_id'])
                    ->update([
                        'inspection_state'=>$update_data['inspection_state'],
                        'inspection_result'=>isset($update_data['inspection_result'])?$update_data['inspection_result']:null,
                        'inspection_remark'=>isset($update_data['inspection_remark'])?$update_data['inspection_remark']:null,
                        'inspection_time'=>isset($update_data['inspection_time'])?$update_data['inspection_time']:null,
                        'inspection_reports_ids'=>isset($update_data['inspection_reports_ids'])?$update_data['inspection_reports_ids']:null,
                    ]);
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
        InspectionOrderCache::del($ids);
        return $param;
    }
    /**
     * 检测订单删除
     *
     * @param array $ids  检测订单id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new InspectionOrderModel();
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
        InspectionOrderCache::del($ids);
        return $update;
    }
}
