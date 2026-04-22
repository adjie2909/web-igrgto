<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static bool $viewCacheCleared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $viewCompiledPath = env('VIEW_COMPILED_PATH');
        if (is_string($viewCompiledPath) && $viewCompiledPath !== '' && ! is_dir($viewCompiledPath)) {
            @mkdir($viewCompiledPath, 0777, true);
        }

        if (! self::$viewCacheCleared && is_string($viewCompiledPath) && $viewCompiledPath !== '' && is_dir($viewCompiledPath)) {
            foreach (glob(rtrim($viewCompiledPath, '\\/').DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            self::$viewCacheCleared = true;
        }
    }
}
