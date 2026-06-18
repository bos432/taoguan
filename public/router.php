<?php

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$documentRoot = __DIR__;
$target = realpath($documentRoot . $path);
$normalizedPath = rtrim($path, '/');
$indexTarget = $normalizedPath === ''
    ? false
    : realpath($documentRoot . $normalizedPath . DIRECTORY_SEPARATOR . 'index.html');

// Let the built-in server return static assets directly when they exist.
if ($path !== '/' && $target && str_starts_with($target, $documentRoot) && is_file($target)) {
    return false;
}

// Support SPA directories like /admin-next/, /merchant/ and /app/
// by serving their own index.html before handing over to ThinkPHP routes.
if ($indexTarget && str_starts_with($indexTarget, $documentRoot) && is_file($indexTarget)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($indexTarget);
    return true;
}

// Support history-mode H5 routes like /app/pages/my/login by
// falling back to the app shell instead of handing them to ThinkPHP.
$appShell = realpath($documentRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'index.html');
if (
    $appShell
    && str_starts_with($path, '/app/')
    && str_starts_with($appShell, $documentRoot)
    && is_file($appShell)
) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($appShell);
    return true;
}

require __DIR__ . '/index.php';
