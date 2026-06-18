<?php

namespace app\common\service\merchant;
use app\common\model\finance\MerchantBillModel;
use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use app\common\cache\merchant\MerchantCache;
use think\facade\Db;

/**
 * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓?
 */
class MerchantService
{
    /**
     * 婵犵數濮烽弫鎼佸磿閹寸姷绀婇柍褜鍓氶妵鍕即閸℃顏柛娆忕箻閺岋綁骞囬浣瑰創閺夆晜绻冪换娑欐綇閸撗呅滈梺琛″亾闂侇剙绉撮惌妤呯叓閸ャ劎鈯曢柣鎾寸洴閺屽秷顧侀柛鎾跺枛楠炲啴鎮滈挊澶岊吋濡炪倖妫佹慨銈夊窗閺囩喓绡€?
     * @var array
     */
    public static $edit_field = [
            'id' => '',
        'title' => '',
        'content' => '',
        'is_disable' => '',
        'username' => '',
        'password' => '',
        'region_id' => '',
        'address' => '',
        'phone' => '',
        'auth_state' => '',
        'auth_msg' => '',
        'sort' => '',
        'remark' => '',
        'name' => '',
        'image_id' => '',
        'member_id' => '',
    ];
    /**
     * @title:闂傚倸鍊风粈渚€骞栭銈嗗仏妞ゆ劧绠戠壕鍧楁煙缂併垹娅橀柡浣割儐娣囧﹪濡堕崨顔兼闂佹椿鍘介〃濠囧蓟濞戞矮娌柛鎾椻偓婵洤鈹?
     * @author闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟鐑樻煥閺嬪牏鈧箍鍎遍幉姗€鏁愰崨鍌涙瀹曘劑顢橀悪鈧Σ鐗堢節閻㈤潧浠﹂柛銊ュ閸掓帗鎯旈敐鍥╋紴?
     * @date闂?025/2/12
     * @param $source 闂傚倸鍊风粈渚€骞栭位鍥敇閵忕姷锛熼梺鑲┾拡閸撴繃鎱ㄩ搹顐犱簻闁哄洦顨呮禍楣冩⒑?闂傚倸鍊风欢姘焽瑜嶈灋闁哄啫鐗婇崐鍧楁煥閺囩偛鈧悂鎮為崹顐犱簻闁圭儤鍨甸弸銈夋煕閵堝棛鎳囬柡宀€鍠栭、娆戠驳鐎ｎ偆鏆紓鍌欑贰閸犳帡寮查悩姹団偓浣糕槈濡粎鍠庨悾鈩冿紣娴ｅ壊妫?闂傚倸鍊风欢姘焽瑜嶈灋闁哄啫鍊婚惌鍡椼€掑锝呬壕閻庢鍠楅悡鈩冩叏閳ь剟鏌嶉崹娑欐珔濞存粓绠栭弻锝夋偄閸涘﹦鍑″銈忕秬濞呮洟濡?3闂傚倸鍊风欢姘焽瑜嶈灋闁哄啫鐗婇崵鍕煛閸垺鏆╅柡鍕€垮缁樻媴閸涘﹤鏆堥梺鍝勭焿缂嶄礁鐣峰┑鍥ㄥ劅妞ゎ厽鍨堕弲?
     * @return array
     */
    public static function getParams($source=1)
    {
        $auth_states = MerchantModel::AUTH_STATE;
        return compact('auth_states');
    }
    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犻亶鏌＄仦鍓ф创妤犵偞甯￠獮娆撳礃閳哄啯姣岄梻?
     *
     * @param array  $where 闂傚倸鍊风粈渚€骞栭位鍥敃閿曗偓閻掑灚銇勯幒鍡椾壕閻庢鍠栭悥濂搞€?
     * @param int    $page  濠电姷顣槐鏇㈠磻閹达箑纾归柡鍥ュ灪閸婅埖鎱ㄥΟ鎸庣【婵?
     * @param int    $limit 闂傚倸鍊峰ù鍥ь浖閵娿儺娓婚柟鐑橆殔绾偓闂佽鍎煎Λ鍕⒒?
     * @param array  $order 闂傚倸鍊风粈浣革耿闁秴纾块柕鍫濇处閺嗘粓鏌熼悜妯活梿濠?
     * @param string $field 闂傚倷娴囬褏鈧稈鏅濈划娆撳箳濡炲皷鍋撻崘顔煎耿婵炴垼椴搁弲?
     *
     * @return array
     */
    public static function list($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,content,is_disable,create_time,update_time,username,password,region_id,address,phone,auth_state,auth_msg,sort,remark,name,image_id,member_id';
        }
        if (empty($order)) {
            $order = ['sort'=>'asc',$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        if (strpos($field, 'member_id') !== false) {
            $with = ['member'=>function($query){
                $query->with(['avatar'])->append(['avatar_url'])->hidden(['avatar'])->field('member_id,nickname,avatar_id');
            }];
        }
        if (strpos($field, 'image_id') !== false) {
            $with[]   = $hidden[]   = 'image';
            $append[] = 'image_url';
        }
        if ($page == 0 || $limit == 0) {
            return $model->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['auth_state_title'] = MerchantModel::getAuthState($v['auth_state'],2);
        }
        $status_nums =self::getStatusNum(0);
        return compact('count', 'pages', 'page', 'limit', 'list','status_nums');
    }

    /**
     * @title: 闂傚倸鍊风粈渚€骞夐敓鐘茬闁告縿鍎抽惌鎾绘煕椤愶絾澶勯柡浣稿€归妵鍕箳閹存繍浠奸梺钘夊暟閸犳牠寮婚弴鐔风窞婵☆垳鍘х敮銉╂⒑閹肩偛鈧洜鈧矮鍗冲璇测槈閵忊€充簻闂備礁鐏濋鎰涢悙宸富?
     * @author闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟鐑樻煥閺嬪牏鈧箍鍎遍幉姗€鏁愰崨鍌涙瀹曘劑顢橀悪鈧Σ鐗堢節閻㈤潧浠﹂柛銊ュ閸掓帗鎯旈敐鍥╋紴?
     * @date闂?025/9/14
     * @param $where
     * @param $page
     * @param $limit
     * @param $order
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getList($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        if (empty($field)) {
            $field = 'id,title,member_id';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }
        $with     = [];
        $append   = [];
        $hidden   = [];
        if (strpos($field, 'member_id') !== false) {
            $with = ['member'=>function($query){
                $query->with(['avatar'])->append(['avatar_url'])->hidden(['avatar'])->field('member_id,avatar_id');
            }];
        }
        if (strpos($field, 'id') !== false) {
            $with[] ='sellGoods';
        }
        if ($page == 0 || $limit == 0) {
            return $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->order($order)->select()->toArray();
        }
        $count = $model->where($where)->count();
        $pages = ceil($count / $limit);
        $list = $model->with($with)->append($append)->hidden($hidden)->field($field)->where($where)->page($page)->limit($limit)->order($order)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['sellGoodsNum'] = count($v['sellGoods']);
            unset($list[$k]['sellGoods']);
        }
        return compact('count', 'pages', 'page', 'limit', 'list');
    }
    /**
     * @title:闂傚倸鍊风粈渚€骞栭銈嗗仏妞ゆ劧绠戠壕鍧楁煙缂併垹娅橀柡浣割儐娣囧﹪濡堕崨顓熸疁闂佺顑嗛幑鍥х暦濡や礁绶炲┑鐘插€甸幏銈夋⒒娴ｈ櫣甯涙い銊ユ缁绘稒绻濋崒銈呮濡炪倖鍔х徊浠嬵敋闁秵鐓涘璺侯儏閻忥附銇?
     * @author闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟鐑樻煥閺嬪牏鈧箍鍎遍幉姗€鏁愰崨鍌涙瀹曘劑顢橀悪鈧Σ鐗堢節閻㈤潧浠﹂柛銊ュ閸掓帗鎯旈敐鍥╋紴?
     * @date闂?024/12/15
     * @param $member_id
     * @return array
     */
    public static function getStatusNum($member_id=0){
        $where = " where is_delete=0 and is_disable=0";
        //闂傚倸鍊风粈渚€骞栭銈囩煋闁绘垶鏋荤紞鏍ь熆鐠虹尨鍔熼柡鍡愬€曢妴鎺戭潩閿濆懍澹曢梻浣筋嚃閸犳帡寮查悩璇茬疇婵犲﹤鎳庨弸鍫熶繆椤栨繃銆冨瑙勬礃缁绘稒娼忛崜褍鍩屽┑鐘亾闂侇剙绉撮惌妤呮煃瑜滈崜鐔煎蓟閿濆鍋愰柛娆忣槸閸嬪秹姊洪幖鐐插婵＄偠妫勯锝夊箮閼恒儱浜滈梺绋跨箺閸嬫劙宕?
        $auth_state_num = Db::query("SELECT auth_state,count(id) as num from ya_merchant ".$where." GROUP BY auth_state");
        $auth_state_nums = array();
        //闂傚倸鍊风粈渚€骞栭銈嗗仏妞ゆ劧绠戠壕鍧楁煙缂併垹娅橀柡浣割儐娣囧﹪濡堕崨顔兼缂備讲鍋撻柛宀€鍋為悡蹇撯攽閻愰潧浜炬繛鍛嚇閺屾盯寮拠鎻掑Е闂佽鍠涢～澶愬箯閸涘瓨顥堟繛鎴炵煯閹綁鏌ｆ惔銈庢綈婵炲弶绮撳畷浼村冀瑜滈崵鏇熴亜閹板墎鍒版い鏇憾閺屸剝寰勭€ｎ亞浠稿?
        $auth_state_nums['all'] = Db::name('merchant')
            ->where('is_delete',0)
            ->where('is_disable',0)
            ->count();
        foreach (MerchantModel::AUTH_STATE as $k => $v) {
            $auth_state_nums[$v['code']] =0;
            foreach ($auth_state_num as $k1 => $v1) {
                if($v1['auth_state'] == $v['value']) {
                    $auth_state_nums[$v['code']] = $v1['num'];
                    break;
                }
            }
        }
        return $auth_state_nums;
    }
    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犳粓鎳ｉ幇鐗堢厱闁靛鍨抽崚鏉款熆瑜庤ぐ鍐箒?
     *
     * @param int  $id   闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插閸嬫捇骞?
     * @param bool $exce 濠电姷鏁搁崑鐐哄垂閸洖绠伴柛婵勫劤閻捇鏌熺紒銏犳灍闁稿鍊濋弻鏇熺箾閻愵剚鐝旈梺鍦嚀閻栧ジ寮婚弴鐔虹瘈闊洦绋掗宥囩磼濡や礁鐏存慨濠冩そ瀹曘劍绻濋崟顐ｆ闂傚倸鍊哥€氼剛鈧凹鍙冮獮鍫ュΩ閵娧呮澑濠电偞鍨堕…鍥囬埡鍛拺闁告稑锕︾粻鎾绘倵濮樼厧鏋ゅ瑙勬礃缁傛帞鈧綆鍋勬禒?
     *
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = MerchantCache::get($id);
        if (empty($info)) {
            $model = new MerchantModel();
            $info = $model->with(['image', 'files'])
                ->append(['image_url', 'images'])
                ->hidden(['image', 'files'])
                ->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('Merchant not found: ' . $id);
                }
                return [];
            }
            $info = $info->toArray();
            MerchantCache::set($id, $info);
        }
        return $info;
    }
    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犳粓鎳ｉ幇鐗堢厱闁靛鍨抽崚鏉款熆瑜庤ぐ鍐箒?
     *
     * @param int  $id   闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插閸嬫捇骞?
     * @param bool $exce 濠电姷鏁搁崑鐐哄垂閸洖绠伴柛婵勫劤閻捇鏌熺紒銏犳灍闁稿鍊濋弻鏇熺箾閻愵剚鐝旈梺鍦嚀閻栧ジ寮婚弴鐔虹瘈闊洦绋掗宥囩磼濡や礁鐏存慨濠冩そ瀹曘劍绻濋崟顐ｆ闂傚倸鍊哥€氼剛鈧凹鍙冮獮鍫ュΩ閵娧呮澑濠电偞鍨堕…鍥囬埡鍛拺闁告稑锕︾粻鎾绘倵濮樼厧鏋ゅ瑙勬礃缁傛帞鈧綆鍋勬禒?
     *
     * @return array|Exception
     */
    public static function getInfoByMemberID()
    {
        $model = new MerchantModel();
        $info = $model->where('member_id',member_id())
            ->append(['image_url', 'images'])
            ->hidden(['image', 'files'])
            ->field('id,title,username,phone,auth_state,auth_msg,name,image_id')
            ->find();
        if($info){
            $info = $info->toArray();
            $info['auth_state_title'] = MerchantModel::getAuthState($info['auth_state'],2);
            $info['mer_host'] = 'https://'.$_SERVER['SERVER_NAME']."/merchant";
            $info['pwd'] ="123456";
        }
        return $info;
    }

    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸褍顥氬┑鍌氭憸閸欐捇鏌涢妷锝呭妞わ絾鐓￠弻娑欐償閳藉棙鈻堥梺鍝勫閸撴繈骞忛崨鏉戜紶闁告洖鐏氬В澶愭煟鎼淬値娼愰柟鎼侇棑濞嗐垹顫濈捄铏圭暰闂佹悶鍎弬渚€宕戦幘缁樻櫜閹肩补鈧尙鍑圭紓?
     *
     * @param int $merchantId
     * @param int $days
     * @return array
     */
    public static function getAnalytics($merchantId, $param = [])
    {
        $range = self::resolveAnalyticsRange($param);
        $days = $range['days'];
        $refundStatus = MemberOrderModel::getStatus('refund', 1);
        $merchant = MerchantModel::where('id', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->field('id,title,auth_state,create_time,member_id')
            ->find();
        if (empty($merchant)) {
            exception('Merchant not found');
        }
        $merchant = $merchant->toArray();

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $rangeStart = $range['start_time'];
        $rangeEnd = $range['end_time'];
        $merchantMemberId = intval($merchant['member_id'] ?? 0);

        $orderQuery = Db::name('member_order')
            ->where('merchant_id', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0);
        $paidOrderQuery = Db::name('member_order')
            ->where('merchant_id', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('pay_status', 1)
            ->where('status', '<>', $refundStatus);
        $goodsQuery = GoodsModel::where('merchant_id', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0);
        $purchasePaidQuery = Db::name('member_order')
            ->where('member_id', $merchantMemberId)
            ->where('merchant_id', '<>', $merchantId)
            ->where('is_delete', 0)
            ->where('is_disable', 0)
            ->where('pay_status', 1)
            ->where('status', '<>', $refundStatus);

        $orderCount = (clone $orderQuery)->count();
        $paidOrderCount = (clone $paidOrderQuery)->count();
        $paidAmount = self::toFloat((clone $paidOrderQuery)->sum('pay_price'));
        $purchasePaidOrderCount = $merchantMemberId > 0 ? (clone $purchasePaidQuery)->count() : 0;
        $purchasePaidAmount = $merchantMemberId > 0 ? self::toFloat((clone $purchasePaidQuery)->sum('pay_price')) : 0;
        $todayOrderCount = (clone $orderQuery)->whereBetweenTime('create_time', $todayStart, $todayEnd)->count();
        $todayPaidOrderCount = (clone $paidOrderQuery)->whereBetweenTime('pay_time', $todayStart, $todayEnd)->count();
        $todayPaidAmount = self::toFloat((clone $paidOrderQuery)->whereBetweenTime('pay_time', $todayStart, $todayEnd)->sum('pay_price'));
        $todayBuyerCount = intval((clone $paidOrderQuery)->whereBetweenTime('pay_time', $todayStart, $todayEnd)->distinct(true)->count('member_id'));
        $todayPurchasePaidAmount = $merchantMemberId > 0 ? self::toFloat((clone $purchasePaidQuery)->whereBetweenTime('pay_time', $todayStart, $todayEnd)->sum('pay_price')) : 0;
        $todayPurchasePaidOrderCount = $merchantMemberId > 0 ? (clone $purchasePaidQuery)->whereBetweenTime('pay_time', $todayStart, $todayEnd)->count() : 0;
        $goodsCount = (clone $goodsQuery)->count();
        $onSaleGoodsCount = (clone $goodsQuery)->where('status', 1)->where('stock', '>', 0)->count();
        $soldOutGoodsCount = (clone $goodsQuery)->where('status', 1)->where('stock', '<=', 0)->count();
        $pendingGoodsCount = (clone $goodsQuery)->where('status', 0)->count();

        $rangeOrderCount = (clone $orderQuery)->whereBetweenTime('create_time', $rangeStart, $rangeEnd)->count();
        $rangePaidOrderCount = (clone $paidOrderQuery)->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)->count();
        $rangePaidAmount = self::toFloat((clone $paidOrderQuery)->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)->sum('pay_price'));
        $rangeBuyerCount = intval((clone $paidOrderQuery)->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)->distinct(true)->count('member_id'));
        $rangePurchasePaidAmount = $merchantMemberId > 0 ? self::toFloat((clone $purchasePaidQuery)->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)->sum('pay_price')) : 0;
        $rangePurchasePaidOrderCount = $merchantMemberId > 0 ? (clone $purchasePaidQuery)->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)->count() : 0;

        $statusRows = (clone $orderQuery)
            ->field('status, COUNT(id) as total')
            ->group('status')
            ->select()
            ->toArray();
        $statusMap = [];
        foreach ($statusRows as $row) {
            $statusMap[intval($row['status'])] = intval($row['total']);
        }
        $statusBreakdown = [];
        foreach (MemberOrderModel::STATUS as $status) {
            $count = $statusMap[$status['value']] ?? 0;
            $statusBreakdown[] = [
                'status' => $status['value'],
                'code' => $status['code'],
                'label' => $status['label'],
                'count' => $count,
                'rate' => $orderCount > 0 ? round(($count / $orderCount) * 100, 1) : 0,
            ];
        }

        $orderTrendRows = (clone $orderQuery)
            ->whereBetweenTime('create_time', $rangeStart, $rangeEnd)
            ->field("DATE_FORMAT(create_time,'%Y-%m-%d') as day, COUNT(id) as order_count")
            ->group("DATE_FORMAT(create_time,'%Y-%m-%d')")
            ->select()
            ->toArray();
        $paidTrendRows = (clone $paidOrderQuery)
            ->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)
            ->field("DATE_FORMAT(pay_time,'%Y-%m-%d') as day, COUNT(id) as paid_order_count, SUM(pay_price) as paid_amount")
            ->group("DATE_FORMAT(pay_time,'%Y-%m-%d')")
            ->select()
            ->toArray();
        $purchaseTrendRows = $merchantMemberId > 0 ? (clone $purchasePaidQuery)
            ->whereBetweenTime('pay_time', $rangeStart, $rangeEnd)
            ->field("DATE_FORMAT(pay_time,'%Y-%m-%d') as day, COUNT(id) as buy_order_count, SUM(pay_price) as buy_amount")
            ->group("DATE_FORMAT(pay_time,'%Y-%m-%d')")
            ->select()
            ->toArray() : [];
        $orderTrendMap = [];
        foreach ($orderTrendRows as $row) {
            $orderTrendMap[$row['day']] = intval($row['order_count']);
        }
        $paidTrendMap = [];
        foreach ($paidTrendRows as $row) {
            $paidTrendMap[$row['day']] = [
                'paid_order_count' => intval($row['paid_order_count']),
                'paid_amount' => self::toFloat($row['paid_amount'] ?? 0),
            ];
        }
        $purchaseTrendMap = [];
        foreach ($purchaseTrendRows as $row) {
            $purchaseTrendMap[$row['day']] = [
                'buy_order_count' => intval($row['buy_order_count']),
                'buy_amount' => self::toFloat($row['buy_amount'] ?? 0),
            ];
        }
        $trend = [];
        $trendEndDate = date('Y-m-d', strtotime($rangeEnd));
        $trendStartDate = date('Y-m-d', strtotime($rangeStart));
        for ($index = $days - 1; $index >= 0; $index--) {
            $day = date('Y-m-d', strtotime('-' . $index . ' days', strtotime($trendEndDate)));
            if (strtotime($day) < strtotime($trendStartDate)) {
                continue;
            }
            $trend[] = [
                'day' => $day,
                'label' => date('m-d', strtotime($day)),
                'order_count' => $orderTrendMap[$day] ?? 0,
                'paid_order_count' => $paidTrendMap[$day]['paid_order_count'] ?? 0,
                'paid_amount' => $paidTrendMap[$day]['paid_amount'] ?? 0,
                'buy_order_count' => $purchaseTrendMap[$day]['buy_order_count'] ?? 0,
                'buy_amount' => $purchaseTrendMap[$day]['buy_amount'] ?? 0,
            ];
        }

        $topGoodsRows = Db::name('member_order_detailed')
            ->alias('detail')
            ->join('member_order order_info', 'order_info.id = detail.member_order_id')
            ->where('order_info.merchant_id', $merchantId)
            ->where('order_info.is_delete', 0)
            ->where('order_info.is_disable', 0)
            ->where('order_info.pay_status', 1)
            ->where('order_info.status', '<>', $refundStatus)
            ->whereBetweenTime('order_info.pay_time', $rangeStart, $rangeEnd)
            ->field('detail.goods_id, SUM(detail.quantity) as sale_num, SUM(detail.total) as sale_amount, COUNT(DISTINCT detail.member_order_id) as order_count')
            ->group('detail.goods_id')
            ->order('sale_amount desc, sale_num desc')
            ->limit(5)
            ->select()
            ->toArray();
        $goodsIds = array_column($topGoodsRows, 'goods_id');
        $goodsList = [];
        if (!empty($goodsIds)) {
            $goodsList = GoodsModel::whereIn('id', $goodsIds)
                ->with(['image'])
                ->append(['image_url'])
                ->hidden(['image'])
                ->field('id,title,image_id,source,unit')
                ->select()
                ->toArray();
        }
        $goodsMap = [];
        foreach ($goodsList as $goods) {
            $goodsMap[intval($goods['id'])] = $goods;
        }
        $topGoods = [];
        foreach ($topGoodsRows as $row) {
            $goodsId = intval($row['goods_id']);
            $goods = $goodsMap[$goodsId] ?? [];
            $topGoods[] = [
                'goods_id' => $goodsId,
                'title' => $goods['title'] ?? 'Deleted product',
                'image_url' => $goods['image_url'] ?? '',
                'unit' => $goods['unit'] ?? '',
                'sale_num' => intval($row['sale_num']),
                'sale_amount' => self::toFloat($row['sale_amount'] ?? 0),
                'order_count' => intval($row['order_count']),
            ];
        }

        $overview = [
            'order_count' => $orderCount,
            'paid_order_count' => $paidOrderCount,
            'paid_amount' => $paidAmount,
            'purchase_paid_order_count' => $purchasePaidOrderCount,
            'purchase_paid_amount' => $purchasePaidAmount,
            'today_order_count' => $todayOrderCount,
            'today_paid_amount' => $todayPaidAmount,
            'today_buyer_count' => $todayBuyerCount,
            'today_purchase_paid_order_count' => $todayPurchasePaidOrderCount,
            'today_purchase_paid_amount' => $todayPurchasePaidAmount,
            'goods_count' => $goodsCount,
            'on_sale_goods_count' => $onSaleGoodsCount,
            'sold_out_goods_count' => $soldOutGoodsCount,
            'pending_goods_count' => $pendingGoodsCount,
            'average_order_amount' => $paidOrderCount > 0 ? round($paidAmount / $paidOrderCount, 2) : 0,
        ];
        $rangeSummary = [
            'filter_type' => $range['filter_type'],
            'days' => $days,
            'month' => $range['month'],
            'start_date' => date('Y-m-d', strtotime($rangeStart)),
            'end_date' => date('Y-m-d', strtotime($rangeEnd)),
            'order_count' => $rangeOrderCount,
            'paid_order_count' => $rangePaidOrderCount,
            'paid_amount' => $rangePaidAmount,
            'buyer_count' => $rangeBuyerCount,
            'label' => $range['label'],
        ];
        $tradeComparison = [
            'range_buy_amount' => $rangePurchasePaidAmount,
            'range_buy_order_count' => $rangePurchasePaidOrderCount,
            'range_sale_amount' => $rangePaidAmount,
            'range_sale_order_count' => $rangePaidOrderCount,
            'range_diff_amount' => round($rangePaidAmount - $rangePurchasePaidAmount, 2),
            'range_diff_order_count' => intval($rangePaidOrderCount - $rangePurchasePaidOrderCount),
            'range_buy_to_sale_ratio' => $rangePaidAmount > 0 ? round(($rangePurchasePaidAmount / $rangePaidAmount) * 100, 1) : null,
            'today_diff_amount' => round($todayPaidAmount - $todayPurchasePaidAmount, 2),
            'today_diff_order_count' => intval($todayPaidOrderCount - $todayPurchasePaidOrderCount),
            'today_buy_to_sale_ratio' => $todayPaidAmount > 0 ? round(($todayPurchasePaidAmount / $todayPaidAmount) * 100, 1) : null,
            'total_buy_amount' => $purchasePaidAmount,
            'total_buy_order_count' => $purchasePaidOrderCount,
            'total_sale_amount' => $paidAmount,
            'total_sale_order_count' => $paidOrderCount,
            'total_diff_amount' => round($paidAmount - $purchasePaidAmount, 2),
            'total_diff_order_count' => intval($paidOrderCount - $purchasePaidOrderCount),
            'today_buy_amount' => $todayPurchasePaidAmount,
            'today_buy_order_count' => $todayPurchasePaidOrderCount,
            'today_sale_amount' => $todayPaidAmount,
            'today_sale_order_count' => $todayPaidOrderCount,
        ];

        return [
            'merchant' => [
                'id' => intval($merchant['id']),
                'title' => $merchant['title'],
                'auth_state' => intval($merchant['auth_state']),
                'auth_state_title' => MerchantModel::getAuthState($merchant['auth_state'], 2),
                'create_time' => $merchant['create_time'],
            ],
            'overview' => $overview,
            'range_summary' => $rangeSummary,
            'trade_comparison' => $tradeComparison,
            'status_breakdown' => $statusBreakdown,
            'trend' => $trend,
            'top_goods' => $topGoods,
        ];
    }
    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴狅妇绱掔紒妯兼创闁轰焦鍔欏畷濂割敃閵忊槅鍞叉繝?
     *
     * @param array $param 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犳粓鎳ｉ幇鐗堢厱闁靛鍨抽崚鏉款熆瑜庤ぐ鍐箒?
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        unset($param[$pk]);
        $param['create_uid']  = user_id();
        $param['create_time'] = datetime();
        // 闂傚倷娴囬褔鏌婇敐鍜佺劷鐟滃秷鐏嬪┑鐐村灟閸ㄦ椽宕?
        $password = null;
        if (isset($param['password'])) {
            $password = $param['password'];
            $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
        }
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            /******************闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎箲閹邦剛鍘梺鍝ュТ濡繈寮婚悢鍏煎€绘俊顖濆吹椤︺儱顪?*****************************/
            $menu_list = Db::name('merchant_menu')->where('is_delete','<>',1)->field('menu_id')->select()->toArray();
            $role_data = array(
                'mer_id'=>$id,//merchant id
                'role_name'=>'Super Admin',//role name
                'role_desc'=>'Super Admin',//role desc
                'remark'=>'System default',//remark
                'sort'=>1,//sort
                'is_admin'=>1,//system role
                'menu_ids'=>array_column($menu_list,'menu_id')
            );
            $role = MerchantRoleService::add($role_data);
            /******************闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎礈瑜忕敮娑㈡煕鐎ｃ劌濮傞柡灞炬礃缁绘繆绠涢弴鐘虫闂?*****************************/
            $user_data = array(
                'mer_id'=>$id,//merchant id
                'number'=>'001',//number
                'nickname'=>isset($param['username']) ? $param['username'] : $param['title'],//nickname
                'username'=>$param['username'],//username
                'phone'=>$param['phone'],//phone
                'remark'=>'System default',//remark
                'sort'=>1,//sort
                'is_super'=>1,//super admin
                'is_admin'=>1,//system role
                'role_ids'=>[$role['role_id']]
            );
            if($password){
                $user_data['password'] = $password;//闂傚倷娴囬褔鏌婇敐鍜佺劷鐟滃秷鐏嬪┑鐐村灟閸ㄦ椽宕?
            }
            $role = MerchantUserService::add($user_data);
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }
        return $param;
    }
    /**
     * @title:闂傚倷娴囬褎顨ラ幖浣稿偍婵犲﹤鐗嗙粈鍫熺節闂堟侗鍎愰柛?
     * @author闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟鐑樻煥閺嬪牏鈧箍鍎遍幉姗€鏁愰崨鍌涙瀹曘劑顢橀悪鈧Σ鐗堢節閻㈤潧浠﹂柛銊ュ閸掓帗鎯旈敐鍥╋紴?
     * @date闂?024/12/20
     */
    public static function auth($ids,$param)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $list = $model
            ->where('is_delete',0)
            ->where('auth_state',0)
            ->where($pk, 'in', $ids)
            ->select();
        if(count($list)<=0){
            exception('No merchants matched the audit conditions');
        }
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            foreach ($list as $k=>$v){
                $v->auth_time=datetime();
                $v->auth_uid=user_id();
                switch (intval($param['auth_state'])){
                    case 0://闂備浇顕ф鎼佹倶濮橆剦鐔嗘慨妞诲亾妤犵偛锕ラ幆鏃堚€﹂幋鐐存珝闂備礁缍婂Λ璺ㄧ矆娓氣偓瀹曟﹢鍩€?
                        $v->auth_state=0;
                        break;
                    case 1://闂傚倷娴囬褎顨ラ幖浣稿偍婵犲﹤鐗嗙粈鍫熺節闂堟侗鍎愰柛瀣儔閺岀喖骞嗛弶鍟冩捇鏌ｉ幒鏃傚笡闁逛究鍔岃灒闁惧繒娅㈢槐鐐电磽?
                        $v->auth_state=1;
                        /******************闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎箲閹邦剛鍘梺鍝ュТ濡繈寮婚悢鍏煎€绘俊顖濆吹椤︺儱顪?*****************************/
                        $menu_list = Db::name('merchant_menu')->where('is_delete','<>',1)->field('menu_id')->select()->toArray();
                        $role_data = array(
                            'mer_id'=>$v['id'],//闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸鍘滈崑鎾剁磼?
                            'role_name'=>'Super Admin',//role name
                            'role_desc'=>'Super Admin',//role desc
                            'remark'=>'System default',//remark
                            'sort'=>1,//闂傚倸鍊风粈浣革耿闁秴纾块柕鍫濇处閺嗘粓鏌熼悜妯活梿濠?
                            'is_admin'=>1,//闂傚倸鍊风粈渚€骞栭銈傚亾濮樺崬鍘寸€规洝顫夌€靛ジ寮堕幋鐘垫毎濠电偠鎻徊钘夛耿闁秴鐓濋柡鍐ㄧ墛閻擄綁鐓崶銊﹀暗缂佸妞介弻娑滅疀閹炬潙绐涚紓浣介哺閹稿骞忛崨鏉戜紶闁告洦鍓涙禍鐐翠繆閻愵亜鈧倝宕戦崨娣偓鍐╃節閸パ呯枃濠电娀娼ч悧濠勨偓姘皑閹叉瓕绠涢弴鐘靛箵?闂傚倸鍊风欢姘焽瑜嶈灋闁哄倹顑欏〒濠氭煙閻戞ɑ鈷掔紒鐘虫煥椤潡鎳滈棃娑橆潓缂備讲鍋撻柛灞绢嚔瑜版帗鏅查柛銉㈡櫆閹叉﹢姊洪崫銉ユ瀻闁硅櫕锕㈠濠氭偄閻撳骸鐎銈嗘⒐鐎氬酣鏁嶉崟顓狅紳?0闂傚倸鍊风欢姘焽瑜嶈灋闁哄啫鍊婚惌鍡椼€掑锝呬壕濡?
                            'menu_ids'=>array_column($menu_list,'menu_id')
                        );
                        $role = MerchantRoleService::add($role_data);
                        /******************闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎礈瑜忕敮娑㈡煕鐎ｃ劌濮傞柡灞炬礃缁绘繆绠涢弴鐘虫闂?*****************************/
                        $user_data = array(
                            'mer_id'=>$v['id'],//闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲海鎳撶€氱兘寮?
                            'number'=>'001',//缂傚倸鍊搁崐鎼佸磹閹间礁纾圭憸鐗堝笚閸嬪鏌￠崵鍫曨暒濮?
                            'nickname'=>isset($v['username'])?$v['username']:$v['title'],//闂傚倸鍊烽悞锕€顪冮崹顕呯劷闁秆勵殔缁€澶屸偓骞垮劚椤︻垶寮伴妷锔剧闁瑰瓨鐟ラ悘鈺呮煕濞嗗骏韬柡宀嬬秮婵偓闁斥晛鍟悵?
                            'username'=>$v['username'],//闂傚倸鍊烽悞锕€顪冮崹顕呯劷闁秆勵殔缁€澶屸偓骞垮劚椤︻垶寮伴妷锔剧闁瑰鍋熼。鏌ユ倵濮橆剦鐓奸柡灞诲姂瀵潙螖閳ь剚绂嶆ィ鍐╁仭?
                            'phone'=>$v['phone'],//闂傚倸鍊风粈浣虹礊婵犲偆鐒界憸鏃堛€侀弽顓炲耿婵＄偟绮弫鐘绘⒑?
                            'remark'=>'System default',//remark
                            'sort'=>1,//闂傚倸鍊风粈浣革耿闁秴纾块柕鍫濇处閺嗘粓鏌熼悜妯活梿濠?
                            'is_super'=>1,//闂傚倸鍊风粈渚€骞栭銈傚亾濮樺崬鍘寸€规洝顫夌€靛ジ寮堕幋鐘垫毎濠电偠鎻徊鍧楀磿閵堝绠氶柕澶嗘櫆閻撴洘绻涢幋婵嗚埞闁诲繘浜堕弻娑橆潩閿濆懍澹曢梻?闂?闂?
                            'is_admin'=>1,//闂傚倸鍊风粈渚€骞栭銈傚亾濮樺崬鍘寸€规洝顫夌€靛ジ寮堕幋鐘垫毎濠电偠鎻徊钘夛耿闁秴鐓濋柡鍐ㄧ墛閻擄綁鐓崶銊﹀暗缂佸妞介弻娑滅疀閹炬潙绐涚紓浣介哺閹稿骞忛崨鏉戜紶闁告洦鍓涙禍鐐翠繆閻愵亜鈧倝宕戦崨娣偓鍐╃節閸パ呯枃濠电娀娼ч悧濠勨偓姘皑閹叉瓕绠涢弴鐘靛箵?闂傚倸鍊风欢姘焽瑜嶈灋闁哄倹顑欏〒濠氭煙閻戞ɑ鈷掔紒鐘虫煥椤潡鎳滈棃娑橆潓缂備讲鍋撻柛灞绢嚔瑜版帗鏅查柛銉㈡櫆閹叉﹢姊洪崫銉ユ瀻闁硅櫕锕㈠濠氭偄閻撳骸鐎銈嗘⒐鐎氬酣鏁嶉崟顓狅紳?0闂傚倸鍊风欢姘焽瑜嶈灋闁哄啫鍊婚惌鍡椼€掑锝呬壕濡?
                            'role_ids'=>[$role['role_id']],
                            'password'=>123456,
                        );
                        $role = MerchantUserService::add($user_data);
                        break;
                    case 2://闂傚倷娴囬褎顨ラ幖浣稿偍婵犲﹤鐗嗙粈鍫熺節闂堟侗鍎愰柛瀣儔閺屟嗙疀閿濆懍绨荤紓浣稿閸嬨倕顕ｉ崼鏇為唶妞ゆ劦婢€閸戜粙鎮?
                        $v->auth_state=2;
                        $v->auth_msg=$param['auth_msg'];
                        break;
                }
                $v->save();
            }
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        $param['ids'] = $ids;
        MerchantCache::clear();
        return $param;
    }
    /**
     * @title: 闂傚倸鍊风粈渚€骞夐敓鐘茬闁告縿鍎抽惌鎾绘煕椤愶絾澶勯柡浣稿€归妵鍕箳閹存繍浠鹃梺绋款儍閸旀垿寮婚弴鐔虹瘈闊洦娲滈弳鐘绘⒑缂佹ɑ灏版繛鑼枛瀵鎮㈤搹鍦紲濠碘槅鍨伴幖顐︼綖閸績鏀芥い鏃€鏋婚崗顒傜磼閻樿櫕灏柣锝囧厴椤㈡盯鎮欓幓鎺曗偓鍨攽鎺抽崐鏇㈡晝閵堝鐤炬繛鍡樻尰閳锋垶鎱ㄩ悷鐗堟悙闁诲繆鏅犻弻锝夊Χ閸涱喖鏋犲?
     * @author闂傚倸鍊烽悞锔锯偓绗涘懐鐭欓柟鐑樻煥閺嬪牏鈧箍鍎遍幉姗€鏁愰崨鍌涙瀹曘劑顢橀悪鈧Σ鐗堢節閻㈤潧浠﹂柛銊ュ閸掓帗鎯旈敐鍥╋紴?
     * @date闂?025/9/14
     * @param $param
     * @return mixed
     */
    public static function userAdd($param)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        $param['create_uid']  = member_id();
        $param['create_time'] = datetime();
        $param['member_id'] = member_id();
        $param['username'] =$param['phone'];
        // 闂傚倷娴囬褔鏌婇敐鍜佺劷鐟滃秷鐏嬪┑鐐村灟閸ㄦ椽宕?
        $param['password'] = password_hash(123456, PASSWORD_BCRYPT);
