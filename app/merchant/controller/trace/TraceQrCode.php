<?php
namespace app\merchant\controller\trace;
use app\common\controller\BaseController;
use app\common\service\trace\TraceBatchService;
use app\common\service\trace\TraceQrCodeService;
use app\common\validate\trace\TraceQrCodeValidate;
use think\Response;
use hg\apidoc\annotation as Apidoc;
/**
 * @Apidoc\Title("二维码管理")
 * @Apidoc\Group("trace")
 * @Apidoc\Sort("250")
 */
class TraceQrCode extends BaseController
{
    /**
    * @Apidoc\Title("二维码管理列表")
    * @Apidoc\Query(ref="pagingQuery")
    * @Apidoc\Query(ref="sortQuery")
    * @Apidoc\Query(ref="searchQuery")
    * @Apidoc\Query(ref="dateQuery")
    * @Apidoc\Returned(ref="expsReturn")
    * @Apidoc\Returned(ref="pagingReturn")
    * @Apidoc\Returned("list", type="array", desc="二维码管理列表", children={
    *   @Apidoc\Returned(ref="app\common\model\trace\TraceQrCodeModel", field="id,merchant_id,goods_id,is_disable,create_time,update_time,trace_batch_id,code,anti_fake_code,remark,scanning_num,scanning_time,image_id,image_url")
    * })
    */
    public function list()
    {
        $where = $this->buildWhere([
            'trace_batch_id',
            'goods_id',
            'is_disable'
        ]);
        $where = $this->where(where_delete($where));
        $where[] = ['merchant_id','=',mer_id()];
        $data = TraceQrCodeService::list($where, $this->page(), $this->limit(), $this->order());
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
     *   @Apidoc\Returned(ref="app\common\model\trace\TraceQrCodeModel", field="id,merchant_id,goods_id,is_disable,create_time,update_time,trace_batch_id,code,anti_fake_code,remark,scanning_num,scanning_time,image_id,image_url")
     * })
     */
    public function getParams()
    {
        $where = $this->where(where_delete());
        $where[] = ['merchant_id','=',mer_id()];
        $where[] = ['auth_status','=',1];
        $batch_list = TraceBatchService::list($where,0,0, $this->order(),'id as value,title as label,is_disable as disable,id,goods_id,goods_num');
        return success([
            'batch_list' => $batch_list
        ]);
    }
    /**
    * @Apidoc\Title("二维码管理信息")
    * @Apidoc\Query(ref="app\common\model\trace\TraceQrCodeModel", field="id")
    * @Apidoc\Returned(ref="app\common\model\trace\TraceQrCodeModel")
    */
    public function info()
    {
        $param = $this->params(['id/d' => '']);
        validate(TraceQrCodeValidate::class)->scene('info')->check($param);
        $data = TraceQrCodeService::info($param['id']);
        return success($data);
    }
    /**
    * @Apidoc\Title("二维码管理添加")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\trace\TraceQrCodeModel", field="merchant_id,goods_id,is_disable,trace_batch_id,code,anti_fake_code,remark,scanning_num,scanning_time,image_id,image_url")
    */
    public function add()
    {
        $param = $this->params(TraceQrCodeService::$edit_field);
        $param['merchant_id'] = mer_id();
        validate(TraceQrCodeValidate::class)->scene('add')->check($param);
        $data = TraceQrCodeService::add($param);
        if(isset($param['is_download']) && $param['is_download'] == 1 && $data){
            // 判断文件路径是否有效
            if (is_file($data)) {
                // 读取文件内容
                $fileContent = file_get_contents($data);
                //删除打包文件
                unlink($data);
                // 返回文件内容作为响应，设置适当的头部信息
                return Response::create($fileContent, 'html', 200)
                    ->contentType('application/zip')  // 设置 ZIP 文件的 MIME 类型
                    ->header([
                        'Content-Disposition' => 'attachment; filename="' . urlencode(basename($data)) . '"',  // 设置下载文件名
                        'Cache-Control' => 'max-age=0',  // 防止缓存
                    ]);
            }
        }else{
            return success($data);
        }
    }
    /**
    * @Apidoc\Title("二维码管理修改")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="app\common\model\trace\TraceQrCodeModel", field="id,merchant_id,goods_id,is_disable,trace_batch_id,code,anti_fake_code,remark,scanning_num,scanning_time,image_id,image_url")
    */
    public function edit()
    {
        $param = $this->params(TraceQrCodeService::$edit_field);
        validate(TraceQrCodeValidate::class)->scene('edit')->check($param);
        $data = TraceQrCodeService::edit($param['id'], $param);
        return success($data);
    }
    /**
    * @Apidoc\Title("二维码管理删除")
    * @Apidoc\Method("POST")
    * @Apidoc\Param(ref="idsParam")
    */
    public function dele()
    {
        $param = $this->params(['ids/a' => []]);
        validate(TraceQrCodeValidate::class)->scene('dele')->check($param);
        $data = TraceQrCodeService::dele($param['ids']);
        return success($data);
    }
    /**
     * @Apidoc\Title("二维码管理禁用")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     * @Apidoc\Param(ref="app\common\model\trace\TraceQrCodeModel", field="is_disable")
     */
    public function disable()
    {
        $param = $this->params(['ids/a' => [], 'is_disable/d' => 0]);
        validate(TraceQrCodeValidate::class)->scene('disable')->check($param);
        $data = TraceQrCodeService::edit($param['ids'], $param);
        return success($data);
    }
    /**
     * @Apidoc\Title("二维码批量下载")
     * @Apidoc\Method("POST")
     * @Apidoc\Param(ref="idsParam")
     */
    public function download()
    {
        $param = $this->params(['ids/a' => []]);
        validate(TraceQrCodeValidate::class)->scene('download')->check($param);
        $data = TraceQrCodeService::download($param['ids']);
        // 判断文件路径是否有效
        if (is_file($data)) {
            // 读取文件内容
            $fileContent = file_get_contents($data);
            //删除打包文件
            unlink($data);
            // 返回文件内容作为响应，设置适当的头部信息
            return Response::create($fileContent, 'html', 200)
                ->contentType('application/zip')  // 设置 ZIP 文件的 MIME 类型
                ->header([
                    'Content-Disposition' => 'attachment; filename="' . urlencode(basename($data)) . '"',  // 设置下载文件名
                    'Cache-Control' => 'max-age=0',  // 防止缓存
                ]);
        }
        return error("下载失败，请稍后再试");
    }
}
