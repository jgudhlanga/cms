<?php

use App\Enums\Acl\RoleEnum;
use App\Http\Middleware\EnsureRegistrationOpen;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectImpersonatedToPortal;
use App\Http\Middleware\RedirectStudentMiddleware;
use App\Services\Students\RegistrationAvailabilityService;
use App\Support\Auth\DefaultHome;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Exceptions\ExceptionParser;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            RedirectImpersonatedToPortal::class,
        ]);
        $middleware->api(append: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->alias([
            'redirect.student' => RedirectStudentMiddleware::class,
            'registration.open' => EnsureRegistrationOpen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport(JsonApiException::class);

        $exceptions->render(ExceptionParser::renderer());

        $redirectFromForbidden = static function (Request $request): ?RedirectResponse {
            if ($request->is('api*') || $request->expectsJson()) {
                return null;
            }

            $user = $request->user();

            if ($user === null || $user->isImpersonated()) {
                return null;
            }

            if ($user->hasRole(RoleEnum::STUDENT->name())) {
                if ($request->routeIs('portal.*')) {
                    return null;
                }

                $route = $user->has_student_profile
                    ? 'portal.dashboard'
                    : (app(RegistrationAvailabilityService::class)->isRegistrationOpen()
                        ? 'portal.application.level-options'
                        : 'portal.registration.maintenance');

                return to_route($route);
            }

            if (
                DefaultHome::shouldUseLecturerHome($user)
                && ($request->routeIs('dashboard') || $request->routeIs('home'))
            ) {
                return to_route('lecturer.dashboard');
            }

            return null;
        };

        $exceptions->renderable(function (AuthorizationException $e, Request $request) use ($redirectFromForbidden) {
            return $redirectFromForbidden($request);
        });

        $exceptions->renderable(function (HttpException $e, Request $request) use ($redirectFromForbidden) {
            $redirect = $e->getStatusCode() === 403
                ? $redirectFromForbidden($request)
                : null;

            if ($redirect !== null) {
                return $redirect;
            }

            if ($request->is('api*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Not Found',
                    'exception' => class_basename($e),
                ], $e->getStatusCode());
            }
        });
    })->create();
