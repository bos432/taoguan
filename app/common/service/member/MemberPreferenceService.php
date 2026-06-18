<?php

namespace app\common\service\member;

use app\common\model\member\MemberPreferenceModel;

class MemberPreferenceService
{
    public static function list(int $memberId, string $group = 'ui', array $keys = []): array
    {
        $model = new MemberPreferenceModel();
        $query = $model->where([
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
        ]);

        if (!empty($keys)) {
            $query->where('preference_key', 'in', $keys);
        }

        $list = $query->order(['preference_id' => 'asc'])->select()->toArray();
        foreach ($list as &$item) {
            $item['decoded_value'] = self::decodeValue($item['preference_value'] ?? '', $item['value_type'] ?? 'json');
        }
        unset($item);

        $map = [];
        foreach ($list as $item) {
            $map[$item['preference_key']] = $item['decoded_value'];
        }

        return compact('group', 'list', 'map');
    }

    public static function summary(int $memberId, string $group = 'ui'): array
    {
        $model = new MemberPreferenceModel();
        $list = $model->where([
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
        ])->field('preference_id,preference_key,create_time,update_time')->select()->toArray();

        $fingerprints = [];
        $latestUpdateTime = '';
        foreach ($list as $item) {
            $time = '' . ($item['update_time'] ?? $item['create_time'] ?? '');
            $fingerprints[] = ($item['preference_key'] ?? '') . '@' . $time;
            if ($time !== '' && ($latestUpdateTime === '' || strcmp($time, $latestUpdateTime) > 0)) {
                $latestUpdateTime = $time;
            }
        }

        sort($fingerprints);

        return [
            'group' => $group,
            'count' => count($list),
            'latest_update_time' => $latestUpdateTime,
            'version' => sha1($group . '|' . count($fingerprints) . '|' . implode('|', $fingerprints)),
        ];
    }

    public static function save(int $memberId, string $group, string $key, $value, string $valueType = 'json', string $remark = ''): array
    {
        $model = new MemberPreferenceModel();
        $where = [
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
            ['preference_key', '=', $key],
        ];

        $encodedValue = self::encodeValue($value, $valueType);
        $payload = [
            'preference_value' => $encodedValue,
            'value_type' => $valueType,
            'remark' => $remark,
            'update_uid' => $memberId,
            'update_time' => datetime(),
        ];

        $info = $model->where($where)->find();
        if ($info) {
            $info->save($payload);
        } else {
            $payload = array_merge($payload, [
                'member_id' => $memberId,
                'preference_group' => $group,
                'preference_key' => $key,
                'create_uid' => $memberId,
                'create_time' => datetime(),
            ]);
            $model->save($payload);
        }

        return self::info($memberId, $group, $key);
    }

    public static function batchSave(int $memberId, string $group, array $items = []): array
    {
        $savedKeys = [];
        foreach ($items as $item) {
            $key = '' . ($item['preference_key'] ?? $item['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $value = $item['preference_value'] ?? ($item['value'] ?? null);
            $valueType = '' . ($item['value_type'] ?? 'json');
            $remark = '' . ($item['remark'] ?? '');

            self::save($memberId, $group, $key, $value, $valueType, $remark);
            $savedKeys[] = $key;
        }

        return self::list($memberId, $group, $savedKeys);
    }

    public static function dele(int $memberId, string $group, array $keys = []): array
    {
        if (empty($keys)) {
            return ['count' => 0, 'keys' => []];
        }

        $model = new MemberPreferenceModel();
        $count = $model->where([
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
        ])->where('preference_key', 'in', $keys)->delete();

        return ['count' => $count, 'keys' => array_values($keys)];
    }

    public static function clear(int $memberId, string $group): array
    {
        $model = new MemberPreferenceModel();
        $count = $model->where([
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
        ])->delete();

        return ['count' => $count, 'group' => $group];
    }

    public static function info(int $memberId, string $group, string $key): array
    {
        $model = new MemberPreferenceModel();
        $info = $model->where([
            ['member_id', '=', $memberId],
            ['preference_group', '=', $group],
            ['preference_key', '=', $key],
        ])->find();

        if (!$info) {
            return [];
        }

        $data = $info->toArray();
        $data['decoded_value'] = self::decodeValue($data['preference_value'] ?? '', $data['value_type'] ?? 'json');
        return $data;
    }

    private static function encodeValue($value, string $valueType): string
    {
        switch ($valueType) {
            case 'int':
                return (string) intval($value);
            case 'float':
                return (string) floatval($value);
            case 'bool':
                return $value ? '1' : '0';
            case 'string':
                return (string) $value;
            case 'json':
            default:
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
                return $encoded === false ? '' : $encoded;
        }
    }

    private static function decodeValue(string $value, string $valueType)
    {
        switch ($valueType) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
            case 'string':
                return $value;
            case 'json':
            default:
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }
    }
}
