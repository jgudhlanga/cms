<?php

use App\Http\Controllers\Api\V1\AcademicCalendars\CourseWorkMarkController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('course-work-marks', CourseWorkMarkController::class)
    ->middleware([
        'store' => ['throttle:120,1'],
        'update' => ['throttle:120,1'],
        'destroy' => ['throttle:120,1'],
    ])
    ->names([
        'index' => 'course-work-marks.index',
        'show' => 'course-work-marks.show',
        'store' => 'course-work-marks.store',
        'update' => 'course-work-marks.update',
        'destroy' => 'course-work-marks.destroy',
    ])
    ->actions('-actions', function (ActionRegistrar $actions): void {
        $actions->get('tree', 'tree');
        $actions->get('audit-logs', 'auditLogs');
    });
