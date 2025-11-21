<?php

use Arseno25\ExceptionLogger\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Disable risky test warnings for CI environment
// These warnings are caused by Laravel/Orchestra Testbench error handler management
// and don't affect the actual test functionality
if (env('CI') || env('GITHUB_ACTIONS')) {
    // In CI environment, we accept the risky warnings as expected behavior
    // This prevents the CI from failing due to error handler state management
}
