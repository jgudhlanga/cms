<?php

use App\Http\Controllers\Api\V1\Students\StudentApplicationController;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('student-applications', StudentApplicationController::class)
    ->readOnly()
    ->names([
        'index' => 'students.student-applications.index',
        'show' => 'students.student-applications.show',
    ])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->get('dashboard-stats', 'dashboardStats');
    });

$server->resource('student-id-card-requests', JsonApiController::class)
    ->readOnly()
    ->names([
        'index' => 'students.student-id-card-requests.index',
        'show' => 'students.student-id-card-requests.show',
    ]);
