<?php

namespace app\common\service\setting;

use app\common\model\setting\AccordAcceptModel;
use think\facade\Db;
use think\facade\Request;

class AccordAcceptService
{
    private static $tableReady = null;

    private static function ensureStorageTable(): void
    {
        if (self::$tableReady === true) {
            return;
        }

        $default = config('database.default', 'mysql');
        $prefix = config('database.connections.' . $default . '.prefix', 'ya_');
        $table = $prefix . 'setting_accord_accept';
        $exists = Db::query("SHOW TABLES LIKE '{$table}'");
        if (empty($exists)) {
            Db::execute("
                CREATE TABLE `{$table}` (
                    `id` int unsigned NOT NULL AUTO_INCREMENT,
                    `accord_id` int unsigned NOT NULL DEFAULT 0,
                    `accord_unique` varchar(100) NOT NULL DEFAULT '',
                    `accord_name` varchar(255) NOT NULL DEFAULT '',
                    `member_id` int unsigned NOT NULL DEFAULT 0,
                    `scene` varchar(50) NOT NULL DEFAULT '',
                    `platform` tinyint unsigned NOT NULL DEFAULT 0,
                    `application` tinyint unsigned NOT NULL DEFAULT 0,
                    `request_ip` varchar(64) NOT NULL DEFAULT '',
                    `user_agent` varchar(255) NOT NULL DEFAULT '',
                    `is_disable` tinyint unsigned NOT NULL DEFAULT 0,
                    `is_delete` tinyint unsigned NOT NULL DEFAULT 0,
                    `create_uid` int unsigned NOT NULL DEFAULT 0,
                    `update_uid` int unsigned NOT NULL DEFAULT 0,
                    `delete_uid` int unsigned NOT NULL DEFAULT 0,
                    `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                    `update_time` datetime DEFAULT NULL,
                    `delete_time` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_member_unique_scene` (`member_id`, `accord_unique`, `scene`),
                    KEY `idx_accord_unique` (`accord_unique`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        self::$tableReady = true;
    }

    public static function sceneTitle($scene = '')
    {
        $map = [
            'login' => '登录同意',
            'order_confirm' => '订单确认',
            'merchant_apply' => '商家入驻',
            'default' => '默认场景',
        ];

        return $map[$scene] ?? $scene;
    }

    public static function acceptMemberAccords($memberId = 0, $accordUniques = [], $scene = 'default')
    {
        self::ensureStorageTable();

        $memberId = intval($memberId);
        if ($memberId <= 0) {
            exception('请先登录');
        }

        $scene = trim((string) $scene);
        if ($scene === '') {
            exception('缺少协议场景');
        }

        $accordUniques = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, (array) $accordUniques))));

        if (empty($accordUniques)) {
            exception('缺少协议标识');
        }

        $accordList = AccordService::frontendResolveByUniques($accordUniques);
        if (empty($accordList)) {
            exception('协议不存在或已停用');
        }
        if (count($accordList) !== count($accordUniques)) {
            $resolvedUniques = array_column($accordList, 'unique');
            $missing = array_values(array_diff($accordUniques, $resolvedUniques));
            exception('以下协议不存在或不可用：' . implode('、', $missing));
        }

        $rows = [];
        $now = datetime();
        $requestIp = Request::ip();
        $userAgent = mb_substr((string) Request::header('user-agent', ''), 0, 255);

        foreach ($accordList as $accord) {
            $rows[] = [
                'accord_id' => max(0, intval($accord['accord_id'] ?? 0)),
                'accord_unique' => $accord['unique'],
                'accord_name' => $accord['name'],
                'member_id' => $memberId,
                'scene' => $scene,
                'platform' => function_exists('member_platform') ? intval(member_platform()) : 0,
                'application' => function_exists('member_application') ? intval(member_application()) : 0,
                'request_ip' => $requestIp,
                'user_agent' => $userAgent,
                'is_disable' => 0,
                'is_delete' => 0,
                'create_uid' => $memberId,
                'update_uid' => 0,
                'delete_uid' => 0,
                'create_time' => $now,
                'update_time' => null,
                'delete_time' => null,
            ];
        }

        (new AccordAcceptModel())->insertAll($rows);

        return $rows;
    }

    public static function statusMemberAccords($memberId = 0, $accordUniques = [])
    {
        self::ensureStorageTable();

        $memberId = intval($memberId);
        $accordUniques = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, (array) $accordUniques))));

        if (empty($accordUniques)) {
            return [];
        }

        $accords = AccordService::frontendResolveByUniques($accordUniques);

        $rows = [];
        if ($memberId > 0 && !empty($accords)) {
            $rows = (new AccordAcceptModel())
                ->where('member_id', $memberId)
                ->whereIn('accord_unique', $accordUniques)
                ->where('is_delete', 0)
                ->order('create_time', 'desc')
                ->select()
                ->toArray();
        }

        $latestMap = [];
        foreach ($rows as $row) {
            $key = $row['accord_unique'] ?? '';
            if ($key === '' || isset($latestMap[$key])) {
                continue;
            }
            $latestMap[$key] = $row;
        }

        $statusList = [];
        foreach ($accords as $accord) {
            $latest = $latestMap[$accord['unique']] ?? null;
            $statusList[] = [
                'accord_id' => intval($accord['accord_id'] ?? 0),
                'accord_unique' => $accord['unique'],
                'accord_name' => $accord['name'],
                'accepted' => $latest ? 1 : 0,
                'latest_accept_time' => $latest['create_time'] ?? '',
                'latest_scene' => $latest['scene'] ?? '',
                'latest_scene_title' => self::sceneTitle($latest['scene'] ?? ''),
            ];
        }

        return $statusList;
    }
}
