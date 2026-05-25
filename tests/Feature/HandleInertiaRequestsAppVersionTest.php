<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\AppVersion;
use Illuminate\Http\Request;

test('inertia shared data includes app version from config', function () {
    config(['app.version' => 'ci-test-abc1234']);

    $middleware = app(HandleInertiaRequests::class);
    $shared = $middleware->share(Request::create('/'));

    expect($shared['appVersion'])->toBe('ci-test-abc1234');
});

test('app version resolver uses config when APP_VERSION is set', function () {
    config(['app.version' => '1.0.0+build']);

    expect(app(AppVersion::class)->resolve())->toBe('1.0.0+build');
});
