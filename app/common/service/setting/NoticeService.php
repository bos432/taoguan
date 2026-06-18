<?php


namespace app\common\service\setting;

use app\common\cache\setting\NoticeCache;
use app\common\model\setting\NoticeModel;
use app\common\service\system\NoticeService as LegacyNoticeService;
use think\facade\Db;

/**
 * 通告管理
 */
class NoticeService
{
    private static $tableExistsMap = [];
    private static $columnExistsMap = [];
    private static $dbName = null;

    /**
     * 添加修改字段
     * @var array
     */
    public static $edit_field = [
        'notice_id/d'   => '',
        'image_id/d'    => 0,
        'type/d'        => 0,
        'popup_enabled/d' => 0,
        'popup_frequency/s' => 'once',
        'popup_scope/s' => 'all',
        'popup_sort/d' => 250,
        'popup_button_text/s' => '查看详情',
        'popup_jump_type/s' => 'detail',
        'popup_jump_value/s' => '',
        'content_type/s' => 'html',
        'title/s'       => '',
        'title_color/s' => '#606266',
        'start_time/s'  => '',
        'end_time/s'    => '',
        'desc/s'        => '',
        'content/s'     => '',
        'remark/s'      => '',
        'sort/d'        => 250,
    ];

    private static function baseFieldList(): array
    {
        return [
            'notice_id',
            'image_id',
            'type',
            'title',
            'title_color',
            'start_time',
            'end_time',
            'is_disable',
            'desc',
            'content',
            'remark',
            'sort',
            'create_time',
            'update_time',
        ];
    }

    private static function popupFieldList(): array
    {
        return [
            'popup_enabled',
            'popup_frequency',
            'popup_scope',
            'popup_sort',
            'popup_button_text',
            'popup_jump_type',
            'popup_jump_value',
            'content_type',
        ];
    }

    private static function getDatabaseName(): string
    {
        if (self::$dbName !== null) {
            return self::$dbName;
        }

        try {
            $rows = Db::query('SELECT DATABASE() AS db_name');
            self::$dbName = strval($rows[0]['db_name'] ?? '');
        } catch (\Throwable $e) {
            self::$dbName = '';
        }

        return self::$dbName;
    }

    private static function getTableName(string $table): string
    {
        $default = config('database.default', 'mysql');
        $prefix = config('database.connections.' . $default . '.prefix', 'ya_');
        return $prefix . $table;
    }

    private static function tableExists(string $table): bool
    {
        if (array_key_exists($table, self::$tableExistsMap)) {
            return self::$tableExistsMap[$table];
        }

        $dbName = self::getDatabaseName();
        if ($dbName === '') {
            self::$tableExistsMap[$table] = false;
            return false;
        }

        try {
            $rows = Db::query(
                'SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$dbName, self::getTableName($table)]
            );
            self::$tableExistsMap[$table] = intval($rows[0]['total'] ?? 0) > 0;
        } catch (\Throwable $e) {
            self::$tableExistsMap[$table] = false;
        }

        return self::$tableExistsMap[$table];
    }

