<?php

test('web responses carry baseline security headers', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeaderMissing('Strict-Transport-Security')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

test('api responses carry baseline security headers', function () {
    $this->getJson(route('v1.genders.index'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('a report-only content security policy can be switched on', function () {
    config(['custom.security.csp_report_only' => true]);

    $this->get(route('login'))
        ->assertHeader('Content-Security-Policy-Report-Only', config('custom.security.csp_policy'));
});

test('hsts is only sent on secure production requests', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');

    $this->get('http://localhost/login')
        ->assertHeaderMissing('Strict-Transport-Security');
});
