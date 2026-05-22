<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::middleware('auth:sanctum')->group(function () {
    JsonApiRoute::server('v1')
        ->name('v1.json.')
        ->prefix('v1/json')
        ->withoutMiddleware(SubstituteBindings::class)
        ->resources(function (ResourceRegistrar $server) {
            $server->resource('hostels', JsonApiController::class)->readOnly();
            $server->resource('hostel-rooms', JsonApiController::class)->readOnly();
            $server->resource('hostel-room-allocations', JsonApiController::class)->readOnly();
        });
});