    private static function columnsExist(string $table, array $columns): bool
    {
        $columns = array_values(array_unique(array_filter(array_map(function ($column) {
            return trim((string) $column);
        }, $columns))));
        if (empty($columns)) {
            return true;
        }

        $cacheKey = $table . ':' . implode(',', $columns);
        if (array_key_exists($cacheKey, self::$columnExistsMap)) {
            return self::$columnExistsMap[$cacheKey];
        }

        if (!self::tableExists($table)) {
            self::$columnExistsMap[$cacheKey] = false;
            return false;
        }

        $dbName = self::getDatabaseName();
        if ($dbName === '') {
            self::$columnExistsMap[$cacheKey] = false;
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $bindings = array_merge([$dbName, self::getTableName($table)], $columns);

        try {
            $rows = Db::query(
                "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN ({$placeholders})",
                $bindings
            );
            self::$columnExistsMap[$cacheKey] = intval($rows[0]['total'] ?? 0) === count($columns);
        } catch (\Throwable $e) {
            self::$columnExistsMap[$cacheKey] = false;
        }

        return self::$columnExistsMap[$cacheKey];
    }

    public static function hasSettingNoticeTable(): bool
    {
        return self::tableExists('setting_notice');
    }

    public static function hasSettingNoticeReadTable(): bool
    {
        return self::tableExists('setting_notice_read');
    }

    public static function supportsPopupFields(): bool
    {
        return self::columnsExist('setting_notice', self::popupFieldList());
    }

    private static function normalizeFieldString(string $field = ''): string
    {
        $fields = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, explode(',', $field)))));

        if (!self::supportsPopupFields()) {
            $fields = array_values(array_filter($fields, function ($item) {
                return !in_array($item, self::popupFieldList(), true);
            }));
        }

        return implode(',', $fields);
    }

    private static function normalizeInfo(array $info = []): array
    {
        if (empty($info)) {
            return [];
        }

        $defaults = [
            'popup_enabled' => 0,
            'popup_frequency' => 'once',
            'popup_frequency_name' => SettingService::popupFrequencies('once'),
            'popup_scope' => 'all',
            'popup_scope_name' => SettingService::popupScopes('all'),
            'popup_sort' => 250,
            'popup_button_text' => '查看详情',
            'popup_jump_type' => 'detail',
            'popup_jump_type_name' => SettingService::popupJumpTypes('detail'),
            'popup_jump_value' => '',
            'content_type' => 'html',
        ];

        $info = array_merge($defaults, $info);
        $info['type_name'] = $info['type_name'] ?? SettingService::noticeTypes($info['type'] ?? '');
        $info['popup_frequency_name'] = SettingService::popupFrequencies($info['popup_frequency'] ?? 'once');
        $info['popup_scope_name'] = SettingService::popupScopes($info['popup_scope'] ?? 'all');
        $info['popup_jump_type_name'] = SettingService::popupJumpTypes($info['popup_jump_type'] ?? 'detail');

        return $info;
    }

    private static function normalizeLegacyParam(array $param = []): array
    {
        $allowed = array_flip(self::baseFieldList());
        return array_intersect_key($param, $allowed);
    }

    private static function normalizeStorageParam(array $param = []): array
    {
        $allowed = self::baseFieldList();
        if (self::supportsPopupFields()) {
            $allowed = array_merge($allowed, self::popupFieldList());
        }
        return array_intersect_key($param, array_flip($allowed));
    }

    /**
     * 通告列表
     *
     * @param array  $where 条件
     * @param int    $page  页数
     * @param int    $limit 数量
     * @param array  $order 排序
     * @param string $field 字段
     * 
     * @return array 
     */
    public static function list($where = [], $page = 1, $limit = 10, $order = [], $field = '')
    {
        $field = self::normalizeFieldString($field);
        if (!self::hasSettingNoticeTable()) {
            $data = LegacyNoticeService::list($where, $page, $limit, $order, $field);
            $data['types'] = SettingService::noticeTypes();
            return $data;
        }

        $model = new NoticeModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $fields = array_merge([$pk, 'image_id', 'type'], self::baseFieldList());
            if (self::supportsPopupFields()) {
                $fields = array_merge($fields, self::popupFieldList());
            }
            $field = implode(',', array_values(array_unique($fields)));
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'desc'];
        }

        $with = $append = $hidden = $field_no = [];
        if (strpos($field, 'image_id') !== false) {
            $with[]   = $hidden[] = 'image';
            $append[] = 'image_url';
        }
        if (strpos($field, 'type') !== false) {
            $append[] = 'type_name';
        }
        if (strpos($field, 'popup_frequency') !== false) {
            $append[] = 'popup_frequency_name';
        }
        if (strpos($field, 'popup_scope') !== false) {
            $append[] = 'popup_scope_name';
        }
        if (strpos($field, 'popup_jump_type') !== false) {
            $append[] = 'popup_jump_type_name';
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

        $types = SettingService::noticeTypes();

        return compact('count', 'pages', 'page', 'limit', 'list', 'types');
    }

    /**
     * 通告信息
     *
     * @param int  $id   通告id
     * @param bool $exce 不存在是否抛出异常
     * 
     * @return array|Exception
     */
    public static function info($id, $exce = true)
    {
        if (!self::hasSettingNoticeTable()) {
            return self::normalizeInfo(LegacyNoticeService::info($id, $exce));
        }

        $info = NoticeCache::get($id);
        if (empty($info)) {
            $model = new NoticeModel();

            $info = $model->find($id);
            if (empty($info)) {
                if ($exce) {
                    exception('通告不存在：' . $id);
                }
                return [];
            }
            $info = $info->append(['image_url', 'type_name', 'popup_frequency_name', 'popup_scope_name', 'popup_jump_type_name'])->hidden(['image'])->toArray();
            $info = self::normalizeInfo($info);

            NoticeCache::set($id, $info);
        }

        return self::normalizeInfo($info);
    }

    /**
     * 通告添加
     *
     * @param array $param 通告信息
     * 
     * @return array|Exception
     */
    public static function add($param)
    {
        if (!self::hasSettingNoticeTable()) {
            return LegacyNoticeService::add(self::normalizeLegacyParam($param));
        }

        $param = self::normalizeStorageParam($param);
        $model = new NoticeModel();
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
     * 通告修改
     *
     * @param int|array $ids   通告id
     * @param array     $param 通告信息
     * 
     * @return array|Exception
     */
    public static function edit($ids, $param = [])
    {
        if (!self::hasSettingNoticeTable()) {
            return LegacyNoticeService::edit($ids, self::normalizeLegacyParam($param));
        }

        $param = self::normalizeStorageParam($param);
        $model = new NoticeModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid']  = user_id();
        $param['update_time'] = datetime();

        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        NoticeCache::del($ids);

        return $param;
    }

    /**
     * 通告删除
     *
     * @param array $ids  通告id
     * @param bool  $real 是否真实删除
     * 
     * @return array|Exception
     */
    public static function dele($ids, $real = false)
    {
        if (!self::hasSettingNoticeTable()) {
            return LegacyNoticeService::dele($ids, $real);
        }

        $model = new NoticeModel();
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

        NoticeCache::del($ids);

        return $update;
    }
}
