<?php

use App\Http\Controllers\Api\V1\Students\StudentProgramController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('student-programs', StudentProgramController::class)
    ->readOnly()
    ->names([
        'index' => 'students.student-programs.index',
        'show' => 'students.student-programs.show',
    ])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->get('dashboard-stats', 'dashboardStats');
    });
