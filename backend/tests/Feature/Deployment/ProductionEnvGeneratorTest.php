<?php

namespace Tests\Feature\Deployment;

use Dotenv\Dotenv;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ProductionEnvGeneratorTest extends TestCase
{
    public function test_it_generates_a_secure_production_env_and_retains_the_app_key_on_regeneration(): void
    {
        $directory = sys_get_temp_dir().'/gmr-env-'.Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory.'/backend.env';
        $nuxtTarget = $directory.'/frontend.env';
        $environment = array_replace($this->deploymentEnvironment(), ['DB_PASSWORD' => 'fake$pass#with"quotes']);

        try {
            $first = new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), $environment);
            $first->mustRun();
            $generated = Dotenv::parse(file_get_contents($target));
            $nuxt = Dotenv::parse(file_get_contents($nuxtTarget));

            $this->assertSame('production', $generated['APP_ENV']);
            $this->assertSame('false', $generated['APP_DEBUG']);
            $this->assertSame('true', $generated['SESSION_SECURE_COOKIE']);
            $this->assertSame('fake$pass#with"quotes', $generated['DB_PASSWORD']);
            $this->assertMatchesRegularExpression('/^base64:[A-Za-z0-9+\/=]+$/', $generated['APP_KEY']);
            $this->assertSame('https://api.example.test', $nuxt['NUXT_LARAVEL_API_URL']);
            $this->assertSame('true', $nuxt['NUXT_SESSION_COOKIE_SECURE']);
            $this->assertSame('gmr_session', $nuxt['NUXT_SESSION_COOKIE_NAME']);
            if (PHP_OS_FAMILY !== 'Windows') {
                $this->assertSame(0600, fileperms($target) & 0777);
            }
            $originalKey = $generated['APP_KEY'];

            $second = new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), array_replace($environment, ['APP_NAME' => 'Updated Deployment Name']));
            $second->mustRun();
            $regenerated = Dotenv::parse(file_get_contents($target));

            $this->assertSame($originalKey, $regenerated['APP_KEY']);
            $this->assertSame('Updated Deployment Name', $regenerated['APP_NAME']);
        } finally {
            if (is_file($target)) {
                unlink($target);
            }
            if (is_file($nuxtTarget)) {
                unlink($nuxtTarget);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function test_it_refuses_to_generate_env_without_required_values_or_valid_production_urls(): void
    {
        $directory = sys_get_temp_dir().'/gmr-env-'.Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory.'/backend.env';
        $nuxtTarget = $directory.'/frontend.env';

        try {
            $missingValues = $this->deploymentEnvironment();
            unset($missingValues['DB_PASSWORD']);
            $missing = new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), $missingValues);
            $missing->run();
            $this->assertSame(1, $missing->getExitCode());
            $this->assertFileDoesNotExist($target);

            $insecureUrl = new Process(
                [PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget],
                base_path(),
                array_replace($this->deploymentEnvironment(), ['APP_URL' => 'http://api.example.test']),
            );
            $insecureUrl->run();
            $this->assertSame(1, $insecureUrl->getExitCode());
            $this->assertFileDoesNotExist($target);
        } finally {
            if (is_file($target)) {
                unlink($target);
            }
            if (is_file($nuxtTarget)) {
                unlink($nuxtTarget);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function test_cpanel_deployment_packages_runtime_files_and_initial_database_content(): void
    {
        $deployment = Yaml::parseFile(base_path('../.cpanel.yml'));
        $tasks = $deployment['deployment']['tasks'] ?? [];
        $this->assertSame(['/bin/bash ./backend/deploy/cpanel-deploy.sh'], $tasks);
        $script = file_get_contents(base_path('deploy/cpanel-deploy.sh'));
        $guide = file_get_contents(base_path('deploy/CPANEL-ENV.md'));

        $this->assertStringContainsString('exec >>"$LOG_FILE" 2>&1', $script);
        $this->assertStringContainsString('tar -C "$REPO_ROOT/backend"', $script);
        $this->assertStringNotContainsString('rsync', $script);
        $this->assertStringContainsString('releases/download/nuxt-$REVISION', $script);
        $this->assertStringContainsString('sha256sum -c nuxt.tar.gz.sha256', $script);
        $this->assertStringContainsString('Nuxt build revision does not match HEAD', $script);
        $this->assertStringContainsString('run ln -s', $script);
        $this->assertStringContainsString('queue:restart', $script);
        $this->assertLessThan(strpos($script, "log 'Sync backend"), strpos($script, 'sha256sum -c'));
        $this->assertLessThan(strpos($script, 'migrate --force'), strpos($script, 'config:clear'));
        $this->assertLessThan(strpos($script, 'optimize:clear'), strpos($script, 'migrate --force'));
        $this->assertStringContainsString('generate-production-env.php', $script);
        $this->assertStringContainsString("--exclude='*.md'", $script);
        $this->assertStringContainsString("--exclude='.env*'", $script);
        $this->assertStringContainsString('migrate --force', $script);
        $this->assertStringContainsString('.initial-content-seeded', $script);
        $this->assertStringContainsString('db:seed --force', $script);
        $this->assertStringNotContainsString('run npm', $script);
        $this->assertStringContainsString('gmr-deploy.log', $script);
        $this->assertStringContainsString('trap fail ERR', $script);
        $this->assertStringContainsString('! -path "$DEPLOYPATH/backend/vendor/*" -delete', $script);
        $this->assertStringContainsString('! -path "$DEPLOYPATH/frontend/.output/*" -delete', $script);
        $this->assertStringContainsString('database/database.sqlite', $script);
        $this->assertStringNotContainsString('rm -f "$DEPLOYPATH/backend/.env"', $script);
        $this->assertStringContainsString('no SSH required', $guide);
        $this->assertStringContainsString('File Manager', $guide);
    }

    public function test_nuxt_release_builds_both_branches_and_packages_a_verified_revision(): void
    {
        $workflow = Yaml::parseFile(base_path('../.github/workflows/nuxt-release.yml'));
        $this->assertSame(['main', 'auth'], $workflow['on']['push']['branches']);
        $this->assertSame('write', $workflow['permissions']['contents']);
        $this->assertSame('ubuntu-latest', $workflow['jobs']['build']['runs-on']);
        $steps = $workflow['jobs']['build']['steps'];
        $this->assertSame(22, $steps[1]['with']['node-version']);
        $this->assertSame('node-server', $steps[3]['env']['NITRO_PRESET']);
        $publish = $steps[4]['run'];
        $this->assertStringContainsString('> frontend/.output/revision', $publish);
        $this->assertStringContainsString('sha256sum nuxt.tar.gz', $publish);
        $this->assertStringContainsString('tag="nuxt-$GITHUB_SHA"', $publish);
        $this->assertStringContainsString('gh release upload', $publish);
    }

    public function test_it_preserves_configured_resend_and_google_only_in_the_backend_env(): void
    {
        $directory = sys_get_temp_dir().'/gmr-env-'.Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory.'/backend.env';
        $nuxtTarget = $directory.'/frontend.env';
        try {
            $environment = array_replace($this->deploymentEnvironment(), [
                'MAIL_MAILER' => 'resend', 'RESEND_API_KEY' => 'test-resend-key',
                'MAIL_FROM_ADDRESS' => 'auth@verified.example.test',
                'GOOGLE_CLIENT_ID' => 'test-client', 'GOOGLE_CLIENT_SECRET' => 'test-google-secret',
                'GOOGLE_REDIRECT_URI' => 'https://api.example.test/auth/google/callback',
            ]);
            (new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), $environment))->mustRun();
            $generated = Dotenv::parse(file_get_contents($target));
            $this->assertSame('resend', $generated['MAIL_MAILER']);
            $this->assertSame('test-resend-key', $generated['RESEND_API_KEY']);
            $this->assertSame('https://api.example.test/auth/google/callback', $generated['GOOGLE_REDIRECT_URI']);
            $nuxt = file_get_contents($nuxtTarget);
            $this->assertStringNotContainsString('test-resend-key', $nuxt);
            $this->assertStringNotContainsString('test-google-secret', $nuxt);
        } finally {
            if (is_file($target)) {
                unlink($target);
            }
            if (is_file($nuxtTarget)) {
                unlink($nuxtTarget);
            }
            rmdir($directory);
        }
    }

    public function test_it_rejects_incomplete_resend_and_google_configuration_before_writing_env(): void
    {
        $directory = sys_get_temp_dir().'/gmr-env-'.Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory.'/backend.env';
        $nuxtTarget = $directory.'/frontend.env';
        try {
            foreach ([
                ['MAIL_MAILER' => 'resend'],
                ['GOOGLE_CLIENT_ID' => 'test-client'],
                ['GOOGLE_CLIENT_ID' => 'test-client', 'GOOGLE_CLIENT_SECRET' => 'test-secret', 'GOOGLE_REDIRECT_URI' => 'http://wrong.test/callback'],
            ] as $invalid) {
                $process = new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), array_replace($this->deploymentEnvironment(), $invalid));
                $process->run();
                $this->assertSame(1, $process->getExitCode());
                $this->assertFileDoesNotExist($target);
            }
        } finally {
            if (is_file($target)) {
                unlink($target);
            }
            if (is_file($nuxtTarget)) {
                unlink($nuxtTarget);
            }
            rmdir($directory);
        }
    }

    /** @return array<string, string> */
    private function deploymentEnvironment(): array
    {
        return [
            'APP_URL' => 'https://api.example.test',
            'FRONTEND_URL' => 'https://www.example.test',
            'SANCTUM_STATEFUL_DOMAINS' => 'www.example.test',
            'SESSION_DOMAIN' => '.example.test',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'gmr_test',
            'DB_USERNAME' => 'gmr_test',
            'DB_PASSWORD' => 'fake-database-password',
            'SEED_ADMIN' => 'false',
            'MAIL_MAILER' => 'log',
            'RESEND_API_KEY' => '',
            'GOOGLE_CLIENT_ID' => '',
            'GOOGLE_CLIENT_SECRET' => '',
            'GOOGLE_REDIRECT_URI' => '',
        ];
    }
}