//        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            if(isset($param['id']) && $param['id']){
                $model = $model->append(['image_ids'])->find($param['id']);
                if($model){
                  relation_update($model, $model['image_ids'], file_ids($param['images']), 'files', ['file_type' => 'image']);
                  $param['auth_state'] = MerchantModel::getAuthState('wait',1);
                  $edit_res=  $model->where('id',$param['id'])->update($param);
                }
            }else{
                $model->save($param);
                // 婵犵數濮烽弫鎼佸磿閹寸姷绀婇柍褜鍓氶妵鍕即閸℃顏柛娆忕箻閺岋綁骞囬浣瑰創濠碘槅鍋呴敃銏ゅ蓟濞戙垹唯妞ゆ牜鍋為宥咁渻?
                if (isset($param['images']) && $param['images']) {
                    $model->files()->saveAll(file_ids($param['images']), ['file_type' => 'image'], true);
                }
            }
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }
        if (isset($errmsg)) {
            exception($errmsg);
        }
        return $param;
    }
     /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犳粓鎳ｉ幇鐗堢厱闁靛鍔嶇涵楣冩煛鐎ｎ剙鏋涢柡?
     *
     * @param int|array $ids   闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插閸嬫捇骞?
     * @param array     $param 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犳粓鎳ｉ幇鐗堢厱闁靛鍨抽崚鏉款熆瑜庤ぐ鍐箒?
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        unset($param[$pk], $param['ids']);
        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            $user_edit = [];
            // 闂傚倷娴囬褔鏌婇敐鍜佺劷鐟滃秷鐏嬪┑鐐村灟閸ㄦ椽宕?
            if (isset($param['password'])) {
                $param['password'] = password_hash($param['password'], PASSWORD_BCRYPT);
                $user_edit['password'] = $param['password'];
            }
            if (isset($param['username'])) {
                $user_edit['username'] = $param['username'];
                $user_edit['nickname'] = isset($param['username'])?$param['username']:$param['title'];
            }
            if (isset($param['phone'])) {
                $user_edit['phone'] = $param['phone'];
            }
            if (isset($param['phone'])) {
                $user_edit['phone'] = $param['phone'];
            }
            if(count($user_edit)>0){
                $edit_pwd = Db::name('merchant_user')
                    ->where('is_delete',0)
                    ->where('is_disable',0)
                    ->where('is_admin',1)
                    ->whereIn('mer_id',$ids)
                    ->update($user_edit);
            }
            $res = $model->where($pk, 'in', $ids)->update($param);
            if (empty($res)) {
                exception();
            }
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }
        if (isset($errmsg)) {
            exception($errmsg);
        }
        $param['ids'] = $ids;
        MerchantCache::del($ids);
        return $param;
    }
    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插娴犻亶鏌＄仦鍓ф创妤犵偞甯″顒佹償閹惧鈧増绻?
     *
     * @param array $ids  闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕鍗灻洪妸锔锯枖鐎广儱顦伴悡銉︾箾閹寸伝顏堫敂椤忓牊鐓曞┑鐘插閸嬫捇骞?
     * @param bool  $real 闂傚倸鍊风粈渚€骞栭銈傚亾濮樺崬鍘寸€规洝顫夌€靛ジ寮堕幋鐘垫毎濠电偞鎸婚崺鍐磻閹剧粯鐓欐い鏍ㄧ閸犳﹢鏌熼鍝勭仾妞わ妇澧楅幆鏃堝閿涘嫰鎷婚梻鍌氬€风粈渚€骞夐敍鍕殰闁绘劕顕粻楣冩煃瑜滈崜姘辨崲?
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new MerchantModel();
        $pk = $model->getPk();
        if ($real) {
            $res = $model->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $update['auth_state']=2;
            $update['auth_msg']="闂傚倸鍊峰ù鍥敋閺嶎厼绐楅柟鎹愵嚙缁€澶屸偓鍏夊亾闁告洦鍋嗛ˇ鈺呮⒑閹稿海鈽夐悗姘煎弮瀹曟垿寮介妸锝勭盎闂佽澹嬮弲娑㈠焵椤戞儳鈧繂鐣烽妷鈺佺劦妞ゆ帒瀚埛鎴︽煠婵劕鈧洘绂掑鍕箚妞ゆ劧缍嗗▓婊勵殽閻愬樊妯€妤犵偛閰ｉ幐濠冨緞瀹€鈧崢顒勬⒒娴ｅ憡鎯堢紒澶嬬叀瀹曟繃鎯旈埄鍐╃槑闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕閬嵥囬柆宥呯煑闁哄洨鍠嗘禍婊堟煙閼割剙濡烽柛瀣崌閹煎綊顢曢敐鍡欐殽";
            $res = $model->where($pk, 'in', $ids)->update($update);
        }
        if (empty($res)) {
            exception();
        }
        $update['ids'] = $ids;
        MerchantCache::del($ids);
        return $update;
    }

    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕閬嵥囬婊呯＜闁冲搫鎳忛崑锝夋煕濠靛棗顏柟顖氱墢閳?
     * @Author: 闂傚倸鍊风粈渚€骞栭銈傚亾濮樼厧寮鐐村姍楠炴牗鎷呴崫銉︾劸婵犲痉鏉库偓鎾绘倿閿斿墽绀?
     * @DateTime:2025-02-20 15:42
     * @param $mer_id 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸鍘滈崑鎾剁磼?
     * @param $money 闂傚倸鍊峰ù鍥Υ閳ь剟鏌涚€ｎ偅宕岄柟顔肩秺瀹曨偊宕熼浣稿壍闁诲孩顔栭崰妤佺箾婵犲偆娼栨繛宸簻閻顭跨捄鐚村姛濞寸厧鑻—?
     * @param $title 闂傚倸鍊峰ù鍥Υ閳ь剟鏌涚€ｎ偅宕岄柟顔肩秺瀹曨偊宕熼浣稿壍闁诲孩顔栭崰妤佺箾婵犲洤钃熸繛鎴烇供濞尖晜銇勯幒鎴濃偓鎰板Χ閸ャ劌浠?
     * @param $data_id 闂傚倸鍊烽懗鍫曗€﹂崼銏″床闁规壆澧楅崑顏堟煕閵夛絽濡挎い鈺冨厴閺屾盯顢曢悩鑼痪闂佺顑嗛敃銏ゅ蓟瀹ュ鏁冮柨婵嗘閻?
     * @param $remark 濠电姷鏁告慨浼村垂閻撳簶鏋栨繛鎴炩棨濞差亝鏅濋柛灞剧閻?
     */
    public static function recharge($mer_id=null,$money=0,$title="",$data_id=null,$remark=null)
    {
        //闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸鍘滈崑鎾剁磼?
        if(!$mer_id){
            $mer_id = mer_id(true);
        }
        //闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎礈瑜忕敮娑㈡煕鐎ｃ劌濮傞柡灞炬礃缁绘繆绠涢弴鐘虫闂備胶绮幐璇差渻鐎?
        $create_uid = operate_user_id();
        $merModel = new MerchantModel();
        $mer_money = $merModel->where('id',$mer_id)->field('id,mer_money')->find();

        $model = new MerchantBillModel();
        $pk = $model->getPk();
        $param = array(
            'create_uid'=>$create_uid,
            'create_time'=>datetime(),
            'mer_id'=>$mer_id,
            'money'=>$money,
            'type'=>MerchantBillModel::getType('RECHARGE',1),
            'data_id'=>$data_id,
            'title'=>$title,
            'remark'=>$remark,
        );
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',$mer_id)->update([
                'mer_money'=>bcadd($mer_money['mer_money'],$money,2)
            ]);
            if (empty($res)) {
                exception('Recharge failed');
            }
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        return $param;
    }

    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕閬嵥囬婊呬笉闁汇垹鎲￠悡娆撴⒑椤撱劎鐣遍柡瀣〒缁?
     * @Author: 闂傚倸鍊风粈渚€骞栭銈傚亾濮樼厧寮鐐村姍楠炴牗鎷呴崫銉︾劸婵犲痉鏉库偓鎾绘倿閿斿墽绀?
     * @DateTime:2025-02-20 15:42
     * @param $money 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶嬩繆閵堝懏鍣界€规挷绶氶弻娑㈠Ψ椤旂厧顫梺缁樻尭閸婂潡寮婚悢鍛婄秶闁诡垎鍛掗梻?
     * @param $title 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶嬩繆閵堝懏鍣界€规挷绶氶弻娑㈠Ψ椤旂厧顫╅柣鐔哥懕婵″洭鍩€椤掆偓缁犲秹宕曢崡鐐╂灃?
     * @param $data_id 闂傚倸鍊烽懗鍫曗€﹂崼銏″床闁规壆澧楅崑顏堟煕閵夛絽濡挎い鈺冨厴閺屾盯顢曢悩鑼痪闂佺顑嗛敃銏ゅ蓟瀹ュ鏁冮柨婵嗘閻?
     * @param $remark 濠电姷鏁告慨浼村垂閻撳簶鏋栨繛鎴炩棨濞差亝鏅濋柛灞剧閻?
     */
    public static function withdrawal($money=0,$title="",$data_id=null,$remark=null)
    {
        $merModel = new MerchantModel();
        $mer_money = $merModel->where('id',mer_id())->field('id,mer_money')->find();
        if($mer_money['mer_money']<$money){
            exception('Insufficient balance: ' . $mer_money['mer_money'] . ', unable to withdraw');
        }
        if($money <=0){
            exception('Invalid amount, withdraw failed');
        }
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        $param = array(
            'create_uid'=>operate_user_id(),
            'create_time'=>datetime(),
            'mer_id'=>mer_id(true),
            'money'=>-$money,//闂傚倸鍊烽懗鍫曞箠閹剧粯鍊舵繝闈涚墢閻挾鈧娲栧ú銊х矆婵犲洦鐓涢柛鎰╁妿婢ф盯鏌ｉ幘鍐测偓鍧楀蓟閻斿憡缍囬柟顖嗗懏袙闂?
            'type'=>MerchantBillModel::getType('WITHDRAWAL',1),
            'data_id'=>$data_id,
            'title'=>$title,
            'remark'=>$remark,
        );
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',mer_id())->update(['mer_money'=>bcsub($mer_money['mer_money'],$money,2)]);
            if (empty($res)) {
                exception('Withdrawal failed');
            }
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        return $param;
    }


    /**
     * 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸绠嶉崕閬嵥囬婊呯＜闁冲搫鍊舵禍婊堟煛瀹ュ啫濮€濠㈣蓱閹?
     * @Author: 闂傚倸鍊风粈渚€骞栭銈傚亾濮樼厧寮鐐村姍楠炴牗鎷呴崫銉︾劸婵犲痉鏉库偓鎾绘倿閿斿墽绀?
     * @DateTime:2025-02-20 18:42
     * @param $money 婵犵數濮烽弫鎼佸磻閻愬搫鍨傞柣銏犳啞閸嬪鏌熼悧鍫熺凡闁搞劌鍊块弻娑樼暆閳ь剟宕戦悙鐑樺亗闁稿瞼鍋為悡娆撳级閸喎鍔ら柣锔界矒閺?
     * @param $title 婵犵數濮烽弫鎼佸磻閻愬搫鍨傞柣銏犳啞閸嬪鏌熼悧鍫熺凡闁搞劌鍊块弻娑樼暆閳ь剟宕戝☉姘枂闁挎棃鏁崑鎾荤嵁閸喖濮庡┑?
     * @param $data_id 闂傚倸鍊烽懗鍫曗€﹂崼銏″床闁规壆澧楅崑顏堟煕閵夛絽濡挎い鈺冨厴閺屾盯顢曢悩鑼痪闂佺顑嗛敃銏ゅ蓟瀹ュ鏁冮柨婵嗘閻?
     * @param $remark 濠电姷鏁告慨浼村垂閻撳簶鏋栨繛鎴炩棨濞差亝鏅濋柛灞剧閻?
     * @param $mer_id 闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲海鎳撶€氱兘寮?
     * @param $exce 闂備浇顕х€涒晠顢欓弽顓炵獥闁哄稁鍘肩壕褰掓煙闂傚鍔嶉柛瀣樀閺屾盯顢曢敐鍡欘槬闂佸憡鐟ラ敃銉╁Φ閸曨垰鍐€闁靛ě灞炬闂備胶顭堟鎼佹晝椤忓牆钃熼柣鏃囧亹閸欐洟鏌熺紒妯虹瑨濠殿噯绠撳?
     */
    public static function consumption($money=0,$title="",$data_id=null,$remark=null,$mer_id=null,$exce = true)
    {
        //闂傚倸鍊风粈渚€骞楀鍫濈獥閹肩补鍨惧☉妯滄棃宕担瑙勬珦闁诲骸鍘滈崑鎾剁磼?
        if(!$mer_id){
            $mer_id = mer_id(true);
        }
        //闂傚倸鍊风粈渚€骞夐敍鍕殰婵°倕鍟伴惌娆撴煙鐎电啸缁惧彞绮欓弻鐔煎礈瑜忕敮娑㈡煕鐎ｃ劌濮傞柡灞炬礃缁绘繆绠涢弴鐘虫闂備胶绮幐璇差渻鐎?
        $create_uid = operate_user_id();
        $model = new MerchantBillModel();
        $pk = $model->getPk();
        // 闂傚倸鍊风粈渚€骞夐敓鐘茬鐟滅増甯掗崹鍌炴煟濡も偓閻楀﹪宕ｈ箛娑欑厓闁告繂瀚埀顒€顭烽妴鍛村蓟閵夛箑鈧敻鏌ㄥ┑鍡欏嚬缂併劎绮妵?
        $model->startTrans();
        try {
            $merModel = new MerchantModel();
            $mer_money = $merModel->where('id',$mer_id)->field('id,mer_money')->find();
            if($mer_money['mer_money']<$money && $exce){
                exception('Insufficient balance: ' . $mer_money['mer_money'] . ', please recharge first');
            }
            if($money<=0 && $exce){
                exception('Invalid amount, payment failed');
            }
            $param = array(
                'create_uid'=>$create_uid,
                'create_time'=>datetime(),
                'mer_id'=>$mer_id,
                'money'=>-$money,//闂傚倸鍊烽懗鍫曞箠閹剧粯鍊舵繝闈涚墢閻挾鈧娲栧ú銊х矆婵犲洦鐓涢柛鎰╁妿婢ф盯鏌ｉ幘鍐测偓鍧楀蓟閻斿憡缍囬柟顖嗗懏袙闂?
                'type'=>MerchantBillModel::getType('CONSUMPTION',1),
                'data_id'=>$data_id,
                'title'=>$title,
                'remark'=>$remark,
            );
            $model->save($param);
            $id = $model->$pk;
            if (empty($id)) {
                exception();
            }
            $param[$pk] = $id;
            $res = $merModel->where('id',$mer_id)->update(['mer_money'=>bcsub($mer_money['mer_money'],$money,2)]);
            if (empty($res) && $exce) {
                exception('Payment failed');
            }
            // 闂傚倸鍊风粈浣革耿鏉堚晛鍨濇い鏍仜缁€澶愭煙閻戞ê鐒炬繛灏栨櫊閺屻劑寮崒娑欑彧濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->commit();
        } catch (\Exception $e) {
            $errmsg = $e->getMessage();
            // 闂傚倸鍊烽悞锕傚箖閸洖纾块柟鎯版绾惧鏌ｉ幇鐗堟锭闁搞劍绻堥幃妤€鈽夊▎妯煎姺濡炪倐鏅滈悡锟犲箖濡ゅ懏鏅查幖瀛樼箘閻╁骸顪?
            $model->rollback();
        }

        if (isset($errmsg)) {
            exception($errmsg);
        }

        return $param;
    }

    /**
     * 闂備浇宕甸崰鎰版偡鏉堚晛绶ゅΔ锝呭暞閸婇潧霉閻樺樊鍎忕紒鈧崱娑欑厱闁斥晛鍠氶悞浠嬫煃瑜滈崗娑氬垝濞嗗浚鍤曟い鏇楀亾鐎规洖銈稿鎾偆閸屾粌鎯為梻鍌欐祰閸嬫劙宕㈣瀹曨垶顢曢埛?
     *
     * @param array $param
     * @return array
     */
    private static function resolveAnalyticsRange($param = [])
    {
        $filterType = $param['filter_type'] ?? 'days';
        $supportedDays = [7, 15, 30];
        $maxCustomDays = 93;

        if ($filterType === 'month') {
            $month = trim($param['month'] ?? '');
            if (preg_match('/^\\d{4}-\\d{2}$/', $month)) {
                $monthStart = date('Y-m-01 00:00:00', strtotime($month . '-01'));
                $monthEnd = date('Y-m-t 23:59:59', strtotime($month . '-01'));
                $endTime = strtotime($monthEnd) > time() ? date('Y-m-d H:i:s') : $monthEnd;

                return [
                    'filter_type' => 'month',
                    'days' => self::calcDays($monthStart, $endTime),
                    'month' => $month,
                    'start_time' => $monthStart,
                    'end_time' => $endTime,
                    'label' => $month,
                ];
            }
        }

        if ($filterType === 'custom') {
            $startDate = trim($param['start_date'] ?? '');
            $endDate = trim($param['end_date'] ?? '');
            if ($startDate && $endDate) {
                $startTime = $startDate . ' 00:00:00';
                $endTime = $endDate . ' 23:59:59';
                if (strtotime($startTime) !== false && strtotime($endTime) !== false && strtotime($startTime) <= strtotime($endTime)) {
                    $days = self::calcDays($startTime, $endTime);
                    if ($days > $maxCustomDays) {
                        $endTime = date('Y-m-d 23:59:59', strtotime($startTime . ' +' . ($maxCustomDays - 1) . ' days'));
                        $days = self::calcDays($startTime, $endTime);
                        $endDate = date('Y-m-d', strtotime($endTime));
                    }
                    return [
                        'filter_type' => 'custom',
                        'days' => $days,
                        'month' => '',
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'label' => $startDate . ' 闂?' . $endDate,
                    ];
                }
            }
        }

        $days = intval($param['days'] ?? 7);
        if (!in_array($days, $supportedDays, true)) {
            $days = 7;
        }

        $endTime = date('Y-m-d 23:59:59');
        $startTime = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        return [
            'filter_type' => 'days',
            'days' => $days,
            'month' => '',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'label' => 'Recent ' . $days . ' days',
        ];
    }

    /**
     * 闂備浇宕垫慨宕囨閵堝洦顫曢柡鍥ュ灪閸嬧晛鈹戦悩宕囶暡闁搞倕锕ラ妵鍕疀閹炬惌妫炴繝娈垮暙閸愬墽鍞甸梺鎼炲妽缁嬫帒鈻嶉崱娑欑厸?
     *
     * @param string $startTime
     * @param string $endTime
     * @return int
     */
    private static function calcDays($startTime, $endTime)
    {
        $diff = strtotime($endTime) - strtotime($startTime);
        if ($diff <= 0) {
            return 1;
        }

        return intval(floor($diff / 86400)) + 1;
    }

    /**
     * 闂傚倸鍊搁崐椋庣矆娴ｅ壊鍤曢柛顐ｆ礀閸ㄥ倿鏌涜椤﹀綊鎮滈挊澶岋紲闂佺粯鍔栬ぐ鍐船閵娾晜鈷戠紓浣股戠亸顓熺箾閹绢噮妫戞繛鎴犳暬閸┾偓妞ゆ帒瀚埛?     *
     * @param mixed $value
     * @return float
     */
    private static function toFloat($value)
    {
        return round(floatval($value), 2);
    }
}
