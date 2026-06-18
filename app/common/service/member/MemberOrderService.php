<?php
namespace app\common\service\member;
use app\api\controller\member\MemberOrder;
use app\common\cache\member\MemberOrderCache;
use app\common\model\file\FileModel;
use app\common\model\file\MerchantFileModel;
use app\common\model\goods\GoodsImagesModel;
use app\common\model\goods\GoodsInventoryModel;
use app\common\model\goods\GoodsLabelModel;
use app\common\model\goods\GoodsModel;
use app\common\service\finance\MerchantPurchaseLedgerService;
use app\common\service\goods\GoodsBuyLockService;
use app\common\model\member\MemberOrderDetailedModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\member\MemberShopCartModel;
use app\common\model\member\ThirdModel;
use app\common\model\merchant\MerchantModel;
use app\common\model\setting\SettingDeliveryModel;
use app\common\service\member\SettingService as MemberSettingService;
use app\common\service\file\SettingService as FileSettingService;
use app\common\service\merchant\MerchantService;
use app\common\service\setting\SettingDeliveryService;
use app\common\service\system\MerchantPurchaseLimitService;
use EasyWeChat\Factory;
use think\facade\Config;
use think\facade\Db;
use thirdsdk\Http;
/**
 * 订单管理
 */
class MemberOrderService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'is_disable' => '',
        'order_no' => '',
        'member_id' => '',
        'cart_id' => '',
        'freight_price' => '',
        'total_num' => '',
        'total_price' => '',
        'pay_price' => '',
        'pay_status' => '',
        'pay_time' => '',
        'pay_type' => '',
        'pay_voucher_imgs' => '',
        'status' => '',
        'refund_status' => '',
        'refund_type' => '',
        'refund_express' => '',
        'refund_express_name' => '',
        'refund_reason_wap_img_ids' => '',
        'refund_reason_wap_explain' => '',
        'refund_reason_time' => '',
        'refund_reason_wap' => '',
        'refund_reason' => '',
        'refund_price' => '',
        'delivery_name' => '',
        'delivery_code' => '',
        'delivery_type' => '',
        'kuaidi_order_no' => '',
        'mark' => '',
        'remark' => '',
        'merchant_id' => '',
        'take_name'=>'',
        'take_phone'=>'',
        'take_region'=>'',
        'take_address'=>'',
        'self_name'=>'',
        'self_phone'=>'',
        'merchant_list'=>'',
        'source'=>'',
        'goods_ids'=>'',
    ];

    private static function maskDisplayTitle(string $value = ''): string
    {
        return MerchantModel::formatDisplayTitle($value);
    }

    private static function maskPhone(string $value = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $length = mb_strlen($value);
        if ($length <= 7) {
            return self::maskDisplayTitle($value);
        }

        return mb_substr($value, 0, 3) . '****' . mb_substr($value, -4);
    }

    private static function buildConfirmMerchantList(array $merchantIds = []): array
    {
        $merchantIds = array_values(array_unique(array_map('intval', $merchantIds)));
        sort($merchantIds);

        if (count($merchantIds) > 1) {
            exception("请选择单个商家的商品进行结算");
        }
        if (count($merchantIds) <= 0) {
            return [];
        }

        $merchantMap = [];
        $realMerchantIds = [];
        foreach ($merchantIds as $merchantId) {
            if ($merchantId > 0) {
                $realMerchantIds[] = $merchantId;
            }
        }

        if (count($realMerchantIds) > 0) {
            $merchantRows = MerchantModel::whereIn('id', $realMerchantIds)
                ->where('is_delete', 0)
                ->where('is_disable', 0)
                ->with(['image'])
                ->append(['image_url'])
                ->hidden(['image'])
                ->field('id,title,address,image_id')
                ->select()
                ->toArray();
            foreach ($merchantRows as $merchantRow) {
                $merchantMap[intval($merchantRow['id'] ?? 0)] = $merchantRow;
            }
        }

        if (in_array(0, $merchantIds, true)) {
            $merchantMap[0] = [
                'id' => 0,
                'title' => '平台自营',
                'address' => '',
                'image_id' => 0,
                'image_url' => '',
                'receipt_image_url' => '',
            ];
        }

        $merchantList = [];
        foreach ($merchantIds as $merchantId) {
            if (!isset($merchantMap[$merchantId])) {
                exception("结算商家异常，请稍后重试");
            }
            $merchantList[] = $merchantMap[$merchantId];
        }

        return $merchantList;
    }

    private static function shouldUsePlatformVoucher(int $merchantId = 0, array $settingInfo = []): bool
    {
        // 平台自营商家（id=0）总是使用平台收款码
        if ($merchantId === 0) {
            return true;
        }

        // 其他商家根据微信审核设置决定
        return intval($settingInfo['wx_approved'] ?? 0) === 1;
    }

    private static function resolveReceiptImageUrl(array $merchantInfo = [], array $settingInfo = []): string
    {
        $merchantImageUrl = trim((string)($merchantInfo['image_url'] ?? ''));
        $platformVoucherImageUrl = trim((string)($settingInfo['platform_voucher_image_url'] ?? ''));
        $merchantId = intval($merchantInfo['id'] ?? 0);

        if (self::shouldUsePlatformVoucher($merchantId, $settingInfo) && $platformVoucherImageUrl !== '') {
            return $platformVoucherImageUrl;
        }

        return $merchantImageUrl;
    }
    /**
     * 订单管理列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * @param string $source 来源：1、总后端、2、商家端 3、移动端
     *
     * @return array
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '',$source=1)
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        //关联详情
        if(strpos($field, 'id') !== false && ($source==3 || $source==1)){
            $with = [
                'detaileds'=>function($query){
                    $query->field('id,member_order_id,goods_id,quantity,price,total');
                },
                'detaileds.goods',
                'detaileds.goods.image',
            ];
        }
        //关联商家
        if (strpos($field, 'merchant_id') !== false) {
            $with[]   = $hidden[]   = 'merchant';
            $append[] = 'merchant_title';
        }
        //关联用户
        if (strpos($field, 'member_id') !== false) {
            $with[]   = $hidden[]   = 'member';
            $append[] = 'member_title';
        }
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
//        var_dump($model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->buildSql());exit();
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            if (strpos($field, 'status') !== false) {
                $list[$k]['status_title'] = MemberOrderModel::getStatus($v['status'], 2);
            }
            if (strpos($field, 'pay_type') !== false) {
                $list[$k]['pay_type_title'] = MemberOrderModel::getPayType($v['pay_type'], 2);
            }
            if (strpos($field, 'member_id') !== false) {
                $title = MerchantModel::where('member_id',$v['member_id'])
                    ->where('is_disable',0)
                    ->where('is_delete',0)
                    ->where('auth_state',1)
                    ->order('id','desc')
                    ->value('title');
                if($title){
                    $list[$k]['member_title'] = self::maskDisplayTitle((string) $title);
                }
            }
            if(isset($v['detaileds'])){
                foreach ($v['detaileds'] as $k1 => $v1) {
                    $labels=[];
                    if(isset($v1['goods']['goods_label_id'])){
                        $labels = GoodsLabelModel::whereIn('id',$v1['goods']['goods_label_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->column('title');
                    }
                    $list[$k]['detaileds'][$k1]['labels'] = $labels;
                }
            }
            //查询售后图片
            if(($source==1 || $source==2) && isset($v['refund_reason_wap_img_ids'])){
                $files = Db::name('file')
                    ->where('is_disable',0)
                    ->where('is_delete',0)
                    ->where('file_id','in',explode(',',$v['refund_reason_wap_img_ids']))
                    ->select()
                    ->toArray();
                foreach ($files as $fk=>$fv){
                    $files[$fk]['file_url'] = FileSettingService::fileUrl($fv);
                }
                $list[$k]['refund_reason_wap_imgs']=$files;
            }
            //支付凭证图片
            if(isset($v['pay_voucher_imgs'])){
                $files = Db::name('file')
                    ->where('is_disable',0)
                    ->where('is_delete',0)
                    ->where('file_id','in',explode(',',$v['pay_voucher_imgs']))
                    ->select()
                    ->toArray();
                foreach ($files as $fk=>$fv){
                    $files[$fk]['file_url'] = FileSettingService::fileUrl($fv);
                }
                $list[$k]['pay_voucher_imgs']=$files;
            }
            //移动端展示支付审核失败情况
            if($source==3 && isset($v['pay_type']) && isset($v['pay_status']) && isset($v['status']) && $v['status']==0 && $v['pay_status']!=1 && $v['pay_type']==MemberOrderModel::getPayType('voucher', 1) ){
                if(isset($v['pay_auth_msg']) && $v['pay_auth_msg']){
                    $list[$k]['status_title'] = "支付失败(".$v['pay_auth_msg'].")";
                }else{
                    $list[$k]['status_title'] .="(待审核)";
                }
            }
        }
        //统计查询
        if ($source == 3) {
            foreach ($list as $k => $v) {
                if (isset($list[$k]['member_title']) && $list[$k]['member_title'] !== '') {
                    $list[$k]['member_title'] = self::maskDisplayTitle((string) $list[$k]['member_title']);
                }
            }
        }
        $status_nums =[];
        switch ($source){
            case 1:
                $status_nums =self::getStatusNum(0);
                break;
            case 2:
                break;
            case 3:
                break;
        }
        return compact('count', 'pages', 'page', 'limit', 'list','status_nums');
    }

    /**
     * @title:查询订单数量
     * @author：易军辉
     * @date：2024/12/15
     * @param $member_id
     * @return array
     */
    public static function getStatusNum($member_id=0){
        $baseQuery = Db::name('member_order')
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->when($member_id > 0, function($query) use ($member_id) {
                $query->where('member_id', $member_id);
            });

        $statusQuery = clone $baseQuery;

        $status_num = $statusQuery
            ->field('status, count(id) as num')
            ->group('status')
            ->select()
            ->toArray();

        $status_nums = array();
        $status_nums['all'] = (clone $baseQuery)->count();

        foreach (MemberOrderModel::STATUS as $k => $v) {
            $status_nums[$v['code']] = 0;
            foreach ($status_num as $k1 => $v1) {
                if (intval($v1['status'] ?? -999) === intval($v['value'])) {
                    $status_nums[$v['code']] = intval($v1['num'] ?? 0);
                    break;
                }
            }
        }

        return $status_nums;
    }
    /**
     * 订单管理信息
     *
     * @param int  $id   订单管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MemberOrderCache::get($id);
        if (empty($info)) {
            $model = new MemberOrderModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('订单管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MemberOrderCache::set($id, $info);
        }
        return $info;
    }

    /**
     * @title:查询订单详情
     * @author：易军辉
     * @date：2025/2/11
     * @param $id 订单ID
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     */
    public static function getInfo($id,$source=1,$where=[],$field="")
    {
        $model = new MemberOrderModel();
        if (empty($field)) {
            $field = '*';
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        //关联详情
        if(strpos($field, 'id') !== false && $source==2){
            $with = [
                'detaileds'=>function($query){
                    $query->field('id,member_order_id,goods_id,quantity,price,total');
                },
                'detaileds.goods',
                'detaileds.goods.image',
            ];
        }
        //关联用户
        if (strpos($field, 'member_id') !== false) {
            $with[]  = 'member';
        }
        //关联订单日志
        if($source==1 || $source==2){
            $with[]  = 'logs';
        }
        $info = $model
            ->when(count($where)>0, function ($query)use($where){
                $query->where($where);
            })
            ->field($field)
            ->with($with)
            ->append($append)
            ->hidden($hidden)
            ->find($id);
        if (empty($info)) {
            exception('订单不存在：' . $id);
        }
        $info = $info->toArray();
        $info['status_title'] = MemberOrderModel::getStatus($info['status'], 2);
        if(isset($info['logs'])){
            foreach ($info['logs'] as $k1 => $v1) {
                $info['logs'][$k1]['role_type_title'] = MemberOrderModel::getRoleType($v1['role_type'], 2);
            }
        }
        //支付方式
        if(isset($info['pay_type'])){
            $info['pay_type_title'] = MemberOrderModel::getPayType($info['pay_type'], 2);
        }
        //查看售后情况
        if($source==3 && isset($info['refund_reason_wap_img_ids'])){
            $files = Db::name('file')
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('file_id','in',explode(',',$info['refund_reason_wap_img_ids']))
                ->select()
                ->toArray();
            foreach ($files as $k=>$v){
                $files[$k]['file_url'] = FileSettingService::fileUrl($v);
            }
            $info['refund_reason_wap_imgs'] =$files;
        }
        //支付凭证
        if(isset($info['pay_voucher_imgs'])){
            $files = Db::name('file')
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('file_id','in',explode(',',$info['pay_voucher_imgs']))
                ->select()
                ->toArray();
            foreach ($files as $k=>$v){
                $files[$k]['file_url'] = FileSettingService::fileUrl($v);
            }
            $info['pay_voucher_imgs'] =$files;
        }
        //查询快递信息
        if($source==3){
            $info['delivery_list'] =SettingDeliveryService::list([where_delete()],  0, 0, [],'id as value,title as label,is_disable as disabled');
        }
        return $info;
    }
    /**
     * 订单管理添加
     *
     * @param array $param 订单管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MemberOrderModel();
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
     * 订单管理修改
     *
     * @param int|array $ids   订单管理id
     * @param array     $param 订单管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MemberOrderCache::del($ids);
        return $param;
    }
    /**
     * 订单管理删除
     *
     * @param array $ids  订单管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MemberOrderModel();
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
        MemberOrderCache::del($ids);
        return $update;
    }

    /**
     * @title:查询确认订单
     * @author：易军辉
     * @date：2024/12/13
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public static function getConfirmOrder($param)
    {
        MerchantService::assertMemberMerchantAvailableIfExists($param['member_id'] ?? 0);
        //返回数据
        $info = [
            'delivery_type'=>2,//1、快递配送 2、门店自提
            'take_name'=>'',
            'take_phone'=>'',
            'take_region'=>['','',''],
            'take_address'=>'',
            'self_name'=>'',
            'self_phone'=>'',
            'total_num'=>0,
            'total_price'=>0,
            'pay_type'=>MemberOrderModel::getPayType('voucher'),//支付方式：1、微信支付 2、支付方式
            'pay_voucher_imgs'=>[],//支付凭证图片
        ];
        $model = new MemberOrderModel();
        /*********************查询上一个订单信息*******************************/
        $order_data = $model
            ->where('is_delete',0)
            ->where('is_disable',0)
            ->where('member_id',$param['member_id'])
            ->field('take_name,take_phone,take_region,take_address,self_name,self_phone')
            ->order('id desc')
            ->find();
        if ($order_data) {
            $order_data = $order_data->toArray();
            $takeRegion = trim((string) ($order_data['take_region'] ?? ''));
            $order_data['take_region'] = $takeRegion !== '' ? explode(",", $takeRegion) : ['', '', ''];
            foreach ($order_data as $o_key=>$o_val){
                $info[$o_key] = $o_val;
            }
        }
        /*********************查询商品信息*******************************/
        $goodsModel = new GoodsModel();
        $goods_list = $goodsModel
            ->whereIn('id',explode(',',$param['goods_ids']))
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->with(['image'])
            ->append(['image_url'])
            ->hidden(['image'])
            ->field('id,merchant_id,title,image_id,original_price,price,spec,unit,stock,goods_label_id')
            ->order(['merchant_id'=>'asc','id'=>'desc'])
            ->select()->toArray();
        if(count($goods_list)<=0){
            exception("请选择需要结算的商品");
        }
        $source = trim(strval($param['source'] ?? ''));
        if ($source === 'details') {
            foreach ($goods_list as $goodsItem) {
                GoodsBuyLockService::lockForBuy(intval($goodsItem['id'] ?? 0), intval($param['member_id'] ?? 0));
            }
        }
        $merchant_list = self::buildConfirmMerchantList(array_column($goods_list, 'merchant_id'));
        $settingInfo = \app\common\service\system\SettingService::info('wx_approved,platform_voucher_image_id,platform_voucher_image_url');
        
        // 确保平台收款码 URL 正确生成
        if (empty($settingInfo['platform_voucher_image_url']) && !empty($settingInfo['platform_voucher_image_id'])) {
            $platformFile = FileModel::where('file_id', intval($settingInfo['platform_voucher_image_id']))
                ->append(['file_url'])
                ->find();
            if ($platformFile) {
                $platformFileArr = $platformFile->toArray();
                if (!empty($platformFileArr['file_url'])) {
                    $settingInfo['platform_voucher_image_url'] = $platformFileArr['file_url'];
                } elseif (!empty($platformFileArr['file_path'])) {
                    // 兜底：直接用 file_url 函数生成
                    $settingInfo['platform_voucher_image_url'] = file_url($platformFileArr['file_path']);
                }
            }
        }
        
        // 如果还是空的，尝试从 SettingModel 重新获取
        if (empty($settingInfo['platform_voucher_image_url']) && !empty($settingInfo['platform_voucher_image_id'])) {
            $settingModel = \app\common\model\system\SettingModel::find(1);
            if ($settingModel) {
                $settingModel = $settingModel->append(['platform_voucher_image_url']);
                $settingArr = $settingModel->toArray();
                if (!empty($settingArr['platform_voucher_image_url'])) {
                    $settingInfo['platform_voucher_image_url'] = $settingArr['platform_voucher_image_url'];
                }
            }
        }
        $usePlatformVoucher = false;

        foreach ($merchant_list as $key => $val) {
            $merchant_list[$key]['goods'] = [];
            if (self::shouldUsePlatformVoucher(intval($merchant_list[$key]['id'] ?? 0), $settingInfo)) {
                $merchant_list[$key]['title'] = '平台自营';
                $usePlatformVoucher = true;
            }
            if(!empty($merchant_list[$key]['title'])){
                $merchant_list[$key]['title'] = self::maskDisplayTitle((string) $merchant_list[$key]['title']);
            }
            $merchant_list[$key]['receipt_image_url'] = self::resolveReceiptImageUrl($merchant_list[$key], $settingInfo);
            foreach ($goods_list as $k => $v) {
                if($val['id'] == $v['merchant_id']) {
                    //查询商品标签
                    $v['labels']=[];
                    if(isset($v['goods_label_id'])){
                        $v['labels'] = GoodsLabelModel::whereIn('id',$v['goods_label_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->column('title');
                    }
                    //商品数量
                    $v['cart_num']=1;
                    if(isset($param['source']) && $param['source']=='shop_cart'){
                        $cart_num=MemberShopCartModel::where('member_id',$param['member_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->where('is_pay',0)
                            ->where('goods_id',$v['id'])
                            ->where('merchant_id',$v['merchant_id'])
                            ->value('cart_num');
                        if($cart_num && $cart_num>0){
                            $v['cart_num']=$cart_num;
                        }
                    }
                    $info['total_num'] = bcadd($info['total_num'],$v['cart_num'],0);
                    $info['total_price'] =bcadd($info['total_price'],
                        bcmul($v['cart_num'], $v['price'],2)
                        ,2);
                    array_push($merchant_list[$key]['goods'],$v);
                }
            }
        }
        $receiptImageList = [];
        foreach ($merchant_list as $merchantItem) {
            $receiptImageUrl = trim((string)($merchantItem['receipt_image_url'] ?? ''));
            if ($receiptImageUrl !== '' && !in_array($receiptImageUrl, $receiptImageList, true)) {
                $receiptImageList[] = $receiptImageUrl;
            }
        }
        $info['merchant_list'] = $merchant_list;
        $info['use_platform_voucher'] = $usePlatformVoucher ? 1 : 0;
        $info['platform_voucher_image_url'] = trim((string)($settingInfo['platform_voucher_image_url'] ?? ''));
        $info['receipt_image_list'] = $receiptImageList;
        $info['pay_types'] = MemberOrderModel::PAY_TYPE;
        $info['merchant_purchase_limit'] = MerchantPurchaseLimitService::buildRuntime(
            intval($param['member_id'] ?? 0),
            intval($info['total_num'] ?? 0),
            floatval($info['total_price'] ?? 0)
        );
        return $info;
    }

    /**
     * @title:确认订单
     * @author：易军辉
     * @date：2024/12/14
     * @param $param
     */
    public static function confirmOrder($param){
        MerchantService::assertMemberMerchantAvailableIfExists($param['member_id'] ?? 0);
        $source = trim(strval($param['source'] ?? ''));
        $orderedGoodsIds = [];
        $pendingQuantity = 0;
        $pendingAmount = 0;
        foreach (($param['merchant_list'] ?? []) as $merchantItem) {
            foreach (($merchantItem['goods'] ?? []) as $goodsItem) {
                $quantity = intval($goodsItem['cart_num'] ?? 0);
                $price = floatval($goodsItem['price'] ?? 0);
                $pendingQuantity += $quantity;
                $pendingAmount = round($pendingAmount + ($quantity * $price), 2);
            }
        }
        MerchantPurchaseLimitService::assertWithinLimit(
            intval($param['member_id'] ?? 0),
            $pendingQuantity,
            $pendingAmount
        );
        //组装支付
        $member_setting = MemberSettingService::info('wx_miniapp_appid,wx_miniapp_mch_id,wx_miniapp_mch_key');
        if(!isset($member_setting['wx_miniapp_appid']) || !isset($member_setting['wx_miniapp_mch_id']) || !isset($member_setting['wx_miniapp_mch_key'])){
            exception("请联系客服开启微信支付");
        }
        $model = new MemberOrderModel();
        // 启动事务
        $model::startTrans();
        try {
            $pay_common_on = "";//支付订单号
            $order_nos = $model::generateOrderNumber(count($param['merchant_list']));
            $pay_common_on = $order_nos[0];
            $total_fee = 0;//支付金额
            $total_goods_num=0;//商品数量
            foreach ($param['merchant_list'] as $key=>$val){
                $data = [
                    'create_uid'=>$param['member_id'],//添加用户id
                    'create_time'=>datetime(),//创建时间
                    'order_no'=>$order_nos[$key],//订单号
                    'pay_common_on'=>$pay_common_on,//共同支付订单号
                    'member_id'=>$param['member_id'],//会员id
                    'cart_id'=>isset($param['goods_ids'])?$param['goods_ids']:null,//购物车id
                    'pay_type'=>isset($param['pay_type'])?$param['pay_type']:1,//支付方式
                    'pay_voucher_imgs'=>isset($param['pay_voucher_imgs'])?implode(",",array_column($param['pay_voucher_imgs'],'file_id')):null,
                    'status'=>0,//订单状态：0、待付款 1、待发货 2、待收货 3、待评价 4、完成 5、售后 6、已退款 7、取消
                    'delivery_type'=>$param['delivery_type'],//发货类型：1、快递 2、自提
                    'mark'=>isset($param['mark'])?$param['mark']:null,//会员备注
                    'merchant_id'=>$val['id'],//商家id
                    'take_name'=>isset($param['take_name']) && $param['delivery_type']==1?$param['take_name']:null,//收货人
                    'take_phone'=>isset($param['take_phone']) && $param['delivery_type']==1?$param['take_phone']:null,//收货联系电话
                    'take_region'=>isset($param['take_region']) && $param['delivery_type']==1?implode(",",$param['take_region']):null,//收货地区
                    'take_address'=>isset($param['take_address']) && $param['delivery_type']==1?$param['take_address']:null,//收货详细地址
                    'self_name'=>isset($param['self_name']) && $param['delivery_type']==2?$param['self_name']:null,//自提联系人
                    'self_phone'=>isset($param['self_phone']) && $param['delivery_type']==2?$param['self_phone']:null,//自提预留手机号
                ];
                $order_id = $model::insertGetId($data);
                $order_detailed = [];
                $total_num = 0;
                $total_price = 0;
                foreach ($val['goods'] as $k=>$v){
                    if ($source === 'details') {
                        GoodsBuyLockService::assertCanCheckout(intval($v['id'] ?? 0), intval($param['member_id'] ?? 0));
                    }
                    //判断商品库存是否充足
                    $goods = GoodsModel::where('id', $v['id'])
                        ->where('merchant_id', $val['id'])
                        ->where('is_disable', 0)
                        ->where('is_delete', 0)
                        ->where('status', 1)
                        ->field('id,price,sales_sum,stock')
                        ->find();
                    if(!$goods){
                        exception($v['title']."已下架，请重新下单");
                    }
                    if($goods['stock']<$v['cart_num']){
                        exception($v['title']."库存不足，请重新下单");
                    }
                    $total_num = bcadd($total_num,$v['cart_num'],0);
                    $total_price= bcadd($total_price,bcmul($v['cart_num'],$v['price'],2),2);
                    array_push($order_detailed,[
                        'member_order_id'=>$order_id,//订单表id
                        'goods_id'=>$goods['id'],//商品表id
                        'quantity'=>$v['cart_num'],//购买数量
                        'price'=>$goods['price'],//购买单价
                        'total'=>bcmul($v['cart_num'],$goods['price'],2),//购买总价
                    ]);
                    //修改商品销量
                    $updateRes = GoodsModel::where('id', $v['id'])
                        ->where('merchant_id', $val['id'])
                        ->inc('sales_sum', $v['cart_num']) // 累加销量
                        ->dec('stock', $v['cart_num'])    // 减少库存
                        ->update([
                            'update_time'=>datetime(),
                        ]);
                    if ($updateRes === false) {
                        exception($v['title']."库存更新失败，请重新下单");
                    }
                    $orderedGoodsIds[] = intval($v['id']);
                }
                $detaile = MemberOrderDetailedModel::insertAll($order_detailed);
                $update = $model->where('id',$order_id)->update([
                    'total_num'=>$total_num,
                    'total_price'=>$total_price,
                    'pick_up_code'=>$data['delivery_type']==2?$order_id.rand(10, 99):null
                ]);
                $total_fee = bcadd($total_fee,$total_price,2);
                $total_goods_num=bcadd($total_goods_num,$total_num,0);
                //清除购物车
                if(isset($param['source']) && $param['source']=='shop_cart' && isset($param['goods_ids'])){
                    $shop_cart_res = MemberShopCartModel::where('member_id',$param['member_id'])
                        ->where('is_delete',0)
                        ->where('is_disable',0)
                        ->where('is_pay',0)
                        ->whereIn('goods_id',$param['goods_ids'])
                        ->update([
                            'is_delete'=>1,
                            'delete_time'=>datetime(),
                            'delete_uid'=>$param['member_id']
                        ]);
                }
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'订单生成',
                    'member_order_id'=>$order_id,
                    'role_type'=>3
                ]);
            }
            //支付方式处理
            switch ($data['pay_type']){
                case 1:
                    /*************************************组装微信支付************************************/
                    $log_level = Config::get('app.app_debug') ? 'debug' : 'error';
                    $config = [
                        'app_id' => $member_setting['wx_miniapp_appid'],
                        'mch_id' => $member_setting['wx_miniapp_mch_id'],
                        'key'    => $member_setting['wx_miniapp_mch_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                        'response_type' => 'array',
                        'log' => [
                            'level' => $log_level,
                            'file' => runtime_path() . '/easywechat/' . date('Ym') . '/miniProgram.log',
                        ],
                    ];
                    $app = Factory::payment($config);
                    $result = $app->order->unify([
                        'body' => '购买'.$total_goods_num."件商品",
                        'out_trade_no' =>$pay_common_on,
                        'total_fee' => $total_fee*100,
                        'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/member.MemberOrder/orderNotify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                        'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                        'openid' => ThirdModel::where('member_id',$param['member_id'])->value('openid'),
                    ]);
                    if ($result['return_code']  === 'SUCCESS' && $result['return_msg'] === 'OK') {
                        // 提交事务
                        $model::commit();
                        GoodsBuyLockService::releaseByMember($orderedGoodsIds, intval($param['member_id'] ?? 0));
                        // 小程序处理
                        return $app->jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                    }else{
                        exception($result['return_msg']);
                    }
                    break;
                case 2:
                    /*************************************凭证支付************************************/
                    break;
            }
            // 提交事务
            $model::commit();
            GoodsBuyLockService::releaseByMember($orderedGoodsIds, intval($param['member_id'] ?? 0));
            return true;
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 回滚事务
            $model::rollback();
        }
        if (isset($errmsg)) {
            exception($errmsg);
        }
        return false;
    }

    /**
     * @title:查询参数
     * @author：易军辉
     * @date：2025/2/12
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return array
     */
    public static function getParams($source=1)
    {
        $order_status = MemberOrderModel::STATUS;
        $pay_types = MemberOrderModel::PAY_TYPE;
        $delivery_list = [];
        if($source==3){
            array_unshift($order_status, ['value' => -1, 'label' => '全部', 'code' => 'all']);
        }
        if($source==1){
            $delivery_list =SettingDeliveryService::list([where_delete()],  0, 0, [],'id as value,title as label,is_disable as disabled');
        }
        return compact('order_status','delivery_list','pay_types');
    }

    /**
     * @title:查询订单核销参数
     * @author：易军辉
     * @date：2025/2/12
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return array
     */
    public static function getMerParams($source=1)
    {
        $order_status = [
            ['value' => -1, 'label' => '全部', 'code' => 'all'],
            ['value' => 0, 'label' => '待核销','code' => 'p_pay'],
            ['value' => 4, 'label' => '完成','code' => 'success'],
        ];
        return compact('order_status');
    }

    /**
     * @title:订单发货
     * @author：易军辉
     * @date：2025/2/14
     * @param $ids
     * @param $param
     * @return array|mixed
     * @throws \think\Exception
     */
    public static function delivery($ids, $param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->with(['detaileds'])
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',1)
                ->select();
            $delivery_obj = SettingDeliveryModel::query()->where('id',$param['setting_delivery_id'])->find();
            foreach ($list as $key=>$val) {
                //出库
                $inventorys = [];
                foreach ($val['detaileds'] as $k=>$v) {
                    $warehousing_num = GoodsInventoryModel::query()
                        ->where('goods_id',$v['goods_id'])
                        ->where('is_disable',0)
                        ->where('is_delete',0)
                        ->sum('warehousing_num');
                    $merchant_id = GoodsModel::query()->where('id',$v['goods_id'])->value('merchant_id');
                    if($warehousing_num>=$v['quantity']){
                        $last_warehousing = GoodsInventoryModel::query()
                            ->where('goods_id',$v['goods_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->order(['create_time'=>'desc'])
                            ->field('id,setting_warehouse_id,setting_hall_id')
                            ->find();
                        array_push($inventorys,[
                            'merchant_id'=>$merchant_id,//商家id
                            'goods_id'=>$v['goods_id'],//商品id
                            'warehousing_num'=>-$v['quantity'],//出入库数量
                            'setting_warehouse_id'=>isset($last_warehousing['setting_warehouse_id'])?$last_warehousing['setting_warehouse_id']:null,//仓库id
                            'setting_hall_id'=>isset($last_warehousing['setting_hall_id'])?$last_warehousing['setting_hall_id']:null,//大厅id
                            'inventory_type'=>2,//出入库类型：1、入库 2、出库
                            'member_id'=>$val['member_id'],//购买用户
                            'member_order_id'=>$val['id'],//订单id
                            'create_time'=>datetime(),
                            'create_uid'=>operate_user_id()
                        ]);
                    }
                }
                if(count($inventorys)>0){
                    $inventory_add = GoodsInventoryModel::insertAll($inventorys);
                }
                $val->status = MemberOrderModel::getStatus('p_receipt',1);
                $val->kuaidi_order_no = $param['kuaidi_order_no'];
                $val->setting_delivery_id= $param['setting_delivery_id'];
                $val->delivery_name= $delivery_obj['title'];
                $val->delivery_code= $delivery_obj['code'];
                $val->delivery_time = datetime();
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'订单发货',
                    'member_order_id'=>$val['id'],
                    'role_type'=>1
                ]);
                $val->save();
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:查询物流信息
     * @author：易军辉
     * @date：2025/2/14
     * @param $id
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     */
    public static function logistics($id,$source=1){
        $model = new MemberOrderModel();
        $info = $model->find($id);
        if(!isset($info['delivery_code']) || !isset($info['kuaidi_order_no'])){
            exception("未获取到发货信息");
        }
        $param =array (
            'com' =>$info['delivery_code'],             // 快递公司编码
            'num' =>$info['kuaidi_order_no'],     // 快递单号
            'phone' => '',                // 手机号
            'from' => '',                 // 出发地城市
            'to' => '',                   // 目的地城市
            'resultv2' => '1',            // 开启行政区域解析
            'show' => '0',                // 返回格式：0：json格式（默认），1：xml，2：html，3：text
            'order' => 'desc'             // 返回结果排序:desc降序（默认）,asc 升序
        );
        //请求参数
        $body = array();
        $body['customer'] = '72222E10EA93C797BE22A395B9D1485A';
        $body['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $key="MnmlAfXt5520";
        $sign = md5($body['param'].$key.$body['customer']);
        $body['sign'] = strtoupper($sign);
        $url = "https://poll.kuaidi100.com/poll/query.do";
        // 发送post请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        $result = [];
        // 第二个参数为true，表示格式化输出json
        $data = json_decode($res, true);
        $result['delivery_name']=$info['delivery_name'];
        $result['kuaidi_order_no']=$info['kuaidi_order_no'];
        $result['delivery_time']=$info['delivery_time'];
        if(isset($data['data'])){
            if($source==3){
                $result['list']=$data['data'];
            }else{
                $result =$data['data'];
            }
        }else{
            exception("系统繁忙，请稍后再试");
        }
        return $result;
    }

    /**
     * @title:取消订单
     * @author：易军辉
     * @date：2025/2/15
     * @param $order_id 订单id
     * @param $param 参数
     */
    public static function cancelOrder($ids,$param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->with(['detaileds'])
                ->when((isset($param['member_id']) && $param['member_id']>0), function ($query) use ($param) {
                    $query->where('member_id', $param['member_id']);
                })
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',0)
                ->select();
            foreach ($list as $key=>$val) {
                //增加库存
                foreach ($val['detaileds'] as $k=>$v) {
                    $goods  = GoodsModel::query()->where('id',$v['goods_id'])->find();
                    if($goods){
                        $goods_res = GoodsModel::query()->where('id',$goods['id'])->update(['stock'=>bcadd($goods['stock'],$v['quantity'])]);
                    }
                }
                $val->is_delete = 1;
                $val->delete_uid = operate_user_id();
                $val->delete_time = datetime();
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'买家取消订单',
                    'member_order_id'=>$val['id'],
                    'role_type'=>3
                ]);
                $val->save();
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:订单支付
     * @author：易军辉
     * @date：2025/2/15
     * @param $id
     * @param $param
     */
    public static function payOrder($id,$param = [])
    {
        MerchantService::assertMemberMerchantAvailableIfExists($param['member_id'] ?? 0);
        //组装支付
        $member_setting = MemberSettingService::info('wx_miniapp_appid,wx_miniapp_mch_id,wx_miniapp_mch_key');
        if(!isset($member_setting['wx_miniapp_appid']) || !isset($member_setting['wx_miniapp_mch_id']) || !isset($member_setting['wx_miniapp_mch_key'])){
            exception("请联系客服开启微信支付");
        }
        $model = new MemberOrderModel();
        // 启动事务
        $model::startTrans();
        try {
            /*************************************组装微信支付************************************/
            $log_level = Config::get('app.app_debug') ? 'debug' : 'error';
            $config = [
                'app_id' => $member_setting['wx_miniapp_appid'],
                'mch_id' => $member_setting['wx_miniapp_mch_id'],
                'key'    => $member_setting['wx_miniapp_mch_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                'response_type' => 'array',
                'log' => [
                    'level' => $log_level,
                    'file' => runtime_path() . '/easywechat/' . date('Ym') . '/miniProgram.log',
                ],
            ];
            $app = Factory::payment($config);
            $order = $model
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',0)
                ->find($id);
            if(!$order){
                exception("支付订单不存在");
            }
            //重新生成支付订单号
            $order_nos = $model::generateOrderNumber(1);
            $pay_common_on = $order_nos[0];
            $update_res = $model->where('id',$order['id'])->update(['pay_common_on'=>$pay_common_on]);
            $result = $app->order->unify([
                'body' => '购买'.$order['total_num']."件商品",
                'out_trade_no' =>$pay_common_on,
                'total_fee' => $order['total_price']*100,
                'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/member.MemberOrder/orderNotify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                'openid' => ThirdModel::where('member_id',$order['member_id'])->value('openid'),
            ]);
            if ($result['return_code']  === 'SUCCESS' && $result['return_msg'] === 'OK') {
                $model::commit();
                // 小程序处理
                return $app->jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
            }else{
                exception($result['return_msg']);
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
        return true;
    }

    /**
     * @title:查看取件码
     * @author：易军辉
     * @date：2025/2/15
     * @param $id
     */
    public static function getOrderCode($id)
    {
        $model = new MemberOrderModel();
        return $model->where('id',$id)->value('pick_up_code');
    }

    /**
     * @title:提货
     * @author：易军辉
     * @date：2025/2/15
     * @param $ids
     * @param $param
     * @return true
     * @throws \think\Exception
     */
    public static function takeDelivery($ids, $param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->with(['detaileds'])
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',1)
                ->select();
            foreach ($list as $key=>$val) {
                if($val['pick_up_code']!=$param['pick_up_code']){
                    exception('提货码错误');
                }
                //出库
                $inventorys = [];
                foreach ($val['detaileds'] as $k=>$v) {
                    $warehousing_num = GoodsInventoryModel::query()
                        ->where('goods_id',$v['goods_id'])
                        ->where('is_disable',0)
                        ->where('is_delete',0)
                        ->sum('warehousing_num');
                    $merchant_id = GoodsModel::query()->where('id',$v['goods_id'])->value('merchant_id');
                    if($warehousing_num>=$v['quantity']){
                        $last_warehousing = GoodsInventoryModel::query()
                            ->where('goods_id',$v['goods_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->order(['create_time'=>'desc'])
                            ->field('id,setting_warehouse_id,setting_hall_id')
                            ->find();
                        array_push($inventorys,[
                            'merchant_id'=>$merchant_id,//商家id
                            'goods_id'=>$v['goods_id'],//商品id
                            'warehousing_num'=>-$v['quantity'],//出入库数量
                            'setting_warehouse_id'=>isset($last_warehousing['setting_warehouse_id'])?$last_warehousing['setting_warehouse_id']:null,//仓库id
                            'setting_hall_id'=>isset($last_warehousing['setting_hall_id'])?$last_warehousing['setting_hall_id']:null,//大厅id
                            'inventory_type'=>2,//出入库类型：1、入库 2、出库
                            'member_id'=>$val['member_id'],//购买用户
                            'member_order_id'=>$val['id'],//订单id
                            'create_time'=>datetime(),
                            'create_uid'=>operate_user_id()
                        ]);
                    }
                }
                if(count($inventorys)>0){
                    $inventory_add = GoodsInventoryModel::insertAll($inventorys);
                }
                $val->status = MemberOrderModel::getStatus('p_evaluate',1);
                $val->delivery_time = datetime();
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'用户已提货',
                    'member_order_id'=>$val['id'],
                    'role_type'=>1
                ]);
                $val->save();
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:确认收货
     * @author：易军辉
     * @date：2025/2/16
     * @param $ids
     * @param $param
     * @return true
     * @throws \think\Exception
     */
    public static function confirmReceipt($ids,$param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->when((isset($param['member_id']) && $param['member_id']>0), function ($query) use ($param) {
                    $query->where('member_id', $param['member_id']);
                })
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',2)
                ->select();
            foreach ($list as $key=>$val) {
                $val->status = MemberOrderModel::getStatus('p_evaluate',1);
                $val->receipt_time = datetime();
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'买家已收货',
                    'member_order_id'=>$val['id'],
                    'role_type'=>3
                ]);
                $val->save();
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:订单评论
     * @author：易军辉
     * @date：2025/2/16
     * @param $ids
     * @param $param
     * @return true
     * @throws \think\Exception
     */
    public static function submitEvaluation($ids,$param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->with([
                    'detaileds'=>function($query){
                        $query->field('id,member_order_id,goods_id,quantity,price,total');
                    },
                    'detaileds.goods',
                ])
                ->when((isset($param['member_id']) && $param['member_id']>0), function ($query) use ($param) {
                    $query->where('member_id', $param['member_id']);
                })
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',3)
                ->select();
            foreach ($list as $key=>$val) {
                //写入评论
                $detailed_res = MemberOrderDetailedModel::query()
                    ->where('member_order_id',$val['id'])
                    ->update([
                        'evaluate_content'=>$param['evaluate_content'],
                        'evaluate_num'=>$param['evaluate_num'],
                    ]);
                $val->status = MemberOrderModel::getStatus('success',1);
                $val->success_time = datetime();
                //订单完成时，给商家入明细
                if(isset($val['detaileds']) && $val['pay_type']!=MemberOrderModel::getPayType('voucher',1)){
                    foreach ($val['detaileds'] as $k1 => $v1) {
                        if(isset($v1['goods']['merchant_id'])){
                            $mer_recharge = MerchantService::recharge(
                                $v1['goods']['merchant_id'],
                                $v1['total'],
                                "用户购买".$v1['quantity']."件【".$v1['goods']['title']."】",
                                $v1['id'],
                            );
                        }
                    }
                }
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'买家完成评论',
                    'member_order_id'=>$val['id'],
                    'role_type'=>3
                ]);
                $val->save();
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:申请售后
     * @author：易军辉
     * @date：2025/2/16
     * @param $ids
     * @param $param
     * @return true
     * @throws \think\Exception
     */
    public static function submitService($ids,$param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        $refundType = isset($param['refund_type']) ? intval($param['refund_type']) : 2;
        $refundPrice = isset($param['refund_price']) ? $param['refund_price'] : null;
        $refundExplain = isset($param['refund_reason_wap_explain']) ? $param['refund_reason_wap_explain'] : null;
        $refundImageIds = isset($param['refund_reason_wap_imgs']) ? implode(",",array_column($param['refund_reason_wap_imgs'],'file_id')) : null;
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->when((isset($param['member_id']) && $param['member_id']>0), function ($query) use ($param) {
                    $query->where('member_id', $param['member_id']);
                })
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',4)
                ->select();
            if ($list->isEmpty()) {
                exception('订单不存在，或当前状态不支持申请售后');
            }
            $matchedIds = [];
            foreach ($list as $key=>$val) {
                $matchedIds[] = intval($val[$pk]);
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'买家提交申请售后',
                    'member_order_id'=>$val['id'],
                    'role_type'=>3
                ]);
            }
            $saveData = [
                'refund_status' => 1,
                'refund_type' => $refundType,
                'refund_price' => $refundPrice,
                'refund_reason_wap_explain' => $refundExplain,
                'refund_reason_wap_img_ids' => $refundImageIds,
                'status' => MemberOrderModel::getStatus('service',1),
                'update_time' => datetime(),
            ];
            $model->whereIn($pk, $matchedIds)->update($saveData);
            foreach ($matchedIds as $orderId) {
                MemberOrderCache::del($orderId);
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
        MemberOrderCache::del($ids);
        return true;
    }
    /**
     * @title:售后处理
     * @author：易军辉
     * @date：2025/2/16
     * @param $ids
     * @param $param
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return true
     * @throws \think\Exception
     */
    public static function serviceOrder($id,$param = [],$source=1)
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $info = $model
                ->with([
                    'detaileds'=>function($query){
                        $query->field('id,member_order_id,goods_id,quantity,price,total');
                    },
                    'detaileds.goods',
                ])
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',MemberOrderModel::getStatus('service',1))
                ->find($id);
            if(!$info){
                exception("售后订单不存在");
            }
            $save_data = [];
            $log_title = "";
            $is_refund = false;
            switch ($param['refund_status']) {
                case 2://同意售后
                    if($param['refund_type']==1){//退款
                        $log_title="操作员同意退款";
                        $is_refund=true;
                    }else if($param['refund_type']==2){//同意退货
                        $log_title="操作员同意退货";
                        $save_data['refund_consignee'] = $param['refund_consignee'];
                        $save_data['refund_phone'] = $param['refund_phone'];
                        $save_data['refund_address'] = $param['refund_address'];
                    }
                    break;
                case 3://拒绝售后
                    $log_title="操作员拒绝售后";
                    $save_data['refund_reason'] = $param['refund_reason'];
                    break;
                case 4://同意退款
                    $log_title="操作员同意退货退款";
                    $is_refund=true;
                    break;
                default:
                    exception("请选择售后状态");
                    break;
            }
            $save_data['refund_status'] =$param['refund_status'];
            if(isset($param['refund_price'])){
                $save_data['refund_price'] =$param['refund_price'];
            }else{
                $save_data['refund_price'] =$info['refund_price'];
            }
            $pay_amount = isset($info['pay_price']) && floatval($info['pay_price']) > 0
                ? floatval($info['pay_price'])
                : floatval($info['total_price'] ?? 0);
            if (floatval($save_data['refund_price']) <= 0) {
                exception("退款金额必须大于0");
            }
            if ($pay_amount > 0 && floatval($save_data['refund_price']) > $pay_amount) {
                exception("退款金额不能大于实际支付金额");
            }
            $save_data['update_time'] =datetime();
            $save_data['update_uid'] =operate_user_id();
            //同意退款
            if($is_refund){
                $refund_order_nos = $model::generateOrderNumber(1);
                $out_refund_no = $refund_order_nos[0];
                $pay_type = intval($info['pay_type'] ?? 0);
                if ($pay_type === MemberOrderModel::getPayType('voucher',1)) {
                    $log_title .="，退款金额：".$save_data['refund_price']."，退款单号：".$out_refund_no."（凭证支付人工退款）";
                    $save_data['status'] =MemberOrderModel::getStatus('refund',1);
                    $save_data['refund_time'] =datetime();
                    $save_data['out_refund_no'] =$out_refund_no;
                    MemberBillService::add([
                        'member_id'=>$info['member_id'],
                        'title'=>'售后退款',
                        'in_out'=>1,
                        'money'=>$save_data['refund_price'],
                        'bill_type_id'=>4,
                        'order_id'=>$info['id'],
                        'remark'=>'凭证支付售后退款'
                    ]);
                } else {
                    //组装支付
                    $member_setting = MemberSettingService::info('wx_miniapp_appid,wx_miniapp_mch_id,wx_miniapp_mch_key');
                    if(!isset($member_setting['wx_miniapp_appid']) || !isset($member_setting['wx_miniapp_mch_id']) || !isset($member_setting['wx_miniapp_mch_key'])){
                        exception("请联系客服开启微信支付");
                    }
                    /*************************************组装微信支付************************************/
                    $log_level = Config::get('app.app_debug') ? 'debug' : 'error';
                    $config = [
                        'app_id' => $member_setting['wx_miniapp_appid'],
                        'mch_id' => $member_setting['wx_miniapp_mch_id'],
                        'key'    => $member_setting['wx_miniapp_mch_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
                        'cert_path' => config_path('cert/').'apiclient_cert.pem', // 使用助手函数
                        'key_path' => config_path('cert/').'apiclient_key.pem',
                        'response_type' => 'array',
                        'log' => [
                            'level' => $log_level,
                            'file' => runtime_path() . '/easywechat/' . date('Ym') . '/miniProgram.log',
                        ],
                    ];
                    $app = Factory::payment($config);
                    $response = $app->refund->byOutTradeNumber($info['pay_common_on'], $out_refund_no, ($pay_amount*100), ($save_data['refund_price']*100), [
                        'refund_desc' => '用户申请退款', // 可选
                    ]);
                    if ($response['return_code'] === 'SUCCESS' && $response['result_code'] === 'SUCCESS') {
                        $log_title .="，退款金额：".$save_data['refund_price']."，退款单号：".$out_refund_no;
                        // 退款成功
                        $save_data['status'] =MemberOrderModel::getStatus('refund',1);
                        $save_data['refund_time'] =datetime();
                        $save_data['out_refund_no'] =$out_refund_no;
                    } else {
                        exception($response['err_code_des']);
                    }
                }
                //订单退款时，扣除商家
                if(isset($info['detaileds'])){
                    $refund_total = round(floatval($save_data['refund_price']), 2);
                    $detail_amount_total = 0;
                    foreach ($info['detaileds'] as $detail_item) {
                        $detail_amount_total += round(floatval($detail_item['total'] ?? 0), 2);
                    }
                    $detail_count = count($info['detaileds']);
                    $allocated_refund = 0;
                    foreach ($info['detaileds'] as $k1 => $v1) {
                        if(isset($v1['goods']['merchant_id'])){
                            if ($detail_count <= 1) {
                                $detail_refund = $refund_total;
                            } elseif ($k1 === $detail_count - 1) {
                                $detail_refund = round($refund_total - $allocated_refund, 2);
                            } elseif ($detail_amount_total > 0) {
                                $detail_refund = round($refund_total * (floatval($v1['total'] ?? 0) / $detail_amount_total), 2);
                            } else {
                                $detail_refund = round($refund_total / $detail_count, 2);
                            }
                            $allocated_refund += $detail_refund;
                            $mer_recharge = MerchantService::consumption(
                                $detail_refund,
                                "用户申请退款".$v1['quantity']."件【".$v1['goods']['title']."】",
                                $v1['id'],
                                "",
                                $v1['goods']['merchant_id'],
                                false
                            );
                        }
                    }
                }
            }
            //订单日志
            $log = MemberOrderLogService::add([
                'title'=>$log_title,
                'member_order_id'=>$info['id'],
                'role_type'=>$source
            ]);
            $save_res = $model->where('id',$info['id'])->update($save_data);
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
        MemberOrderCache::del($id);
        return true;
    }

    /**
     * @title:退货
     * @author：易军辉
     * @date：2025/2/16
     * @param $ids
     * @param $param
     * @return true
     * @throws \think\Exception
     */
    public static function returnGoods($ids,$param = [])
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        // 启动事务
        $model::startTrans();
        try {
            $list = $model->whereIn($pk, $ids)
                ->when((isset($param['member_id']) && $param['member_id']>0), function ($query) use ($param) {
                    $query->where('member_id', $param['member_id']);
                })
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('status',5)
                ->where('refund_type',2)
                ->where('refund_status',2)
                ->select();
            if(count($list)<=0){
                exception("未查询到退货订单");
            }
            $delivery_obj = SettingDeliveryModel::query()->where('id',$param['refund_delivery_id'])->find();
            if(!$delivery_obj){
                exception("快递公司不存在");
            }
            $matchedIds = [];
            foreach ($list as $key=>$val) {
                $matchedIds[] = intval($val[$pk]);
                //订单日志
                $log = MemberOrderLogService::add([
                    'title'=>'买家已发货',
                    'member_order_id'=>$val['id'],
                    'role_type'=>3
                ]);
            }
            $saveData = [
                'refund_delivery_id' => $param['refund_delivery_id'],
                'refund_express' => $param['refund_express'],
                'refund_express_name' => $delivery_obj['title'],
                'refund_express_code' => $delivery_obj['code'],
                'update_time' => datetime(),
            ];
            $model->whereIn($pk, $matchedIds)->update($saveData);
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
        MemberOrderCache::del($ids);
        return true;
    }

    /**
     * @title:查询交易信息
     * @author：易军辉
     * @date：2025/2/17
     */
    public static function getOrderTransaction($type=1)
    {
        $model = new MemberOrderModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,order_no,member_id,cart_id,freight_price,total_num,total_price,pay_price,pay_status,pay_time,pay_type,status,refund_status,refund_type,refund_express,refund_express_name,refund_reason_wap_img_ids,refund_reason_wap_explain,refund_reason_time,refund_reason_wap,refund_reason,refund_price,delivery_name,delivery_code,delivery_type,kuaidi_order_no,mark,remark,merchant_id';
        }
        $order = [$pk => 'desc'];
        $with     = [];
        $append   = [];
        $hidden   = [];
        //关联详情
        if(strpos($field, 'id') !== false){
            $with = [
                'detaileds'=>function($query){
                    $query->field('id,member_order_id,goods_id,quantity,price,total');
                },
                'detaileds.goods',
                'detaileds.goods.image',
                'member',
                'member.avatar'
            ];
        }
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where('status','>',0)->where('is_delete',0)->where('is_disable',0)->page(1)->limit(100)->order($order)->select()->toArray();
        $data = [];
        foreach ($list as $key=>$val) {
            $img = "";
            $text = "";
            if(isset($val['member']['avatar']['file_url'])){
                $img = $val['member']['avatar']['file_url'];
            }
            if(isset($val['member']['nickname'])){
                $text .=self::hideMiddle($val['member']['nickname']);
            }
            $text .="购买了".$val['total_num']."件商品，";
            foreach ($val['detaileds'] as $k=>$v) {
                if(isset($v['goods']['title'])){
                    $text.=$v['goods']['title'];
                }
            }
            if($type==2){
                array_push($data,$text);
            }else{
                array_push($data,[
                    'text'=>$text,
                    'img'=>$img,
                ]);
            }
        }
        return $data;
    }
    private static function hideMiddle($str)
    {
        $length = mb_strlen($str); // 获取字符串长度，支持多字节字符

        // 小于3个字不需要隐藏
        if ($length < 3) {
            return $str;
        }

        // 如果字符串长度为3，只隐藏中间一个字
        if ($length == 3) {
            return mb_substr($str, 0, 1) . '*' . mb_substr($str, 2, 1);
        }

        // 对于长度大于3的情况，将中间3个字符替换成***
        $start = mb_substr($str, 0, floor(($length - 3) / 2)); // 前半部分
        $end = mb_substr($str, -ceil(($length - 3) / 2)); // 后半部分

        return $start . '***' . $end;
    }

    /**
     * @title:支付审核
     * @author：易军辉
     * @date：2025/6/29
     * @param $ids 订单号
     * @param $param 参数
     * @return array|mixed
     * @throws \think\Exception
     */
    public static function orderPayAuth($ids,$param = [])
    {
        $model = new MemberOrderModel();
        // 启动事务
        $model::startTrans();
        try {
            $orderList = $model->whereIn('id',$ids)
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('pay_status',0)
                ->where('pay_type',MemberOrderModel::getPayType('voucher',1))
                ->when(isset($param['merchant_id']) && $param['merchant_id'],function ($query) use ($param) {
                    $query->where('merchant_id',$param['merchant_id']);
                })
                ->select();
            if(count($orderList)<=0){
                exception("未查询到符合条件的订单");
            }
            foreach ($orderList as $key=>$val) {
                switch ($param['pay_status']) {
                    case 1://支付成功
                        //商品转移操作
                        $payTime = datetime();
                        $orderPayPrice = count($orderList) > 1
                            ? floatval($val['total_price'] ?? 0)
                            : floatval($param['pay_price'] ?? $val['total_price'] ?? 0);
                        $merchant_id = MerchantModel::where('member_id',$val['member_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->where('auth_state',1)
                            ->value('id');
                        if($merchant_id){
                            $ledgerOrder = $val->toArray();
                            $ledgerOrder['pay_price'] = $orderPayPrice;
                            MerchantPurchaseLedgerService::recordOrder($ledgerOrder, intval($merchant_id), $payTime);
                            $goods = MemberOrderDetailedModel::leftjoin('ya_goods', 'ya_goods.id=ya_member_order_detailed.goods_id')
                                ->where('ya_member_order_detailed.member_order_id',$val['id'])
                                ->field('ya_goods.*,ya_member_order_detailed.quantity')
                                ->select()
                                ->toArray();
                            foreach ($goods as $k=>$v) {
                                $goods[$k]['merchant_id']=$merchant_id;
                                $goods[$k]['status']=1;
                                $goods[$k]['sales_sum']=0;
                                $goods[$k]['click_count']=0;
                                $goods[$k]['stock']=$v['quantity'];
                                unset($goods[$k]['quantity']);
                                unset($goods[$k]['id']);
                                $add_goods_id = GoodsModel::insertGetId($goods[$k]);
                                //商品组图
                                $goods_images = GoodsImagesModel::where('goods_id',$v['id'])->column('image_id');
                                $add_goods_images = [];
                                if(count($goods_images)>0){
                                    $merchant_files = Db::name('merchant_file')->whereIn('file_id',$goods_images)->select()->toArray();
                                    foreach ($merchant_files as $fk=>$fv) {
                                        $merchant_files[$fk]['mer_id'] = $merchant_id;
                                        unset($merchant_files[$fk]['file_id']);
                                        $image_id = MerchantFileModel::insertGetId($merchant_files[$fk]);
                                        array_push($add_goods_images,[
                                            'image_id'=>$image_id,
                                            'goods_id'=>$add_goods_id,
                                        ]);
                                    }
                                }
                                if(count($add_goods_images)){
                                    $add_imgs_res = GoodsImagesModel::insertAll($add_goods_images);
                                }
                            }
                        }
                        //更改订单状态 - 核销后变为完成状态
                        $edit_res = $model->where('id',$val['id'])->update([
                            'pay_time'=>$payTime,
                            'pay_status'=>1,
                            'pay_price'=>$orderPayPrice,
                            'status'=>MemberOrderModel::getStatus('success',1), // 核销后变为完成
                        ]);
                        //支付账单
                        $bill_data = array(
                            'member_id'=>$val['member_id'],
                            'title'=>'购买商品',
                            'in_out'=>2,
                            'money'=>$orderPayPrice,
                            'bill_type_id'=>4,//凭证支付
                            'order_id'=>$val['id'],
                        );
                        MemberBillService::add($bill_data);
                        //订单日志
                        $log = MemberOrderLogService::add([
                            'title'=>'凭证支付订单成功',
                            'member_order_id'=>$val['id'],
                            'role_type'=>3,
                            'create_uid'=>$val['member_id']
                        ]);
                        break;
                    case 0: // 驳回
                        $edit_res = $model->where('id',$val['id'])->update([
                            'update_time'=>datetime(),
                            'update_uid'=>operate_user_id(),
                            'pay_auth_msg'=>$param['pay_auth_msg'],
                            'pay_status'=>2, // 支付状态：2、拒绝
                            'status'=>MemberOrderModel::getStatus('cancel'), // 订单状态：7、取消
                        ]);
                        //订单日志
                        $log = MemberOrderLogService::add([
                            'title'=>'凭证支付订单驳回：'.$param['pay_auth_msg'],
                            'member_order_id'=>$val['id'],
                            'role_type'=>3,
                            'create_uid'=>operate_user_id()
                        ]);
                        break;
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
        return $param;
    }
}
