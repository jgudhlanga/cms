<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

Route::middleware('auth:sanctum')->group(function () {
    JsonApiRoute::server('v1')
        ->name('v1.json.')
        ->prefix('v1/json')
        ->withoutMiddleware(SubstituteBindings::class)
        ->resources(function (ResourceRegistrar $server) {
            require __DIR__.'/jsonapi/hms.php';
        });
});
