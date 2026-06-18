<?php

namespace app\common\service\system;

class UiThemeSwitchService
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
            exception('前端主题配置保存失败');
        }

        return $data;
    }

    public static function options(): array
    {
        return [
            ['value' => 'origin', 'label' => '默认原版'],
            ['value' => 'red_energy', 'label' => '红底白字五行'],
            ['value' => 'yellow_energy', 'label' => '黄底红字五行'],
            ['value' => 'jade_modern', 'label' => '高级感设计款'],
        ];
    }

    private static function defaults(): array
    {
        return [
            'ui_theme_style' => 'origin',
            'ui_theme_options' => self::options(),
        ];
    }

    private static function normalize(array $data = []): array
    {
        $allowed = array_column(self::options(), 'value');
        $theme = strval($data['ui_theme_style'] ?? 'origin');
        if (!in_array($theme, $allowed, true)) {
            $theme = 'origin';
        }

        return [
            'ui_theme_style' => $theme,
            'ui_theme_options' => self::options(),
        ];
    }

    private static function configPath(): string
    {
        return app()->getRootPath()
            . 'private'
            . DIRECTORY_SEPARATOR
            . 'system-config'
            . DIRECTORY_SEPARATOR
            . 'ui_theme_switch.json';
    }

    private static function legacyConfigPath(): string
    {
        return app()->getRootPath()
            . 'runtime'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'ui_theme_switch.json';
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

    private static function configPaths(): array
    {
        return [
            self::configPath(),
            self::legacyConfigPath(),
        ];
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
