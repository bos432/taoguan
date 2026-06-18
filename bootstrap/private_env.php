<?php

declare(strict_types=1);

use think\App;

if (!function_exists('secure_bootstrap_private_env')) {
    /**
     * Load private env overrides before the framework initializes config.
     * The root .env can then stay sanitized while secrets live in private storage.
     *
     * @return array<int, string>
     */
    function secure_bootstrap_private_env(App $app): array
    {
        $env = $app->make('env');
        $rootPath = $app->getRootPath();

        $candidates = array_filter([
            getenv('APP_PRIVATE_ENV_FILE') ?: '',
            $rootPath . 'private/system-config/.env.local',
            $rootPath . 'private/system-config/app.env',
        ]);

        $loaded = [];
        foreach (array_unique($candidates) as $file) {
            if (is_file($file)) {
                $env->load($file);
                $loaded[] = $file;
            }
        }

        return $loaded;
    }
}
