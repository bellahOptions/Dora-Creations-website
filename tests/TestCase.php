<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // `.env` sets APP_ENV=local and Laravel's env repository prefers it
        // over the value phpunit.xml provides, so without this the suite runs
        // as "local": Application::runningUnitTests() returns false, which
        // silently disables Laravel's CSRF test-skip and Filament's
        // fillForm() test helper. Correcting $_ENV/$_SERVER before the
        // application boots makes .env defer to the testing values.
        foreach (['APP_ENV' => 'testing'] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }
}
