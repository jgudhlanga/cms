<?php

use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('student-programs', JsonApiController::class)
    ->readOnly()
    ->names([
        'index' => 'students.student-programs.index',
        'show' => 'students.student-programs.show',
    ]);
