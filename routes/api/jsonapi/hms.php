<?php

use App\Http\Controllers\Api\V1\HMS\HostelApplicationController;
use App\Http\Controllers\Api\V1\HMS\HostelRoomController;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('hostels', JsonApiController::class)
    ->readOnly()
    ->names(['index' => 'hms.hostels.index', 'show' => 'hms.hostels.show']);

$server->resource('hostel-rooms', HostelRoomController::class)
    ->readOnly()
    ->names(['index' => 'hms.hostel-rooms.index', 'show' => 'hms.hostel-rooms.show'])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->get('stats');
    });

$server->resource('hostel-room-allocations', JsonApiController::class)
    ->readOnly()
    ->names([
        'index' => 'hms.hostel-room-allocations.index',
        'show' => 'hms.hostel-room-allocations.show',
    ]);

$server->resource('hostel-applications', HostelApplicationController::class)
    ->names([
        'index' => 'hms.hostel-applications.index',
        'show' => 'hms.hostel-applications.show',
        'store' => 'hms.hostel-applications.store',
        'update' => 'hms.hostel-applications.update',
        'destroy' => 'hms.hostel-applications.destroy',
    ])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->get('student-lookup', 'studentLookup');
        $actions->get('pending-queue', 'pendingQueue');
        $actions->withId()->get('approval-options', 'approvalOptions');
        $actions->withId()->get('approval-rooms', 'approvalRooms');
    });

$server->resource('hms-settings', JsonApiController::class)
    ->only('index', 'show', 'update')
    ->names([
        'index' => 'hms.hms-settings.index',
        'show' => 'hms.hms-settings.show',
        'update' => 'hms.hms-settings.update',
    ]);
