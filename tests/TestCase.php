<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'legado-ab-fenix-testing-views';

        File::ensureDirectoryExists($compiledViewPath);

        config([
            'view.compiled' => $compiledViewPath,
        ]);
    }
}
