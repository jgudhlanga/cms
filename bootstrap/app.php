<?php

use App\Enums\Acl\RoleEnum;
use App\Http\Middleware\EnsureCanImpersonate;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectStudentMiddleware;
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
        ]);
        $middleware->api(append: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->alias([
            'redirect.student' => RedirectStudentMiddleware::class,
            'ensure.can.impersonate' => EnsureCanImpersonate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport(JsonApiException::class);

        $exceptions->render(ExceptionParser::renderer());

        $redirectStudentFromForbidden = static function (Request $request): ?RedirectResponse {
            if ($request->is('api*') || $request->expectsJson()) {
                return null;
            }

            $user = $request->user();

            if ($user === null || $user->isImpersonated() || ! $user->hasRole(RoleEnum::STUDENT->name())) {
                return null;
            }

            if ($request->routeIs('portal.*')) {
                return null;
            }

            $route = $user->has_student_profile
                ? 'portal.dashboard'
                : 'portal.application.level-options';

            return to_route($route);
        };

        $exceptions->renderable(function (AuthorizationException $e, Request $request) use ($redirectStudentFromForbidden) {
            return $redirectStudentFromForbidden($request);
        });

        $exceptions->renderable(function (HttpException $e, Request $request) use ($redirectStudentFromForbidden) {
            $studentRedirect = $e->getStatusCode() === 403
                ? $redirectStudentFromForbidden($request)
                : null;

            if ($studentRedirect !== null) {
                return $studentRedirect;
            }

            if ($request->is('api*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Not Found',
                    'exception' => class_basename($e),
                ], $e->getStatusCode());
            }
        });
    })->create();
