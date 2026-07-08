<?php

use App\Http\Controllers\Api\V1\HMS\HostelApplicationController;
use App\Http\Controllers\Api\V1\HMS\HostelRoomAllocationController;
use App\Http\Controllers\Api\V1\HMS\HostelRoomController;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/** @var ResourceRegistrar $server */
$server->resource('hostels', JsonApiController::class)
    ->readOnly()
    ->names(['index' => 'hms.hostels.index', 'show' => 'hms.hostels.show']);

$server->resource('hostel-amenities', JsonApiController::class)
    ->readOnly()
    ->names([
        'index' => 'hms.hostel-amenities.index',
        'show' => 'hms.hostel-amenities.show',
    ]);

$server->resource('hostel-rooms', HostelRoomController::class)
    ->readOnly()
    ->names(['index' => 'hms.hostel-rooms.index', 'show' => 'hms.hostel-rooms.show'])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->get('stats');
    });

$server->resource('hostel-room-allocations', HostelRoomAllocationController::class)
    ->readOnly()
    ->names([
        'index' => 'hms.hostel-room-allocations.index',
        'show' => 'hms.hostel-room-allocations.show',
    ])
    ->actions('-actions', function (ActionRegistrar $actions) {
        $actions->withId()->get('roommates', 'roommates');
    });

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
        $actions->get('self-lookup', 'selfLookup');
        $actions->get('accommodation-fees', 'accommodationFees');
        $actions->get('pending-queue', 'pendingQueue');
        $actions->withId()->get('approval-options', 'approvalOptions');
        $actions->withId()->get('approval-rooms', 'approvalRooms');
    });

$server->resource('hostel-queries', JsonApiController::class)
    ->names([
        'index' => 'hms.hostel-queries.index',
        'show' => 'hms.hostel-queries.show',
        'store' => 'hms.hostel-queries.store',
        'update' => 'hms.hostel-queries.update',
        'destroy' => 'hms.hostel-queries.destroy',
    ]);

$server->resource('hostel-leaves', JsonApiController::class)
    ->names([
        'index' => 'hms.hostel-leaves.index',
        'show' => 'hms.hostel-leaves.show',
        'store' => 'hms.hostel-leaves.store',
        'update' => 'hms.hostel-leaves.update',
        'destroy' => 'hms.hostel-leaves.destroy',
    ]);

$server->resource('hostel-notices', JsonApiController::class)
    ->names([
        'index' => 'hms.hostel-notices.index',
        'show' => 'hms.hostel-notices.show',
        'store' => 'hms.hostel-notices.store',
        'update' => 'hms.hostel-notices.update',
        'destroy' => 'hms.hostel-notices.destroy',
    ]);

$server->resource('hms-settings', JsonApiController::class)
    ->only('index', 'show', 'update')
    ->names([
        'index' => 'hms.hms-settings.index',
        'show' => 'hms.hms-settings.show',
        'update' => 'hms.hms-settings.update',
    ]);
