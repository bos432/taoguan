<?php


namespace app\common\service\inspection;

use think\facade\Log;
use think\facade\Config;
use think\facade\Request;
use app\common\cache\inspection\InspectionUserLogCache;
use app\common\model\inspection\InspectionUserLogModel;
use app\common\service\utils\Utils;
use app\common\service\system\SettingService;

/**
 * 用户日志
 */
class InspectionUserLogService
{
    /**
     * 用户日志列表
     *
     * @param array  $where 条件
     * @param int    $page  分页
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function list($where = [], $page = 1, $limit = 10, $order = [], $field = '')
    {
        $model = new InspectionUserLogModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',ins_id,ins_user_id,menu_id,request_url,request_method,request_ip,request_region,request_isp,response_code,response_msg,create_time';
        }
        if (empty($order)) {
            $order = [$pk => 'desc'];
        }

        if (ins_user_hide_where()) {
            $where[] = ins_user_hide_where();
        }
        $where[] =['ins_id','=',ins_id()];
        $with = $append = $hidden = $field_no = [];
        if (strpos($field, 'ins_user_id') !== false) {
            $with[]   = $hidden[] = 'user';
            $append[] = 'nickname';
            $append[] = 'username';
        }
        if (strpos($field, 'menu_id') !== false) {
            $with[]   = $hidden[] = 'menu';
            $append[] = 'menu_name';
            $append[] = 'menu_url';
        }
        $fields = explode(',', $field);
        foreach ($fields as $k => $v) {
            if (in_array($v, $field_no)) {
                unset($fields[$k]);
            }
        }
        $field = implode(',', $fields);

        $count = $model->where($where)->count();
        $pages = 0;
        if ($page > 0) {
            $model = $model->page($page);
        }
        if ($limit > 0) {
            $model = $model->limit($limit);
            $pages = ceil($count / $limit);
        }
        $list = $model->field($field)->where($where)
            ->with($with)->append($append)->hidden($hidden)
            ->order($order)->select()->toArray();

        $log_types = SettingService::logTypes();

        return compact('count', 'pages', 'page', 'limit', 'list', 'log_types');
    }

    /**
     * 用户日志信息
     *
     * @param int  $id   日志id
     * @param bool $exce 不存在是否抛出异常
     * 
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        $info = InspectionUserLogCache::get($id);
        if (empty($info)) {
            $model = new InspectionUserLogModel();

            $info = $model->where('ins_id',ins_id())->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('用户日志不存在：' . $id);
                }
                return [];
            }
            $info = $info
                ->append(['nickname', 'username', 'menu_name', 'menu_url'])
                ->hidden(['user', 'menu'])
                ->toArray();

            InspectionUserLogCache::set($id, $info);
        }

        return $info;
    }

    /**
     * 用户日志添加
     *
     * @param array $param    日志数据
     * @param int   $log_type 日志类型：0登录，1操作，2退出
     * 
     * @return void
     */
    public static function add($param = [], $log_type = SettingService::LOG_TYPE_OPERATION)
    {
        // 用户日志记录是否开启
        if (user_log_switch()) {
            if ($log_type == SettingService::LOG_TYPE_LOGIN) {
                $param['response_code'] = 200;
                $param['response_msg']  = '登录成功';
            }
            if (($param['response_msg'] ?? '') == '退出成功') {
                $log_type = SettingService::LOG_TYPE_LOGOUT;
            }

            // 请求参数排除字段
            $request_param = Request::param();
            $param_without = Config::get('inspection.log_param_without', []);
            array_push($param_without, Config::get('inspection.token_name'));
            foreach ($param_without as $v) {
                unset($request_param[$v]);
            }

            $menu       = InspectionMenuService::info('', false);
            $ip_info    = Utils::ipInfo();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? Request::header('user-agent') ?? '';

            $param['ins_id']         = ins_id();
            $param['ins_user_id']         = ins_user_id();
            $param['menu_id']          = $menu['menu_id'] ?? 0;
            $param['log_type']         = $log_type;
            $param['request_method']   = Request::method();
            $param['request_url']      = $menu['menu_url'] ?? Request::baseUrl();
            $param['request_ip']       = $ip_info['ip'];
            $param['request_country']  = $ip_info['country'];
            $param['request_province'] = $ip_info['province'];
            $param['request_city']     = $ip_info['city'];
            $param['request_area']     = $ip_info['area'];
            $param['request_region']   = $ip_info['region'];
            $param['request_isp']      = $ip_info['isp'];
            $param['request_param']    = $request_param;
            $param['user_agent']       = substr($user_agent, 0, 1024);
            $param['create_time']      = datetime();

            $model = new InspectionUserLogModel();
            $model->save($param);
        }
    }

    /**
     * 用户日志修改
     *
     * @param array $ids   用户id
     * @param array $param 日志信息
     * 
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new InspectionUserLogModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = ins_user_id();
        $param['update_time'] = datetime();
        if (isset($param['request_param'])) {
            $param['request_param'] = serialize($param['request_param']);
        }

        $res = $model->where('ins_id',ins_id())->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        InspectionUserLogCache::del($ids);

        return $param;
    }

    /**
     * 用户日志删除
     *
     * @param array  $ids     日志id
     * @param bool   $real    是否真实删除
     * @param string $user_id 用户ID
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false, $user_id = '')
    {
        $model = new InspectionUserLogModel();
        $pk = $model->getPk();
        $ins_id = ins_id();
        $where = [[$pk, 'in', $ids]];
        if ($user_id !== '') {
            $where[] = ['ins_user_id', 'in', $user_id];
        }
        if ($real) {
            $res = $model->where('ins_id',$ins_id)->where($where)->delete();
        } else {
            $update = delete_update();
            $res = $model->where('ins_id',$ins_id)->where($where)->update($update);
        }

        if (empty($res)) {
            exception();
        }

        $update['ids'] = $ids;

        InspectionUserLogCache::del($ids);

        return $update;
    }

    /**
     * 用户日志清空
     * 
     * @param array $where 条件
     * 
     * @return array
     */
    public static function clear($where = [])
    {
        $model = new InspectionUserLogModel();
        $pk = $model->getPk();

        $where[] = [$pk, '>', 0];

        $count = $model->where('ins_id',ins_id())->where($where)->delete(true);

        $data['count'] = $count;

        return $data;
    }

    /**
     * 用户日志清除
     * 
     * @return void
     */
    public static function clearLog()
    {
        $setting = SettingService::info('log_save_time');
        if ($setting['log_save_time']) {
            $time = date('H');
            if (0 <= $time && $time <= 8) {
                $key = 'clear';
                $val = InspectionUserLogCache::get($key);
                if (empty($val)) {
                    $days = $setting['log_save_time'];
                    $date = date('Y-m-d H:i:s', strtotime("-{$days} day"));
                    $where = [['create_time', '<', $date]];
                    $res = InspectionUserLogService::clear($where);
                    $res['where'] = $where;

                    $log['log'] = 'user-log-clear';
                    $log['data'] = $res;
                    Log::write($log, 'timer');

                    InspectionUserLogCache::set($key, $days, 1800);
                }
            }
        }
    }
}
