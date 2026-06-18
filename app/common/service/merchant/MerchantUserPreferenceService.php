<?php

namespace app\common\service\merchant;

use app\common\model\merchant\MerchantUserPreferenceModel;

class MerchantUserPreferenceService
{
    public static function list(int $merUserId, string $group = 'ui', array $keys = []): array
    {
        $model = new MerchantUserPreferenceModel();
        $query = $model->where([
            ['mer_user_id', '=', $merUserId],
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

    public static function summary(int $merUserId, string $group = 'ui'): array
    {
        $model = new MerchantUserPreferenceModel();
        $list = $model->where([
            ['mer_user_id', '=', $merUserId],
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

    public static function save(int $merUserId, int $merId, string $group, string $key, $value, string $valueType = 'json', string $remark = ''): array
    {
        $model = new MerchantUserPreferenceModel();
        $where = [
            ['mer_user_id', '=', $merUserId],
            ['preference_group', '=', $group],
            ['preference_key', '=', $key],
        ];

        $encodedValue = self::encodeValue($value, $valueType);
        $payload = [
            'mer_id' => $merId,
            'preference_value' => $encodedValue,
            'value_type' => $valueType,
            'remark' => $remark,
            'update_uid' => $merUserId,
            'update_time' => datetime(),
        ];

        $info = $model->where($where)->find();
        if ($info) {
            $info->save($payload);
        } else {
            $payload = array_merge($payload, [
                'mer_user_id' => $merUserId,
                'preference_group' => $group,
                'preference_key' => $key,
                'create_uid' => $merUserId,
                'create_time' => datetime(),
            ]);
            $model->save($payload);
        }

        return self::info($merUserId, $group, $key);
    }

    public static function batchSave(int $merUserId, int $merId, string $group, array $items = []): array
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

            self::save($merUserId, $merId, $group, $key, $value, $valueType, $remark);
            $savedKeys[] = $key;
        }

        return self::list($merUserId, $group, $savedKeys);
    }

    public static function dele(int $merUserId, string $group, array $keys = []): array
    {
        if (empty($keys)) {
            return ['count' => 0, 'keys' => []];
        }

        $model = new MerchantUserPreferenceModel();
        $count = $model->where([
            ['mer_user_id', '=', $merUserId],
            ['preference_group', '=', $group],
        ])->where('preference_key', 'in', $keys)->delete();

        return ['count' => $count, 'keys' => array_values($keys)];
    }

    public static function clear(int $merUserId, string $group): array
    {
        $model = new MerchantUserPreferenceModel();
        $count = $model->where([
            ['mer_user_id', '=', $merUserId],
            ['preference_group', '=', $group],
        ])->delete();

        return ['count' => $count, 'group' => $group];
    }

    public static function info(int $merUserId, string $group, string $key): array
    {
        $model = new MerchantUserPreferenceModel();
        $info = $model->where([
            ['mer_user_id', '=', $merUserId],
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
