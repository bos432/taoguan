<?php
namespace app\common\service\member;
use app\common\cache\member\MemberShopCartCache;
use app\common\model\goods\GoodsLabelModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberShopCartModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\file\MerchantFileService;

/**
 * 购物车
 */
class MemberShopCartService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'is_disable' => '',
        'member_id' => '',
        'goods_id' => '',
        'merchant_id' => '',
        'cart_num' => '',
        'is_pay' => '',
    ];
    /**
     * 购物车列表
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
        $model = new MemberShopCartModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,member_id,goods_id,merchant_id,cart_num,is_pay';
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
     * @title:购物车列表
     * @author：易军辉
     * @date：2024/12/12
     * @param $member_id 会员id
     */
    public static function getList($member_id=0,$order = [], $field = ''){
        $model = new MemberShopCartModel();
        if (empty($field)) {
            $field = 's.id as cart_id,g.id,g.merchant_id,g.title,g.image_id,g.original_price,g.price,g.spec,g.unit,g.stock,g.goods_label_id,s.cart_num';
        }
        if (empty($order)) {
            $order = ['g.merchant_id'=>'asc','s.id' => 'desc'];
        }
        $list = $model->alias('s')
            ->leftjoin('goods g', 's.goods_id=g.id')
            ->field($field)
            ->where('s.member_id',$member_id)
            ->where('s.is_disable',0)
            ->where('s.is_delete',0)
            ->where('s.is_pay',0)
            ->where('g.is_disable',0)
            ->where('g.is_delete',0)
            ->order($order)
            ->select()
            ->toArray();
        if (empty($list)) {
            return ['list' => []];
        }
        $merchant_ids = array_column($list, 'merchant_id');
        $merchant_list = MerchantModel::whereIn('id',$merchant_ids)
            ->where('is_delete',0)
            ->where('is_disable',0)
            ->field('id,title as name')
            ->select()
            ->toArray();
        foreach ($merchant_list as $key => $val) {
            $merchant_list[$key]['name'] = MerchantModel::formatDisplayTitle((string) ($val['name'] ?? ''));
            $merchant_list[$key]['id'] = "shop_".$val['id'];
            $merchant_list[$key]['goods'] = [];
            $merchant_list[$key]['checked'] = false;
            foreach ($list as $k => $v) {
                if($val['id'] == $v['merchant_id']) {
                    //查询商品图片
                    $img = "";
                    if(isset($v['image_id'])){
                        $image_info = MerchantFileService::info($v['image_id']);
                        if(isset($image_info['file_url'])){
                            $img = $image_info['file_url'];
                        }
                    }
                    //查询商品标签
                    $labels=[];
                    if(isset($v['goods_label_id'])){
                        $labels = GoodsLabelModel::whereIn('id',$v['goods_label_id'])
                            ->where('is_disable',0)
                            ->where('is_delete',0)
                            ->column('title');
                    }
                    array_push($merchant_list[$key]['goods'], [
                        'id' => $v['id'],
                        'cart_id'=> $v['cart_id'],
                        'num'=>$v['cart_num'],
                        'max'=>$v['stock'],
                        'checked'=>false,
                        'price'=>$v['price'],
                        'stock'=>$v['stock'],
                        'unit'=>$v['unit'],
                        'original_price'=>$v['original_price']>0?$v['original_price']:bcadd($v['price'],10,2),
                        'labels'=>$labels,
                        'spec'=>$v['spec'],
                        'name'=>$v['title'],
                        'img'=>$img
                    ]);
                }
            }
        }
        return ['list'=>$merchant_list];
    }

    /**
     * @title:查询购物车数量
     * @author：易军辉
     * @date：2024/12/12
     */
    public static function getCartNum($member_id=0){
        if($member_id>0){
            $model = new MemberShopCartModel();
            return $model
                ->where('member_id',$member_id)
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('is_pay',0)
                ->sum('cart_num');
        }else{
            return 0;
        }
    }
    /**
     * 购物车信息
     *
     * @param int  $id   购物车id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MemberShopCartCache::get($id);
        if (empty($info)) {
            $model = new MemberShopCartModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('购物车不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MemberShopCartCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 购物车添加
     *
     * @param array $param 购物车信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MemberShopCartModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        // 启动事务
        $model::startTrans();
        try {
            //查询商品
            $goodsObj = GoodsModel::where('id',$param['goods_id'])->where('is_delete',0)->where('status',1)->find();
            if(!$goodsObj){
                exception("商品不存在");
            }
            if($goodsObj['is_disable']==1){
                exception("该商品已下架");
            }
            //查询购物车
            $info = $model
                ->where('member_id',$param['member_id'])
                ->where('goods_id',$goodsObj['id'])
                ->where('is_disable',0)
                ->where('is_delete',0)
                ->where('is_pay',0)
                ->find();
            if($info){//修改
                $save_res = $model->where('id',$info['id'])->update([
                    'cart_num'=>$info['cart_num']+1,
                    'update_time'=>datetime(),
                    'update_uid'=>$param['member_id']
                ]);
            }else{//添加
                $data=[
                    'create_uid'=>$param['member_id'],//添加用户id
                    'create_time'=>datetime(),//创建时间
                    'member_id'=>$param['member_id'],//会员id
                    'goods_id'=>$goodsObj['id'],//商品id
                    'merchant_id'=>$goodsObj['merchant_id'],//商家id
                    'cart_num'=>1,//商品数量
                ];
                $save_res =$model->save($data);
            }
            if (!$save_res) {
                exception();
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
        return self::getCartNum($param['member_id']);
    }
     /**
     * 购物车修改
     *
     * @param int|array $ids   购物车id
     * @param array     $param 购物车信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MemberShopCartModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  =$param['member_id'];
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->where('member_id',$param['member_id'])->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MemberShopCartCache::del($ids);
        return $param;
    }
    /**
     * 购物车删除
     *
     * @param array $ids  购物车id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false,$member_id=0)
    {
        $model = new MemberShopCartModel();
        $pk = $model->getPk();
        if ($real) {
            $res = $model->where($pk, 'in', $ids)->where('member_id',$member_id)->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk, 'in', $ids)->where('member_id',$member_id)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        MemberShopCartCache::del($ids);
        return $update;
    }
}
