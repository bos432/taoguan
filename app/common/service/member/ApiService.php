<?php


namespace app\common\service\member;

use think\facade\Config;
use app\common\cache\member\ApiCache;
use app\common\cache\member\GroupCache;
use app\common\model\member\ApiModel;
use app\common\model\member\GroupApisModel;
use app\common\service\merchant\MerchantIdentityService;
use app\common\service\setting\NoticePopupService;
use app\common\service\system\MobileAdminAccessService;

/**
 * 浼氬憳鎺ュ彛
 */
class ApiService
{
    /**
     * 娣诲姞淇敼瀛楁
     * @var array
     */
    public static $edit_field = [
        'api_id/d'   => '',
        'api_pid/d'  => 0,
        'api_name/s' => '',
        'api_url/s'  => '',
        'sort/d'     => 250,
    ];

    /**
     * 浼氬憳鎺ュ彛鍒楄〃
     *
     * @param string $type  tree鏍戝舰锛宭ist鍒楄〃
     * @param array  $where 鏉′欢
     * @param array  $order 鎺掑簭
     * @param string $field 瀛楁
     *
     * @return array
     */
    public static function list($type = 'tree', $where = [], $order = [], $field = '')
    {
        $model = new ApiModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',api_pid,api_name,api_url,sort,is_unlogin,is_unauth,is_unrate,is_disable';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'asc'];
        }

        $key = where_cache_key($type, $where, $order, $field);
        $data = ApiCache::get($key);
        if (empty($data)) {
            $data = $model->field($field)->where($where)->order($order)->select()->toArray();
            if ($type == 'tree') {
                $data = array_to_tree($data, $pk, 'api_pid');
            }
            ApiCache::set($key, $data);
        }

        return $data;
    }

    /**
     * 浼氬憳鎺ュ彛淇℃伅
     *
     * @param int|string $id   鎺ュ彛id銆乽rl
     * @param bool       $exce 涓嶅瓨鍦ㄦ槸鍚︽姏鍑哄紓甯?
     *
     * @return array|Exception
     */
    public static function info($id = '', $exce = true)
    {
        if (empty($id)) {
            $id = api_url();
        }

        $info = ApiCache::get($id);
        if (empty($info)) {
            $model = new ApiModel();
            $pk = $model->getPk();

            if (is_numeric($id)) {
                $where[] = [$pk, '=', $id];
            } else {
                $where[] = ['api_url', '=', $id];
                $where[] = where_delete();
            }

            $info = $model->where($where)->find();
            if (empty($info)) {
                if (!is_numeric($id)) {
                    $virtualInfo = MobileAdminAccessService::virtualApiInfo(strval($id));
                    if (!empty($virtualInfo)) {
                        return $virtualInfo;
                    }
                    $virtualInfo = MerchantIdentityService::virtualApiInfo(strval($id));
                    if (!empty($virtualInfo)) {
                        return $virtualInfo;
                    }
                    $virtualInfo = NoticePopupService::virtualApiInfo(strval($id));
                    if (!empty($virtualInfo)) {
                        return $virtualInfo;
                    }
                }
                if ($exce) {
                    exception('浼氬憳鎺ュ彛涓嶅瓨鍦細' . $id);
                }
                return [];
            }
            $info = $info->toArray();

            ApiCache::set($id, $info);
        }

        return $info;
    }

    /**
     * 浼氬憳鎺ュ彛娣诲姞
     *
     * @param array $param 鎺ュ彛淇℃伅
     *
     * @return array|Exception
     */
    public static function add($param)
    {
        $model = new ApiModel();
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

        ApiCache::clear();

        return $param;
    }

    /**
     * 浼氬憳鎺ュ彛淇敼
     *
     * @param int|array $ids   鎺ュ彛id
     * @param array     $param 鎺ュ彛淇℃伅
     *
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        $model = new ApiModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();

        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        ApiCache::clear();

        return $param;
    }

    /**
     * 浼氬憳鎺ュ彛鍒犻櫎
     *
     * @param array $ids  鎺ュ彛id
     * @param bool  $real 鏄惁鐪熷疄鍒犻櫎
     *
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        $model = new ApiModel();
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

        ApiCache::clear();

        return $update;
    }

    /**
     * 浼氬憳鎺ュ彛鍒嗙粍
     *
     * @param array  $where 鏉′欢
     * @param int    $page  椤垫暟
     * @param int    $limit 鏁伴噺
     * @param array  $order 鎺掑簭
     * @param string $field 瀛楁
     *
     * @return array
     */
    public static function group($where = [], $page = 1, $limit = 10,  $order = [], $field = '')
    {
        return GroupService::list($where, $page, $limit, $order, $field);
    }

    /**
     * 浼氬憳鎺ュ彛鍒嗙粍瑙ｉ櫎
     *
     * @param array $api_id    鎺ュ彛id
     * @param array $group_ids 鍒嗙粍id
     *
     * @return int
     */
    public static function groupRemove($api_id, $group_ids = [])
    {
        $where[] = ['api_id', 'in', $api_id];
        if (empty($group_ids)) {
            $group_ids = GroupApisModel::where($where)->column('group_id');
        }
        $where[] = ['group_id', 'in', $group_ids];

        $res = GroupApisModel::where($where)->delete();

        GroupCache::del($group_ids);

        return $res;
    }

    /**
     * 浼氬憳鎺ュ彛鍒楄〃
     *
     * @param string $type url鎺ュ彛url锛宨d鎺ュ彛id
     *
     * @return array
     */
    public static function apiList($type = 'url')
    {
        $key = 'api-' . $type;
        $list = ApiCache::get($key);
        if (empty($list)) {
            $model = new ApiModel();

            $column = 'api_url';
            if ($type == 'id') {
                $column = $model->getPk();
            }

            $list = $model->where([where_delete()])->column($column);
            $list = array_values(array_filter($list));
            if ($type == 'url') {
                $list = array_merge($list, array_column(MobileAdminAccessService::virtualApis(), 'api_url'));
                $list = array_merge($list, array_column(MerchantIdentityService::virtualApis(), 'api_url'));
                $list = array_merge($list, array_column(NoticePopupService::virtualApis(), 'api_url'));
                $list = array_values(array_unique(array_filter($list)));
            }
            sort($list);

            ApiCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 浼氬憳鎺ュ彛鍏嶇櫥鍒楄〃
     *
     * @param string $type url鎺ュ彛url锛宨d鎺ュ彛id
     *
     * @return array
     */
    public static function unloginList($type = 'url')
    {
        $key = 'unlogin-' . $type;
        $list = ApiCache::get($key);
        if (empty($list)) {
            $model = new ApiModel();

            $column = 'api_url';
            $api_is_unlogin = Config::get('api.api_is_unlogin', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($api_is_unlogin) {
                    $api_is_unlogin = $model->where('api_url', 'in', $api_is_unlogin)->column($column);
                }
            }

            $list = $model->where(where_delete(['is_unlogin', '=', 1]))->column($column);
            $list = array_merge($list, $api_is_unlogin);
            if ($type == 'url') {
                $list = array_merge($list, array_column(array_filter(MerchantIdentityService::virtualApis(), function ($item) {
                    return intval($item['is_unlogin'] ?? 0) === 1;
                }), 'api_url'));
                $list = array_merge($list, array_column(array_filter(NoticePopupService::virtualApis(), function ($item) {
                    return intval($item['is_unlogin'] ?? 0) === 1;
                }), 'api_url'));
            }
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            ApiCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 浼氬憳鎺ュ彛鍏嶆潈鍒楄〃
     *
     * @param string $type url鎺ュ彛url锛宨d鎺ュ彛id
     *
     * @return array
     */
    public static function unauthList($type = 'url')
    {
        $key = 'unauth-' . $type;
        $list = ApiCache::get($key);
        if (empty($list)) {
            $model = new ApiModel();

            $column = 'api_url';
            $api_is_unauth = Config::get('api.api_is_unauth', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($api_is_unauth) {
                    $api_is_unauth = $model->where('api_url', 'in', $api_is_unauth)->column($column);
                }
            }
            $api_is_unlogin = self::unloginList($type);

            $list = $model->where(where_delete(['is_unauth', '=', 1]))->column($column);
            $list = array_merge($list, $api_is_unlogin, $api_is_unauth);
            if ($type == 'url') {
                $list = array_merge($list, array_column(array_filter(MerchantIdentityService::virtualApis(), function ($item) {
                    return intval($item['is_unauth'] ?? 0) === 1;
                }), 'api_url'));
                $list = array_merge($list, array_column(array_filter(NoticePopupService::virtualApis(), function ($item) {
                    return intval($item['is_unauth'] ?? 0) === 1;
                }), 'api_url'));
            }
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            ApiCache::set($key, $list);
        }

        return $list;
    }

    /**
     * 浼氬憳鎺ュ彛鍏嶉檺鍒楄〃
     *
     * @param string $type url鎺ュ彛url锛宨d鎺ュ彛id
     *
     * @return array
     */
    public static function unrateList($type = 'url')
    {
        $key = 'unrate-' . $type;
        $list = ApiCache::get($key);
        if (empty($list)) {
            $model = new ApiModel();

            $column = 'api_url';
            $api_is_unrate = Config::get('api.api_is_unrate', []);
            if ($type == 'id') {
                $column = $model->getPk();
                if ($api_is_unrate) {
                    $api_is_unrate = $model->where('api_url', 'in', $api_is_unrate)->column($column);
                }
            }

            $list = $model->where(where_delete(['is_unrate', '=', 1]))->column($column);
            $list = array_merge($list, $api_is_unrate);
            $list = array_unique(array_filter($list));
            $list = array_values($list);
            sort($list);

            ApiCache::set($key, $list);
        }

        return $list;
    }
}
