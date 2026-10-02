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
        $directory = sys_get_temp_dir() . '/gmr-env-' . Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory . '/backend.env';
        $nuxtTarget = $directory . '/frontend.env';
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
            if (PHP_OS_FAMILY !== 'Windows') $this->assertSame(0600, fileperms($target) & 0777);
            $originalKey = $generated['APP_KEY'];

            $second = new Process([PHP_BINARY, base_path('deploy/generate-production-env.php'), $target, $nuxtTarget], base_path(), array_replace($environment, ['APP_NAME' => 'Updated Deployment Name']));
            $second->mustRun();
            $regenerated = Dotenv::parse(file_get_contents($target));

            $this->assertSame($originalKey, $regenerated['APP_KEY']);
            $this->assertSame('Updated Deployment Name', $regenerated['APP_NAME']);
        } finally {
            if (is_file($target)) unlink($target);
            if (is_file($nuxtTarget)) unlink($nuxtTarget);
            if (is_dir($directory)) rmdir($directory);
        }
    }

    public function test_it_refuses_to_generate_env_without_required_values_or_valid_production_urls(): void
    {
        $directory = sys_get_temp_dir() . '/gmr-env-' . Str::uuid();
        mkdir($directory, 0700, true);
        $target = $directory . '/backend.env';
        $nuxtTarget = $directory . '/frontend.env';

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
            if (is_file($target)) unlink($target);
            if (is_file($nuxtTarget)) unlink($nuxtTarget);
            if (is_dir($directory)) rmdir($directory);
        }
    }

    public function test_cpanel_deployment_packages_runtime_files_and_initial_database_content(): void
    {
        $deployment = Yaml::parseFile(base_path('../.cpanel.yml'));
        $tasks = $deployment['deployment']['tasks'] ?? [];
        $script = implode("\n", $tasks);

        $this->assertStringContainsString('generate-production-env.php', $script);
        $this->assertStringContainsString("--exclude='*.md'", $script);
        $this->assertStringContainsString("--exclude='.env*'", $script);
        $this->assertStringContainsString('php artisan migrate --force', $script);
        $this->assertStringContainsString('.initial-content-seeded', $script);
        $this->assertStringContainsString('php artisan db:seed --force', $script);
        $this->assertStringContainsString('npm run build', $script);
        $this->assertStringNotContainsString('rm -f "$DEPLOYPATH/backend/.env"', $script);
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
        ];
    }
}
