<?php

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

/*
|--------------------------------------------------------------------------
| Error page preview (local only)
|--------------------------------------------------------------------------
|
| Visual harness for resources/views/errors. Never registered outside local,
| so nothing here is reachable on staging or production. Widen the guard to
| app()->environment(['local', 'staging']) if you need it on a shared box.
|
*/

if (! app()->environment('local')) {
    return;
}

/** Codes with a page of their own, then a sample of the ones that fall through to 4xx/5xx. */
$previewCodes = [
    'Dedicated pages' => [401, 402, 403, 404, 419, 429, 500, 503],
    'Falls back to 4xx' => [400, 405, 408, 410, 422, 451],
    'Falls back to 5xx' => [501, 502, 504],
];

Route::prefix('__errors')->group(function () use ($previewCodes) {
    Route::get('/', fn () => view('dev.errors-preview', [
        'groups' => $previewCodes,
    ]))->name('dev.errors.preview');

    // Flip the same appearance cookie the app uses, so the previews re-render themed.
    Route::get('theme/{appearance}', function (string $appearance) {
        abort_unless(in_array($appearance, ['light', 'dark', 'system'], true), 404);

        return redirect()->route('dev.errors.preview')
            ->withCookie(cookie('appearance', $appearance, 60 * 24 * 365, '/', null, null, false, false, 'Lax'));
    })->name('dev.errors.theme');

    Route::get('{code}', function (int $code) {
        abort_unless($code >= 400 && $code <= 599, 404);

        // ?real=1 throws for real, so app-level exception hooks (the student 403
        // redirect, for one) run exactly as they would in production.
        if (request()->boolean('real')) {
            abort($code);
        }

        // Otherwise resolve the view the way the framework's handler does, but
        // respond 200 so the page always renders in the gallery's iframes. The
        // errors:: namespace only exists once the handler registers it, and no
        // exception has been thrown on this path.
        (new RegisterErrorViewPaths)();

        $view = view()->exists("errors::{$code}")
            ? "errors::{$code}"
            : 'errors::'.substr((string) $code, 0, 1).'xx';

        abort_unless(view()->exists($view), 404);

        return response()->view($view, ['exception' => new HttpException($code)]);
    })->whereNumber('code')->name('dev.errors.show');
});
