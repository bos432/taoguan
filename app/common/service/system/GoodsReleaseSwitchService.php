<?php

namespace app\common\service\system;

class GoodsReleaseSwitchService
{
    public static function info(): array
    {
        $path = self::readPath();
        if (!is_file($path)) {
            return self::defaults();
        }

        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return self::defaults();
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return self::defaults();
        }

        return self::normalize($data);
    }

    public static function edit(array $param = []): array
    {
        $data = self::normalize(array_merge(self::info(), $param));
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false || !self::writeConfig($encoded)) {
            exception('商品发布开关保存失败');
        }

        return $data;
    }

    public static function enabled(): bool
    {
        return intval(self::info()['goods_release_enabled'] ?? 1) === 1;
    }

    private static function defaults(): array
    {
        return [
            'goods_release_enabled' => 1,
        ];
    }

    private static function normalize(array $data = []): array
    {
        return [
            'goods_release_enabled' => intval($data['goods_release_enabled'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    private static function configPath(): string
    {
        return app()->getRootPath()
            . 'runtime'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'goods_release_switch.json';
    }

    private static function legacyConfigPath(): string
    {
        return app()->getRootPath()
            . 'private'
            . DIRECTORY_SEPARATOR
            . 'system-config'
            . DIRECTORY_SEPARATOR
            . 'goods_release_switch.json';
    }

    private static function configPaths(): array
    {
        return [
            self::configPath(),
            self::legacyConfigPath(),
        ];
    }

    private static function readPath(): string
    {
        $latestPath = '';
        $latestMtime = -1;

        foreach (self::configPaths() as $path) {
            if (is_file($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false && trim($raw) !== '') {
                    $mtime = @filemtime($path);
                    $mtime = $mtime === false ? 0 : intval($mtime);
                    if ($latestPath === '' || $mtime >= $latestMtime) {
                        $latestPath = $path;
                        $latestMtime = $mtime;
                    }
                }
            }
        }

        return $latestPath !== '' ? $latestPath : self::configPath();
    }

    private static function writeConfig(string $encoded): bool
    {
        $written = false;

        foreach (self::configPaths() as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (@file_put_contents($path, $encoded) !== false) {
                $written = true;
            }
        }

        return $written;
    }
}
