<?php
namespace app\common\service\trace;
use app\common\model\trace\TraceBatchModel;
use app\common\model\trace\TraceBatchTacheModel;
use app\common\cache\trace\TraceBatchTacheCache;
use app\common\service\file\SettingService;
use think\facade\Db;

/**
 * 批次环节录入
 */
class TraceBatchTacheService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'describe' => '',
        'is_disable' => '',
        'sort' => '',
        'remark' => '',
        'merchant_id' => '',
        'trace_batch_id' => '',
        'goods_id' => '',
        'trace_tache_id' => '',
        'tacheValue/a' => [],
    ];
    /**
     * 批次环节录入列表
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
        $model = new TraceBatchTacheModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,describe,is_disable,create_time,update_time,sort,remark,merchant_id,trace_batch_id,goods_id,trace_tache_id';
        }
        if (empty($order)) {
            $order = ['trace_batch_id'=>'desc','sort' => 'asc',$pk => 'desc'];
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
        //关联环节
        if (strpos($field, 'trace_tache_id') !== false) {
            $with[]   = $hidden[]   = 'tache';
            $append[] = 'tache_title';
        }
        //关联属性值
        if (strpos($field, 'id') !== false) {
            $with[]= 'tacheValue';
        }
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $key=>$val){
            $list[$key]['value_str'] ="";
            $list[$key]['refuse'] ="";
            if(isset($val['tacheValue'])){
                foreach ($val['tacheValue'] as $k=>$v){
                    if($list[$key]['value_str']!=""){
                        $list[$key]['value_str'] .="，";
                    }
                    $value = $v['value'];
                    if($v['is_inspection_type'] == 1 && $v['inspection_id']){
                        $value =Db::name('inspection')->where('id',$v['inspection_id'])->value('title');
                        $list[$key]['tacheValue'][$k]['inspection_title'] = $value;
                        if($v['inspection_state']==2){
                            $list[$key]['refuse'] =$value."检测失败，请及时处理";
                        }
                    }
                    $list[$key]['value_str'] .=$v['label']."：".$value;
                }
            }
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * 批次环节录入信息
     *
     * @param int  $id   批次环节录入id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
//        $info = TraceBatchTacheCache::get($id);
//        if (empty($info)) {
            $model = new TraceBatchTacheModel();
            $mer_id = mer_id();
            $info = $model->with(['tacheValue'])->when($mer_id>0,function($query)use($mer_id){
                        $query->where('merchant_id',$mer_id);
                    })->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('批次环节不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            $merchantId=mer_id();
            foreach ($info['tacheValue'] as $k=>$v){
                if (isset($v['inspection_id'])){
                    $inspection = Db::name('inspection')->where('id',$v['inspection_id'])->field('address,phone')->find();
                    $info['tacheValue'][$k]['inspection_address'] =$inspection['address'];
                    $info['tacheValue'][$k]['inspection_phone'] =$inspection['phone'];
                }
                if (isset($v['reports_ids'])){
                    $reports_files = Db::name('merchant_file')
                        ->where('is_disable',0)
                        ->where('is_delete',0)
                        ->where('file_id','in',explode(',',$v['reports_ids']))
                        ->when($merchantId>0, function($query) use ($merchantId) {
                            $query->where('mer_id', $merchantId);
                        })
                        ->select()
                        ->toArray();

                    foreach ($reports_files as $file_k=>$file_v){
                        $reports_files[$file_k]['file_url'] = SettingService::fileUrl($file_v);

                    }
                    $info['tacheValue'][$k]['reports'] =$reports_files;
                }
                if (isset($v['inspection_reports_ids'])){
                    $inspection_reports_files = Db::name('inspection_file')
                        ->where('is_disable',0)
                        ->where('is_delete',0)
                        ->where('file_id','in',explode(',',$v['inspection_reports_ids']))
                        ->when($v['inspection_id']>0, function($query) use ($v) {
                            $query->where('ins_id', $v['inspection_id']);
                        })
                        ->select()
                        ->toArray();

                    foreach ($inspection_reports_files as $file_k=>$file_v){
                        $inspection_reports_files[$file_k]['file_url'] = SettingService::fileUrl($file_v);

                    }
                    $info['tacheValue'][$k]['inspection_reports'] =$inspection_reports_files;
                }
            }
//            TraceBatchTacheCache::set($id, $info);
//        }
        return $info;
    }
    /**
     * 批次环节录入添加
     *
     * @param array $param 批次环节录入信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new TraceBatchTacheModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = mer_user_id();
        $param['create_time'] = datetime();
        // 启动事务
        $model->startTrans();
        try {
            $values = [];
            if(isset($param['tacheValue']) && is_array($param['tacheValue'])){
                $values = $param['tacheValue'];
                unset($param['tacheValue']);
            }
            if(isset($param['trace_batch_id'])){
                $param['goods_id'] = TraceBatchModel::getGoodsId($param['trace_batch_id']);
            }

            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            /******************************处理批次环节录入属性值********************************/
            $inspection_order_insert = [];//检测机构订单
            foreach ($values as $k=>$v){
                $insert_data=[
                    'trace_batch_tache_id'=>$id,//批次环节录入id
                    'trace_tache_id'=>$v['trace_tache_id'],//环节模板id
                    'type'=>$v['type'],//属性类型：1、文本 2、单选 3、多选 4、日期 5、地区
                    'label'=>$v['label'],//属性名称
                    'value'=>$v['value'],//属性值
                    'is_inspection_type'=>$v['is_inspection_type'],//检测类型：0、无需检测 1、送检 2自检
                    'inspection_id'=>$v['inspection_id'],//检测机构id
                    'inspection_state'=>0,//检测状态：0、待检测 1、已检测 2、检测失败
                    'reports_ids'=>implode(",",array_column($v['reports'],'file_id')),//自检检测报告文件ids
                ];
                $trace_batch_tache_value_id = Db::name('trace_batch_tache_value')->insertGetId($insert_data);
                if($v['is_inspection_type']==1 && isset($v['inspection_id'])){
                    $inspection_order_insert[] = [
                        'inspection_id'=>$v['inspection_id'],//检测机构id
                        'create_uid'=>operate_user_id(),//添加用户id
                        'create_time'=>datetime(),//创建时间
                        'merchant_id'=>$param['merchant_id'],//商家id
                        'goods_id'=>$param['goods_id'],//商家商品id
                        'trace_batch_id'=>$param['trace_batch_id'],//商家商品批次id
                        'trace_batch_tache_id'=>$id,//批次环节录入id
                        'trace_batch_tache_value_id'=>$trace_batch_tache_value_id,//批次环节录入属性值id
                        'title'=>$v['label'],//检测名称
                        'inspection_state'=>0,//检测状态：0、待检测 1、已检测 2、检测失败
                    ];
                }
            }
            if(count($inspection_order_insert)>0){
                $order_res = Db::name('inspection_order')->insertAll($inspection_order_insert);
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

        $param[$pk] = $id;
        return $param;
    }
     /**
     * 批次环节录入修改
     *
     * @param int|array $ids   批次环节录入id
     * @param array     $param 批次环节录入信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new TraceBatchTacheModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = mer_user_id();
        $param['update_time'] = datetime();
        // 启动事务
        $model->startTrans();
        try {
            $values = [];
            if(isset($param['tacheValue']) && is_array($param['tacheValue'])){
                $values = $param['tacheValue'];
                unset($param['tacheValue']);
            }
            if(isset($param['trace_batch_id'])){
                $param['goods_id'] = TraceBatchModel::getGoodsId($param['trace_batch_id']);
            }
            $mer_id = mer_id();
            $res = $model->when($mer_id>0,function($query)use($mer_id){
                    $query->where('merchant_id',$mer_id);
                })->where($pk, 'in', $ids)->update($param);
            if (empty($res)) {
                exception();
            }
            /******************************处理批次环节录入属性值********************************/
            $inspection_order_insert = [];//检测机构订单
            foreach ($values as $k=>$v){
                $update_data=[
                    'type'=>$v['type'],//属性类型：1、文本 2、单选 3、多选 4、日期 5、地区
                    'label'=>$v['label'],//属性名称
                    'value'=>$v['value'],//属性值
                    'is_inspection_type'=>$v['is_inspection_type'],//检测类型：0、无需检测 1、送检 2自检
                    'reports_ids'=>implode(",",array_column($v['reports'],'file_id')),//自检检测报告文件ids
                ];
                //更换检测机构
                if($v['is_inspection_type']==1 && $v['inspection_state']!=1){
                    $update_data['inspection_id']=$v['inspection_id'];
                    $inspection_id=Db::name('trace_batch_tache_value')->where('id',$v['id'])->value('inspection_id');
                    if($inspection_id!=$v['inspection_id'] && isset($v['inspection_id'])){
                        $update_data['inspection_state']=0;
                        $update_data['inspection_result']=null;
                        $update_data['inspection_remark']=null;
                        $update_data['inspection_time']=null;
                        $update_data['inspection_reports_ids']=null;
                        $inspection_order_insert[] = [
                            'inspection_id'=>$v['inspection_id'],//检测机构id
                            'create_uid'=>operate_user_id(),//添加用户id
                            'create_time'=>datetime(),//创建时间
                            'merchant_id'=>$param['merchant_id'],//商家id
                            'goods_id'=>$param['goods_id'],//商家商品id
                            'trace_batch_id'=>$param['trace_batch_id'],//商家商品批次id
                            'trace_batch_tache_id'=>$v['trace_batch_tache_id'],//批次环节录入id
                            'trace_batch_tache_value_id'=>$v['id'],//批次环节录入属性值id
                            'title'=>$v['label'],//检测名称
                            'inspection_state'=>0,//检测状态：0、待检测 1、已检测 2、检测失败
                        ];
                    }
                }
                $trace_batch_tache_value_res = Db::name('trace_batch_tache_value')->where('id',$v['id'])->update($update_data);
            }
            if(count($inspection_order_insert)>0){
                $order_res = Db::name('inspection_order')->insertAll($inspection_order_insert);
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
        TraceBatchTacheCache::del($ids);
        return $param;
    }
    /**
     * 批次环节录入删除
     *
     * @param array $ids  批次环节录入id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new TraceBatchTacheModel();
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
        TraceBatchTacheCache::del($ids);
        return $update;
    }

    /**
     * @title:根据批次id查询溯源信息
     * @author：易军辉
     * @date：2024/12/21
     * @param $trace_batch_id
     */
    public static function getBatchTache($trace_batch_id){
        $model = new TraceBatchTacheModel();
        $pk = $model->getPk();
        $field = 'id,trace_batch_id,trace_tache_id';
        $order = ['trace_batch_id'=>'desc','sort' => 'asc',$pk => 'desc'];
        $with     = [];
        $append   = [];
        $hidden   = [];
        //关联环节
        if (strpos($field, 'trace_tache_id') !== false) {
            $with[]   = $hidden[]   = 'tache';
            $append[] = 'tache_title';
        }
        //关联属性值
        if (strpos($field, 'id') !== false) {
            $with[]= 'tacheValue';
        }
        $list = $model
            ->with($with)
            ->append($append)
            ->hidden($hidden)
            ->field($field)
            ->where('trace_batch_id',$trace_batch_id)
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->order($order)
            ->select()
            ->toArray();
        foreach ($list as $key=>$val){
            if(isset($val['tacheValue'])){
                foreach ($val['tacheValue'] as $k=>$v){
                    if($v['is_inspection_type'] == 1 && $v['inspection_id']){
                        $inspection_title =Db::name('inspection')->where('id',$v['inspection_id'])->value('title');
                        $list[$key]['tacheValue'][$k]['inspection_title'] = $inspection_title;
                    }
                    if (isset($v['reports_ids'])){
                        $reports_files = Db::name('merchant_file')
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->where('file_id','in',explode(',',$v['reports_ids']))
                            ->select()
                            ->toArray();
                        $reports = [];
                        foreach ($reports_files as $file_k=>$file_v){
//                            $reports_files[$file_k]['file_url'] = SettingService::fileUrl($file_v);
                            array_push($reports,[
                                'file_url'=>SettingService::fileUrl($file_v),
                                'file_ext'=>$file_v['file_ext'],
                                'file_name'=>$file_v['file_name'],
                            ]);
                        }
                        $list[$key]['tacheValue'][$k]['reports'] =$reports;
                    }
                    if (isset($v['inspection_reports_ids'])){
                        $inspection_reports_files = Db::name('inspection_file')
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->where('file_id','in',explode(',',$v['inspection_reports_ids']))
                            ->select()
                            ->toArray();
                        $inspection_reports=[];
                        foreach ($inspection_reports_files as $file_k=>$file_v){
//                            $inspection_reports_files[$file_k]['file_url'] = SettingService::fileUrl($file_v);
                            array_push($inspection_reports,[
                                'file_url'=>SettingService::fileUrl($file_v),
                                'file_ext'=>$file_v['file_ext'],
                                'file_name'=>$file_v['file_name'],
                            ]);
                        }
                        $list[$key]['tacheValue'][$k]['inspection_reports'] =$inspection_reports;
                    }
                }
            }
        }
        /*********************************查询商品信息*********************************/
        $batchModel = new TraceBatchModel();
        $batch = $batchModel
            ->where('id',$trace_batch_id)
            ->with(['goods'=>function($query){
                $query->field('id,title,spec,unit,image_id');
            },'goods.image'])
            ->field('id,title,goods_id,goods_num')
            ->find();
        return compact('batch', 'list');
    }
}
