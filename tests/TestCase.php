<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->forceIsolatedTestingDatabaseEnvironment();

        $app = parent::createApplication();

        $this->assertIsolatedTestingDatabase($app);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'legado-ab-fenix-testing-views';

        File::ensureDirectoryExists($compiledViewPath);

        config([
            'view.compiled' => $compiledViewPath,
        ]);
    }

    private function forceIsolatedTestingDatabaseEnvironment(): void
    {
        $environment = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ];

        foreach ($environment as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function assertIsolatedTestingDatabase($app): void
    {
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                'Tests must use the isolated sqlite :memory: database. Refusing to run against ' .
                "{$connection} / {$database}."
            );
        }
    }
}
