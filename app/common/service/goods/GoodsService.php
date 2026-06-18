<?php
namespace app\common\service\goods;
use app\common\model\goods\GoodsLabelModel;
use app\common\model\goods\GoodsModel;
use app\common\cache\goods\GoodsCache;
use app\common\model\goods\GoodsTypeModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\member\SettingService;
use app\common\service\system\SettingService as SystemSettingService;
use think\facade\Db;

/**
 * 商品
 */
class GoodsService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'code' => '',
        'is_disable' => '',
        'merchant_id' => '',
        'goods_type_id' => '',
        'goods_label_id' => '',
        'image_id' => '',
        'original_price' => '',
        'price' => '',
        'status' => '',
        'remark' => '',
        'content' => '',
        'sort' => '',
        'sales_sum' => '',
        'click_count' => '',
        'spec' => '',
        'unit' => '',
        'stock' => '',
        'video_id' => '',
        'auth_uid' => '',
        'auth_time' => null,
        'auth_msg' => '',
        'images/a'       => [],
        'source/d' => 0,
    ];
    /**
     * 商品列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * @param string $goods_label_ids 商品标签
     * @param string $source 来源：1、总后端、2、商家端 3、移动端
     *
     * @return array
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '',$goods_label_ids=[],$source=1)
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,code,is_disable,create_time,update_time,merchant_id,goods_type_id,goods_label_id,image_id,original_price,price,status,remark,content,sort,sales_sum,click_count,spec,unit,stock,video_id,auth_uid,auth_time,auth_msg,source,member_id';
        }
        if (empty($order)) {
            $order = ['merchant_id'=>'desc','status'=>'asc','sort'=>'asc',$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];

        if (strpos($field, 'goods_type_id') !== false) {
            $with[]   = $hidden[]   = 'type';
            $append[] = 'type_title';
        }
        if (strpos($field, 'merchant_id') !== false) {
            $with[]   = $hidden[]   = 'merchant';
            $append[] = 'merchant_title';
        }
        if (strpos($field, 'member_id') !== false) {
            $with[]   = $hidden[]   = 'member';
            $append[] = 'member_title';
            $append[] = 'member_phone';
        }
        if (strpos($field, 'image_id') !== false) {
            $with[]   = $hidden[]   = 'image';
            $append[] = 'image_url';
        }
        if (strpos($field, 'images') !== false) {
            $with[]   = $hidden[]   = 'files';
            $append[] =  'images';
        } elseif (strpos($field, 'image_urls') !== false) {
            $with[]   = $hidden[]   = 'files';
            $append[] =  'image_urls';
        }
        if ($page == 0 || $limit == 0) {
            return $model->when(count($goods_label_ids)>0,function ($query)use($goods_label_ids){
                foreach ($goods_label_ids as $id) {
                    $query->whereOrRaw("FIND_IN_SET($id, goods_label_id)");
                }
            })->field($field)->with($with)->append($append)->hidden($hidden)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->when(count($goods_label_ids)>0,function ($query)use($goods_label_ids){
            foreach ($goods_label_ids as $id) {
                $query->whereOrRaw("FIND_IN_SET($id, goods_label_id)");
            }
        })->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->when(count($goods_label_ids)>0,function ($query)use($goods_label_ids){
            foreach ($goods_label_ids as $id) {
                $query->whereOrRaw("FIND_IN_SET($id, goods_label_id)");
            }
        })->field($field)->with($with)->append($append)->hidden($hidden)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $key=>$val){
            //状态名称
            if (strpos($field, 'status') !== false){
                $list[$key]['status_title'] = GoodsModel::getStatus($val['status'],2);
            }
            //查询标签名称
            if (strpos($field, 'goods_label_id') !== false && $val['goods_label_id']) {
                $labels = GoodsLabelModel::whereIn('id',$val['goods_label_id'])
                    ->where('is_disable',0)
                    ->where('is_delete',0)
                    ->column('title');
                $list[$key]['label_title'] = implode('、', $labels);
            }else{
                $list[$key]['label_title'] ="";
            }
            //线下商品
            if (strpos($field, 'source') !== false && $val['source']==1 && strpos($field, 'member_id') !== false){
                $list[$key]['merchant_title'] =self::hideMiddle($val['member_title']);
            }
            //标签名称
            if($source==3 && $val['source']==1 && isset($val['is_transaction']) && $val['is_transaction']==1){
                $list[$key]['label_title'] = $list[$key]['label_title']
                    ? $list[$key]['label_title'] . '、已成交'
                    : '已成交';
            }
        }
        //统计数据
        $statistics = [];
        switch ($source){
            case 1:
                $statistics = self::getStatusNum($source);
                break;
                case 3:
                    $statistics['date'] = date('Y-m-d',time());
                    $statistics['total'] =$model->when(count($goods_label_ids)>0,function ($query)use($goods_label_ids){
                        foreach ($goods_label_ids as $id) {
                            $query->whereOrRaw("FIND_IN_SET($id, goods_label_id)");
                        }
                    })->where($where)->sum('price');
                    $statistics['total'] =number_format($statistics['total'], 2, '.', '');
                break;
        }
        return compact('count', 'pages', 'page', 'limit', 'list','statistics');
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
     * 查询状态数量
     * @Author: 易军辉
     * @param $source 来源：1、总后端、2、商家端 3、移动端
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getStatusNum($source=1){
        $merchantId = mer_id();
        $where = " where is_delete=0";
        if($merchantId>0){
            $where .=" and merchant_id=".$merchantId;
        }
        //根据状态查询数量
        $status_num = Db::query("SELECT status,count(id) as num from ya_goods ".$where." GROUP BY status");
        $status_nums = array();

        $status_nums['all'] = Db::name('goods')
            ->where('is_delete',0)
            ->when($merchantId>0, function($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId);
            })->count();
        foreach (GoodsModel::STATUS as $k => $v) {
            $status_nums[$v['code']] =0;
            foreach ($status_num as $k1 => $v1) {
                if($v1['status'] == $v['value']) {
                    $status_nums[$v['code']] = $v1['num'];
                    break;
                }
            }
        }
        return $status_nums;
    }
    /**
     * 商品信息
     *
     * @param int  $id   商品id
     * @param bool $exce 不存在是否抛出异常
     * @param string $source 来源：1、总后端、2、商家端 3、移动端
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true,$source=1)
    {
        $info = GoodsCache::get($id);
        if (empty($info) || $source == 3) {
            $model = new GoodsModel();
            $info = $model->when($source==3,function($query){
                $query->with(['merchant'=>function($query){
                    $query->field('id,title,address');
                },'member'=>function($query){
                    $query->field('member_id,nickname,avatar_id');
                },'member.avatar']);
            })->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('商品不存在：' . $id);
                }
                return [];
            }
            $info = $info->append(['image_url', 'images'])
                ->hidden(['image', 'files'])->toArray();
            if($info['goods_label_id']){
                $info['goods_label_id']=array_map('intval', explode(',', $info['goods_label_id']));
            }else{
                $info['goods_label_id']=[];
            }
            /****************************移动端**********************************************/
            if($source==3){
                //增加浏览数量
                $model->where('id',$info['id'])->update(['click_count'=>$info['click_count']+1]);
                if (!empty($info['merchant']['title'] ?? '')) {
                    $info['merchant']['title'] = MerchantModel::formatDisplayTitle((string) $info['merchant']['title']);
                }
                //商品原价处理
                if($info['original_price']<=0){
                    $info['original_price'] =bcadd($info['price'],10,2);
                }
                //查询商品标签
                $labels=[];
                if(isset($info['goods_label_id'])){
                    $labels = GoodsLabelModel::whereIn('id',$info['goods_label_id'])
                        ->where('is_disable',0)
                        ->where('is_delete',0)
                        ->column('title');
                }
                //线下商品处理
                if($info['source']==1){
                    if(isset($info['is_transaction']) && $info['is_transaction']==1){
                        array_push($labels, "已成交");
                    }
                    if(!isset($info['member']) || !is_array($info['member'])) {
                        $info['member'] = [];
                    }
                    if(!empty($info['member']['nickname'] ?? '')){
                        $info['member']['nickname']=self::hideMiddle($info['member']['nickname']);
                    }
                    //是否支持删除
                    if($info['member_id'] == member_id()){
                        $info['is_del']=1;
                    }else{
                        $info['is_del']=0;
                    }
                    // 默认头像
                    if(!isset($info['member']['avatar']) || !is_array($info['member']['avatar'])) {
                        $info['member']['avatar'] = [];
                    }
                    if(!isset($info['member']['avatar']['file_url'])){
                        $setting = SettingService::info();
                        $info['member']['avatar']['file_url'] =$setting['default_avatar_url'];
                    }
                }
                $info['labels']=$labels;
                //移动端幻灯片处理
                $bannerList=[];
                if(count($info['images'])>1){
                    foreach ($info['images'] as $k=>$v){
                        array_push($bannerList,[
                            'url'=>$v['file_url']
                        ]);
                    }
                }
                if(isset($info['image_url'])){
                    array_push($bannerList,[
                        'url'=>$info['image_url']
                    ]);
                }
                $info['banner_list']=$bannerList;

            }
            GoodsCache::set($id, $info);
        }
        $settingInfo = \app\common\service\system\SettingService::info('wx_approved');
        if($settingInfo['wx_approved']==1){
            unset($info['merchant']);
//            $info['merchant']['title'] = '平台自营';
        }
        return $info;
    }
    /**
     * 商品添加
     *
     * @param array $param 商品信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();
        // 启动事务
        $model->startTrans();
        try {
            //标签处理
            if(isset($param['goods_label_id']) && $param['goods_label_id']){
                $param['goods_label_id'] = implode(',',$param['goods_label_id']);
            }else{
                $param['goods_label_id']=null;
            }
            $model->save($param);
            // 添加文件
            if (isset($param['images']) && $param['images']) {
                $model->files()->saveAll(file_ids($param['images']), ['file_type' => 'image'], true);
            }
            $id = $model->$pk;
            if (empty($id)) {
                exception();
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
     * 商品修改
     *
     * @param int|array $ids   商品id
     * @param array     $param 商品信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        // 启动事务
        $model->startTrans();
        try {
            //标签处理
            if(isset($param['goods_label_id'])){
                $param['goods_label_id'] = implode(',',$param['goods_label_id']);
            }else{
                $param['goods_label_id']=null;
            }
            $res = $model->where($pk, 'in', $ids)->update($param);
            if (empty($res)) {
                exception();
            }
            // 添加文件
            if (isset($param['images'])) {
                foreach ($ids as $id) {
                    $info = $model->find($id);
                    $info = $info->append(['image_ids']);
                    relation_update($info, $info['image_ids'], file_ids($param['images']), 'files', ['file_type' => 'image']);
                }
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
        GoodsCache::del($ids);
        return $param;
    }
    /**
     * 商品删除
     *
     * @param array $ids  商品id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new GoodsModel();
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
        GoodsCache::del($ids);
        return $update;
    }

    /**
     * @title:根据商品分类生成商品编码
     * @author：易军辉
     * @date：2024/12/20
     * @param $goods_type_id
     */
    public static function getCode($goods_type_id){
        $goods_type_code=GoodsTypeModel::where('id',$goods_type_id)->value('code');
        $merchant_id = mer_id();
        $prefix =$goods_type_code.$merchant_id;

        $model = new GoodsModel();
        // 查询数据库中该前缀的最大批次号
        $maxNo = $model->where('is_delete',0)
//            ->when($merchant_id,function ($query)use($merchant_id){
//                $query->where('merchant_id',$merchant_id);
//            })
            ->where('code', 'like', $prefix . '%')
            ->order('code', 'desc') // 降序排序以找到最大的批次号
            ->value('code'); // 直接获取 title 的最大值
        if ($maxNo) {
            // 提取当前最大批次号的最后三位数字，并加 1
            $currentNumber = (int)substr($maxNo, -3);
            $newNumber = str_pad($currentNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // 如果没有最大值，从 001 开始
            $newNumber = '001';
        }

        // 生成最终批次号
        $batchNo = $prefix . $newNumber;
        return $batchNo;
    }

    /**
     * @title:查询商品审核状态
     * @author：易军辉
     * @date：2024/12/20
     * @return array[]
     */
    public static function getStatus(){
        return GoodsModel::STATUS;
    }

    /**
     * @title:审核
     * @author：易军辉
     * @date：2024/12/20
     */
    public static function auth($ids,$param)
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $list = $model
            ->where('is_delete',0)
            ->where('status',0)
            ->where($pk, 'in', $ids)
            ->select();
        if(count($list)<=0){
            exception("未有符合条件的商品需要审核");
        }
        // 启动事务
        $model->startTrans();
        try {
            foreach ($list as $k=>$v){
                $v->auth_time=datetime();
                $v->auth_uid=user_id();
                if(isset($param['goods_label_id'])){
                    $v->goods_label_id = implode(',',$param['goods_label_id']);
                }
                switch (intval($param['goods_status'])){
                    case 0://待审核
                        $v->status=0;
                        break;
                    case 1://审核通过
                        $v->status=1;
                        $v->stock=$param['stock'];
                        break;
                    case 2://审核失败
                        $v->status=2;
                        $v->auth_msg=$param['auth_msg'];
                        break;
                }
                $v->save();
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
        GoodsCache::del($ids);
        return $param;
    }

    /**
     * 批量迁移商品到平台自营
     *
     * @param array $ids
     * @return array
     */
    public static function transferToPlatform(array $ids = [])
    {
        $goodsList = self::getTransferableGoodsList($ids);
        $goodsIds = array_values(array_map('intval', array_column($goodsList, 'id')));
        if (empty($goodsIds)) {
            exception('未找到可迁移的商品');
        }

        $model = new GoodsModel();
        $update = [
            'merchant_id' => 0,
            'member_id' => 0,
            'update_uid' => user_id(),
            'update_time' => datetime(),
        ];

        $model->startTrans();
        try {
            $affected = $model->whereIn('id', $goodsIds)->update($update);
            if ($affected === false) {
                exception('批量迁移失败');
            }
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        GoodsCache::del($goodsIds);
        return [
            'ids' => $goodsIds,
            'target_type' => 'platform',
            'target_merchant_id' => 0,
            'target_member_id' => 0,
            'count' => count($goodsIds),
        ];
    }

    /**
     * 批量迁移商品到指定商家
     *
     * @param array $ids
     * @param int $targetMerchantId
     * @return array
     */
    public static function transferToMerchant(array $ids = [], int $targetMerchantId = 0)
    {
        if ($targetMerchantId <= 0) {
            exception('请选择目标商家');
        }

        $goodsList = self::getTransferableGoodsList($ids);
        $goodsIds = array_values(array_map('intval', array_column($goodsList, 'id')));
        if (empty($goodsIds)) {
            exception('未找到可迁移的商品');
        }

        $merchant = MerchantModel::where('id', $targetMerchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->field('id,title,member_id,auth_state')
            ->find();
        if (empty($merchant)) {
            exception('目标商家不存在或已被禁用');
        }
        if (intval($merchant['auth_state'] ?? 0) !== MerchantModel::getAuthState('success', 1)) {
            exception('目标商家未审核通过，暂不可迁移');
        }

        $targetMemberId = intval($merchant['member_id'] ?? 0);
        if ($targetMemberId <= 0) {
            exception('目标商家未绑定有效会员，暂不可迁移');
        }

        $model = new GoodsModel();
        $update = [
            'merchant_id' => $targetMerchantId,
            'member_id' => $targetMemberId,
            'update_uid' => user_id(),
            'update_time' => datetime(),
        ];

        $model->startTrans();
        try {
            $affected = $model->whereIn('id', $goodsIds)->update($update);
            if ($affected === false) {
                exception('批量迁移失败');
            }
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        GoodsCache::del($goodsIds);
        return [
            'ids' => $goodsIds,
            'target_type' => 'merchant',
            'target_merchant_id' => $targetMerchantId,
            'target_member_id' => $targetMemberId,
            'target_merchant_title' => (string)($merchant['title'] ?? ''),
            'count' => count($goodsIds),
        ];
    }
    /**
     * 商品删除
     *
     * @param array $ids  商品id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function memberDele($id, $real = false)
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        if ($real) {
            $res = $model->where($pk,  $id)->where('source',1)->where('member_id',member_id())->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk,  $id)->where('source',1)->where('member_id',member_id())->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $id;
        GoodsCache::del($id);
        return $update;
    }

    /**
     * @title:线下交易
     * @author：易军辉
     * @date：2025/1/3
     * @param $id
     */
    public static function transaction($id)
    {
        $model = new GoodsModel();
        $pk = $model->getPk();
        $info = $model
            ->where('is_delete',0)
            ->where('source',1)
            ->where($pk,  $id)
            ->find($id);
        if (empty($info)) {
            exception("商品不存在");
        }
        
        $requiresWeighing = intval($info['setting_call_id'] ?? 0) > 0 || intval($info['setting_hall_id'] ?? 0) > 0;
        if($requiresWeighing && $info['is_weighing']!=1){
            exception("该商品需要先完成称重后才能线下交易");
        }
        if($info['is_transaction']==1){
            exception("该商品已被其它买家购买");
        }
        $member_id=member_id();
        if($info['member_id'] == $member_id){
            exception("不能购买自己的商品");
        }
        // 启动事务
        $model->startTrans();
        try {
            $res = $model->where($pk,  $id)->where('source',1)->update([
                'is_transaction'=>1,
                'transaction_member_id'=>$member_id,
                'transaction_time'=>datetime(),
                'sales_sum'=>$info['stock'],
                'stock'=>0
            ]);
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

        $param['id'] = $id;
        GoodsCache::del($id);
        return $param;
    }
    /**
     * @title:保存称重
     * @author：易军辉
     * @date：2024/12/25
     * @param $list
     */
    public static function saveOfflineWait($list)
    {
        $model = new GoodsModel();
        // 启动事务
        $model::startTrans();
        $count  = 0;
        try {
            $member_id = member_id(true);
            foreach ($list as $key=>$val){
                $res = $model->where('id',$val['id'])->where('source',1)->where('is_weighing',0)->update([
                    'is_weighing'=>1,
                    'weighing_uid'=>$member_id,
                    'stock'=>$val['stock'],
                    'weighing_time'=>datetime()
                ]);
                if($res){
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
     * 获取允许迁移的商品列表。
     * 当前仅允许迁移后台商品池(source=0)中的商品，避免误伤线下商品链路。
     *
     * @param array $ids
     * @return array
     */
    private static function getTransferableGoodsList(array $ids = []): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            exception('请选择商品');
        }

        $list = GoodsModel::whereIn('id', $ids)
            ->where('is_delete', 0)
            ->where('source', 0)
            ->field('id,merchant_id,member_id,source')
            ->select()
            ->toArray();

        if (count($list) !== count($ids)) {
            exception('所选商品中包含不可迁移项，当前仅支持迁移后台商品池中的商品');
        }

        return $list;
    }
}
