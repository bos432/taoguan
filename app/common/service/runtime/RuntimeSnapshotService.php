<?php

namespace app\common\service\runtime;

class RuntimeSnapshotService
{
    public static function build(string $scope, array $setting = [], array $profile = [], array $preferenceSummary = []): array
    {
        $settingVersion = self::hashPayload($setting);
        $profileVersion = self::hashPayload($profile);
        $preferenceVersion = '' . ($preferenceSummary['version'] ?? self::hashPayload($preferenceSummary));
        $preferenceCount = intval($preferenceSummary['count'] ?? 0);

        $versionPayload = [
            'scope' => $scope,
            'setting_version' => $settingVersion,
            'profile_version' => $profileVersion,
            'preference_version' => $preferenceVersion,
            'preference_count' => $preferenceCount,
        ];

        return [
            'scope' => $scope,
            'version' => self::hashPayload($versionPayload),
            'setting_version' => $settingVersion,
            'profile_version' => $profileVersion,
            'preference_version' => $preferenceVersion,
            'setting_updated_at' => self::latestTimestamp($setting),
            'profile_updated_at' => self::latestTimestamp($profile),
            'preference_updated_at' => '' . ($preferenceSummary['latest_update_time'] ?? ''),
            'preference_count' => $preferenceCount,
            'generated_at' => datetime(),
        ];
    }

    private static function hashPayload(array $payload): string
    {
        $normalized = self::normalizePayload($payload);
        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return sha1($encoded === false ? serialize($normalized) : $encoded);
    }

    private static function normalizePayload($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        if (self::isAssoc($payload)) {
            ksort($payload);
        }

        foreach ($payload as $key => $value) {
            $payload[$key] = self::normalizePayload($value);
        }

        return $payload;
    }

    private static function latestTimestamp(array $payload): string
    {
        $latest = '';
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $candidate = self::latestTimestamp($value);
            } else if (in_array($key, ['update_time', 'create_time', 'latest_update_time'], true)) {
                $candidate = '' . $value;
            } else {
                $candidate = '';
            }

            if ($candidate !== '' && ($latest === '' || strcmp($candidate, $latest) > 0)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    private static function isAssoc(array $payload): bool
    {
        if ($payload === []) {
            return false;
        }
        return array_keys($payload) !== range(0, count($payload) - 1);
    }
}
