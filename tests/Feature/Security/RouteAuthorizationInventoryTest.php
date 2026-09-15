<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;

it('keeps unauthenticated application routes on the reviewed allow-list', function () {
    $router = app(Router::class);
    $allowed = require base_path('tests/Support/public-routes.php');
    $guards = ['Authenticate', 'ValidateSignature', 'RedirectIfAuthenticated'];

    $publicRoutes = collect($router->getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->getActionName(), 'App\\'))
        ->reject(function (RoutingRoute $route) use ($router, $guards): bool {
            $middleware = implode(' ', $router->gatherRouteMiddleware($route));

            foreach ($guards as $guard) {
                if (str_contains($middleware, $guard)) {
                    return true;
                }
            }

            return false;
        })
        ->map(fn (RoutingRoute $route): string => $route->getName() ?? $route->uri())
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect(array_values(array_diff($publicRoutes, $allowed)))->toBe([]);
});
