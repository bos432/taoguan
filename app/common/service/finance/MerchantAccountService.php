<?php
namespace app\common\service\finance;
use app\common\cache\finance\MerchantAccountCache;
use app\common\model\finance\MerchantAccountModel;

/**
 * 商家银行卡管理
 */
class MerchantAccountService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
        'id' => '',
        'is_disable' => '',
        'type' => '',
        'name' => '',
        'bank' => '',
        'bank_branch' => '',
        'card_no' => '',
        'alipay_account' => '',
        'alipay_url' => '',
        'remark' => '',
        'sort' => '',
        'source/d'=>1,
    ];
    /**
     * 商家银行卡管理列表
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
        $model = new MerchantAccountModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,is_disable,create_time,update_time,merchant_id,type,name,bank,bank_branch,card_no,alipay_account,alipay_url,remark,sort';
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
     * 商家银行卡管理信息
     *
     * @param int  $id   商家银行卡管理id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MerchantAccountCache::get($id);
        if (empty($info)) {
            $model = new MerchantAccountModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('商家银行卡管理不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MerchantAccountCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 商家银行卡管理添加
     *
     * @param array $param 商家银行卡管理信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantAccountModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        //判断重复
        if($param['source'] ==2){
            $account = $model->where('merchant_id',$param['merchant_id'])->where('source',$param['source'])->where('type',$param['type'])->find();
            if($account){
                $type_str = $account['type']==1?'收款银行卡':'收款支付宝';
                exception($type_str.'已存在，请进行修改');
            }
        }
        $param['create_uid']  = mer_user_id();
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
     * 商家银行卡管理修改
     *
     * @param int|array $ids   商家银行卡管理id
     * @param array     $param 商家银行卡管理信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantAccountModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = mer_user_id();
        $param['update_time'] = datetime();
        $merchantId = mer_id();
        $res = $model->where($pk, 'in', $ids)
            ->when($merchantId>0, function($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId);
            })
            ->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MerchantAccountCache::del($ids);
        return $param;
    }
    /**
     * 商家银行卡管理删除
     *
     * @param array $ids  商家银行卡管理id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantAccountModel();
        $pk = $model->getPk();
        $merchantId = mer_id();
        if ($real) {
            $res = $model->where($pk, 'in', $ids)
                ->when($merchantId>0, function($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId);
                })
                ->delete();
        } else {
            $update = mer_delete_update();
            $res = $model->where($pk, 'in', $ids)
                ->when($merchantId>0, function($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId);
                })
                ->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        MerchantAccountCache::del($ids);
        return $update;
    }
}
