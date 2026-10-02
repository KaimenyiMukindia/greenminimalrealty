<?php

declare(strict_types=1);

$target = $argv[1] ?? null;
$nuxtTarget = $argv[2] ?? null;
if (! is_string($target) || $target === '' || ! is_string($nuxtTarget) || $nuxtTarget === '') {
    fwrite(STDERR, "Usage: php generate-production-env.php <laravel-env-path> <nuxt-env-path>\n");
    exit(2);
}

$required = [
    'APP_URL',
    'FRONTEND_URL',
    'SANCTUM_STATEFUL_DOMAINS',
    'SESSION_DOMAIN',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
];
$settings = [];
foreach ($required as $name) {
    $value = getenv($name);
    if (! is_string($value) || $value === '') {
        fwrite(STDERR, "Missing required deployment environment variable: {$name}\n");
        exit(1);
    }
    $settings[$name] = $value;
}

if (! in_array(getenv('DB_CONNECTION') ?: 'mysql', ['mysql', 'mariadb'], true)) {
    fwrite(STDERR, "Production DB_CONNECTION must be mysql or mariadb.\n");
    exit(1);
}

foreach (['APP_URL', 'FRONTEND_URL'] as $urlKey) {
    if (filter_var($settings[$urlKey], FILTER_VALIDATE_URL) === false || ! str_starts_with(strtolower($settings[$urlKey]), 'https://')) {
        fwrite(STDERR, "{$urlKey} must be a valid HTTPS URL for production.\n");
        exit(1);
    }
}

$seedAdmin = filter_var(getenv('SEED_ADMIN') ?: 'false', FILTER_VALIDATE_BOOLEAN);
$adminEmail = getenv('ADMIN_EMAIL') ?: '';
$adminPassword = getenv('ADMIN_PASSWORD') ?: '';
$adminName = getenv('ADMIN_NAME') ?: 'Administrator';
if ($seedAdmin && ($adminEmail === '' || $adminPassword === '')) {
    fwrite(STDERR, "SEED_ADMIN=true requires ADMIN_EMAIL and ADMIN_PASSWORD.\n");
    exit(1);
}

$appKey = null;
if (is_file($target) && preg_match('/^APP_KEY=(.*)$/m', (string) file_get_contents($target), $matches) === 1) {
    $appKey = trim($matches[1], " \t\"'");
}
$appKey = $appKey ?: 'base64:' . base64_encode(random_bytes(32));

$values = [
    'APP_NAME' => getenv('APP_NAME') ?: 'Green Minimal Realty',
    'APP_ENV' => 'production',
    'APP_KEY' => $appKey,
    'APP_DEBUG' => 'false',
    'APP_URL' => $settings['APP_URL'],
    'FRONTEND_URL' => $settings['FRONTEND_URL'],
    'APP_LOCALE' => 'en',
    'APP_FALLBACK_LOCALE' => 'en',
    'BCRYPT_ROUNDS' => '12',
    'SANCTUM_STATEFUL_DOMAINS' => $settings['SANCTUM_STATEFUL_DOMAINS'],
    'SANCTUM_TOKEN_EXPIRATION' => getenv('SANCTUM_TOKEN_EXPIRATION') ?: '20160',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    'SESSION_DOMAIN' => $settings['SESSION_DOMAIN'],
    'SESSION_DRIVER' => 'database',
    'SESSION_LIFETIME' => '120',
    'DB_CONNECTION' => getenv('DB_CONNECTION') ?: 'mysql',
    'DB_HOST' => $settings['DB_HOST'],
    'DB_PORT' => $settings['DB_PORT'],
    'DB_DATABASE' => $settings['DB_DATABASE'],
    'DB_USERNAME' => $settings['DB_USERNAME'],
    'DB_PASSWORD' => $settings['DB_PASSWORD'],
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'database',
    'FILESYSTEM_DISK' => 'public',
    'MAIL_MAILER' => 'log',
    'LOG_CHANNEL' => 'stack',
    'LOG_LEVEL' => 'warning',
    'SEED_ADMIN' => $seedAdmin ? 'true' : 'false',
    'ADMIN_EMAIL' => $adminEmail,
    'ADMIN_PASSWORD' => $adminPassword,
    'ADMIN_NAME' => $adminName,
    'VITE_APP_NAME' => getenv('APP_NAME') ?: 'Green Minimal Realty',
];

$nuxtValues = [
    'NUXT_LARAVEL_API_URL' => $settings['APP_URL'],
    'NUXT_SESSION_COOKIE_SECURE' => 'true',
    'NUXT_SESSION_COOKIE_NAME' => getenv('NUXT_SESSION_COOKIE_NAME') ?: 'gmr_session',
];

foreach ([$target => $values, $nuxtTarget => $nuxtValues] as $path => $fileValues) {
    $lines = [];
    foreach ($fileValues as $name => $value) {
        if (preg_match('/[\r\n\x00]/', $value) === 1) {
            fwrite(STDERR, "Invalid newline or NUL in deployment setting {$name}.\n");
            exit(1);
        }
        $escaped = strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$']);
        $lines[] = $name . '="' . $escaped . '"';
    }

    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Unable to create environment directory.\n");
        exit(1);
    }

    $tempPath = $path . '.tmp';
    if (file_put_contents($tempPath, implode("\n", $lines) . "\n", LOCK_EX) === false) {
        fwrite(STDERR, "Unable to write generated environment file.\n");
        exit(1);
    }
    chmod($tempPath, 0600);
    if (! rename($tempPath, $path)) {
        @unlink($tempPath);
        fwrite(STDERR, "Unable to activate generated environment file.\n");
        exit(1);
    }
    chmod($path, 0600);
}

fwrite(STDOUT, "Generated production Laravel and Nuxt environment files; retained existing APP_KEY.\n");
