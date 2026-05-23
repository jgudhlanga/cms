<?php

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
