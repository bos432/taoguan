<?php
namespace app\common\service\trace;
use app\common\cache\trace\TraceQrCodeCache;
use app\common\model\trace\TraceQrCodeModel;
use app\common\service\system\SettingService as SystemSettingService;
use app\common\service\utils\QrCodeUtils;
use ZipArchive;

/**
 * 二维码管理
 */
class TraceQrCodeService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'merchant_id' => '',
        'goods_id' => '',
        'is_disable' => '',
        'trace_batch_id' => '',
        'code' => '',
        'anti_fake_code' => '',
        'remark' => '',
        'scanning_num' => '',
        'scanning_time' => '',
        'image_id' => '',
        'image_url' => '',
        'apple_num/d'=>0,
        'is_download/d'=>1,
    ];
    /**
     * 二维码管理列表
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
        $model = new TraceQrCodeModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,merchant_id,goods_id,is_disable,create_time,update_time,trace_batch_id,code,anti_fake_code,remark,scanning_num,scanning_time,image_id,image_url';
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
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            if(isset($v['image_url'])){
                $list[$k]['image_url'] = file_url($v['image_url']);
            }
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * 二维码管理信息
     *
     * @param int  $id   二维码管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = TraceQrCodeCache::get($id);
        if (empty($info)) {
            $model = new TraceQrCodeModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('二维码管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            TraceQrCodeCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 二维码管理添加
     *
     * @param array $param 二维码管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new TraceQrCodeModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        // 启动事务
        $model->startTrans();
        $insert_data=[];
        $zipFile="";
        try {
            //查询批次
            $trace_batch = TraceBatchService::info($param['trace_batch_id']);
            //查询已生成多少二维码
            $count = $model
                ->where('is_delete',0)
                ->where('merchant_id',$trace_batch['merchant_id'])
                ->where('goods_id',$trace_batch['goods_id'])
                ->where('trace_batch_id',$trace_batch['id'])
                ->count();

            $limit_num = $trace_batch['goods_num']*2;
            if(($count+$param['apple_num']) > $limit_num){
                exception("二维码生成已超过限制数量：".$limit_num);
            }
            $codes = TraceQrCodeModel::generateBatchAntiFakeCodes($param['apple_num']);
            if(count($codes)>0){
                $sysSetting = SystemSettingService::info('member_website');
                //生成二维码
                $qr_code_utils = new QrCodeUtils();
                $trace_url =$sysSetting['member_website'];
                $create_uid= operate_user_id();
                $time=datetime();
                // 创建 ZipArchive 实例
                $zip = new ZipArchive();
                if(isset($param['is_download']) && $param['is_download'] == 1){
                    $zipFile = 'storage/file/' . date('Ymd') . '/'.$trace_batch['merchant_id'].$trace_batch['goods_id'].$trace_batch['id']."_".time().".zip";
                    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
                        exception('无法创建 ZIP 文件');
                    }
                }
                foreach ($codes as $k=>$v){
                    $image_url = $qr_code_utils->generateQrCodeUrl($trace_url."/pages/goods/trace?code=".$v,300,$v,false);
                    $insert_data[]=[
                        'merchant_id'=>$trace_batch['merchant_id'],//商家ID
                        'goods_id'=>$trace_batch['goods_id'],//商品ID
                        'create_uid'=>$create_uid,//添加用户id
                        'create_time'=>$time,//创建时间
                        'trace_batch_id'=>$trace_batch['id'],//批次id
                        'code'=>$v,//码
                        'remark'=>isset($param['remark'])?$param['remark']:'',//备注
                        'image_url'=>$image_url,//二维码图片路径
                    ];
                    if(isset($param['is_download']) && $param['is_download'] == 1){
                        $zip->addFile($image_url, $v.".png"); // 添加到 ZIP
                    }
                }
                if(isset($param['is_download']) && $param['is_download'] == 1){
                    $zip->close(); // 关闭 ZIP
                }
            }
            if(count($insert_data)>0){
                $res = $model->insertAll($insert_data);
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
        if($zipFile){
            return $zipFile;
        }else{
            return count($insert_data);
        }
    }

     /**
     * 二维码管理修改
     *
     * @param int|array $ids   二维码管理id
     * @param array     $param 二维码管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new TraceQrCodeModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        TraceQrCodeCache::del($ids);
        return $param;
    }
    /**
     * 二维码管理删除
     *
     * @param array $ids  二维码管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new TraceQrCodeModel();
        $pk = $model->getPk();
        $mer_id=mer_id();
        $list = $model->where($pk, 'in', $ids)
            ->when($mer_id>0,function ($query) use ($mer_id){
                $query->where('merchant_id',$mer_id);
            })
            ->field('id,image_url')
            ->select()
            ->toArray();
        if(count($list)<=0){
            exception("您要删除的二维码不存在");
        }
        foreach ($list as $k=>$v){
            //删除二维码
            if(file_exists($v['image_url'])){
                unlink($v['image_url']);
            }
        }
        if ($real) {
            $res = $model->where($pk, 'in', array_column($list,'id'))->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk, 'in',  array_column($list,'id'))->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        TraceQrCodeCache::del($ids);
        return $update;
    }

    /**
     * @title:二维码批量下载
     * @author：易军辉
     * @date：2024/12/6
     * @param $ids
     * @param $real
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function download($ids, $real = false)
    {
        $model = new TraceQrCodeModel();
        $pk = $model->getPk();
        $mer_id=mer_id();
        $list = $model->where($pk, 'in', $ids)
            ->when($mer_id>0,function ($query) use ($mer_id){
                $query->where('merchant_id',$mer_id);
            })
            ->field('id,image_url')
            ->select()
            ->toArray();
        if(count($list)<=0){
            exception("您要下载的二维码不存在");
        }
        // 创建 ZipArchive 实例
        $zip = new ZipArchive();
        $zipFile = 'storage/file/' . date('Ymd') . '/'.$mer_id."_".time().".zip";
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            exception('无法创建 ZIP 文件');
        }
        foreach ($list as $k=>$v){
            $zip->addFile($v['image_url'], basename($v['image_url'])); // 添加到 ZIP
        }
        return $zipFile;
    }
}
