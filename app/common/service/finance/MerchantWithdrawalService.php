<?php
namespace app\common\service\finance;
use app\common\cache\finance\MerchantWithdrawalCache;
use app\common\model\finance\MerchantAccountModel;
use app\common\model\finance\MerchantWithdrawalModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\merchant\MerchantService;
use app\common\service\system\SettingService;

/**
 * 商家提现
 */
class MerchantWithdrawalService
{
    /**
     * 添加、修改字段
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'is_disable' => '',
        'amount' => '',
        'commission' => '',
        'total_amount' => '',
        'name' => '',
        'bank' => '',
        'bank_branch' => '',
        'card_no' => '',
        'alipay_account' => '',
        'auth_status' => '',
        'auth_msg' => '',
        'voucher_id' => '',
        'auth_uid' => '',
        'auth_time' => '',
    ];
    /**
     * 商家提现列表
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
        $model = new MerchantWithdrawalModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,is_disable,create_time,update_time,amount,commission,total_amount,name,bank,bank_branch,card_no,alipay_account,auth_status,auth_msg,voucher_id,auth_uid,auth_time';
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
     * 商家提现信息
     *
     * @param int  $id   商家提现id
     * @param bool $exce 不存在是否抛出异常
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MerchantWithdrawalCache::get($id);
        if (empty($info)) {
            $model = new MerchantWithdrawalModel();
            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('商家提现不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MerchantWithdrawalCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 商家提现添加
     *
     * @param array $param 商家提现信息
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantWithdrawalModel();
        $pk = $model->getPk();
        $merchant_id = mer_id();
        unset($param[$pk]);
        //账户余额
        $merchant = MerchantModel::where('id',$merchant_id)->field('id,mer_money')->find();
        $balance = $merchant['mer_money'];
        if($param['amount']>$balance){
            exception('余额不足：'.$param['amount']);
        }

        //收款账户
        $accounts = MerchantAccountModel::where('merchant_id',$merchant_id)
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->field('id,type,name,bank,bank_branch,card_no,alipay_account')
            ->select();
        $bank = null;
        $alipay=null;
        foreach ($accounts as $key=>$val){
            if($val['type'] == 1){
                $bank = $val;
            }else if($val['type']==2){
                $alipay = $val;
            }
        }
        if(!$bank || !$alipay){
            exception('请完善收款账号信息');
        }
        //提现限制
        $setting = SettingService::info('mer_min_withdrawal_amount,mer_max_withdrawal_amount,mer_withdrawal_commission');
        if($setting['mer_min_withdrawal_amount']>0 && $param['amount']<$setting['mer_min_withdrawal_amount']){
            exception('提现金额不能小于'.$setting['mer_min_withdrawal_amount']);
        }
        if($setting['mer_max_withdrawal_amount']>0 && $param['amount']>$setting['mer_max_withdrawal_amount']){
            exception('提现金额不能大于'.$setting['mer_max_withdrawal_amount']);
        }
        $mer_withdrawal_commission = $setting['mer_withdrawal_commission'];
        $commission = 0;//手续费
        if($mer_withdrawal_commission>0){
            $commission = bcmul($param['amount'],bcdiv($mer_withdrawal_commission, 100,4),4);
            $commission = round($commission, 2); // 四舍五入保留两位小数
        }
        $param['create_uid']  = operate_user_id();
        $param['create_time'] = datetime();
        $param['merchant_id']  =$merchant_id;//商家
        $param['total_amount']  =$param['amount'];//提现总额
        $param['amount']  =bcsub($param['amount'], $commission,2);//提现金额(不含手续费)
        $param['commission']  =$commission;//提现手续费
        $param['code']  =null;//提现编码(OnLineAlipay:在线支付宝,Alipay：支付宝，WeChat：微信，Card：银行卡)
        $param['name']  =$bank['name'];//提现姓名
        $param['bank']  =$bank['bank'];//开户银行
        $param['bank_branch']  =$bank['bank_branch'];//开户支行
        $param['card_no']  =$bank['card_no'];//银行卡号
        $param['alipay_account']  =$alipay['alipay_account'];//支付宝账号
        $param['auth_status']  =0;//审核状态：0、待审核 1、审核通过 2、审核拒绝
        // 启动事务
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            //扣除余额
            $res=MerchantService::withdrawal(
                $param['total_amount'],
                "余额提现",
                $id,
                '提现记录ID：'.$id
            );
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
     * 商家提现修改
     *
     * @param int|array $ids   商家提现id
     * @param array     $param 商家提现信息
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantWithdrawalModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }
        $param['ids'] = $ids;
        MerchantWithdrawalCache::del($ids);
        return $param;
    }
    /**
     * 商家提现删除
     *
     * @param array $ids  商家提现id
     * @param bool  $real 是否真实删除
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantWithdrawalModel();
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
        MerchantWithdrawalCache::del($ids);
        return $update;
    }

    /**
     * 查询提现页面参数
     * @Author: 易军辉
     * @DateTime:2024-07-12 16:45
     *
     */
    public static function getParams()
    {
        //账户余额
        $balance = MerchantModel::where('id',mer_id(true))->value('mer_money as balance');
        //收款账户
        $accounts = MerchantAccountModel::where('merchant_id',mer_id())
            ->where('is_disable',0)
            ->where('is_delete',0)
            ->where('source',2)
            ->field('id,type,name,bank,bank_branch,card_no,alipay_account,source')
            ->select();
        //提现限制
        $setting = SettingService::info('mer_min_withdrawal_amount,mer_max_withdrawal_amount,mer_withdrawal_commission');
        $mer_min_withdrawal_amount = (float)$setting['mer_min_withdrawal_amount'];
        if($mer_min_withdrawal_amount<=0){
            $mer_min_withdrawal_amount = 0.01;
        }
        $mer_max_withdrawal_amount = (float)$setting['mer_max_withdrawal_amount'];
        if($mer_max_withdrawal_amount<=0){
            $mer_max_withdrawal_amount = 'Infinity';
        }
        $mer_withdrawal_commission = (float)$setting['mer_withdrawal_commission'];
        if($mer_withdrawal_commission>0){
            $mer_withdrawal_commission=(float)bcdiv($mer_withdrawal_commission, 100,4);
        }
        $bank = null;
        $alipay=null;
        foreach ($accounts as $key=>$val){
            if($val['type'] == 1){
                $bank = $val;
            }else if($val['type']==2){
                $alipay = $val;
            }
        }
        return compact('balance','bank','alipay','mer_min_withdrawal_amount','mer_max_withdrawal_amount','mer_withdrawal_commission');
    }
}
