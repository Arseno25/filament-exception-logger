<?php

use Arseno25\ExceptionLogger\ExceptionLoggerPlugin;
use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;

it('plugin can be instantiated', function () {
    $plugin = ExceptionLoggerPlugin::make();

    expect($plugin)->toBeInstanceOf(ExceptionLoggerPlugin::class)
        ->and($plugin->getId())->toBe('exception-logger');
});

it('resource can be resolved', function () {
    expect(class_exists(ExceptionLogResource::class))->toBeTrue();
});
