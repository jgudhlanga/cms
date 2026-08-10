<?php

use App\Http\Middleware\RestorePreviousUrlAfterApiRequest;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

function sessionForPreviousUrlTest(): Store
{
    /** @var Store $session */
    $session = app('session.store');
    $session->start();

    return $session;
}

it('restores previous url from same-origin non-api referer after api request', function (): void {
    $session = sessionForPreviousUrlTest();
    $session->setPreviousUrl('http://localhost/api/students?student_type=apprentice');

    $request = Request::create('http://localhost/api/students?student_type=apprentice', 'GET');
    $request->headers->set('Referer', 'http://localhost/students');
    $request->setLaravelSession($session);

    $response = (new RestorePreviousUrlAfterApiRequest)->handle(
        $request,
        static fn (): Response => response('ok'),
    );

    expect($response->getContent())->toBe('ok')
        ->and($session->previousUrl())->toBe('http://localhost/students');
});

it('does not restore previous url from api referer', function (): void {
    $session = sessionForPreviousUrlTest();
    $session->setPreviousUrl('http://localhost/api/students');

    $request = Request::create('http://localhost/api/students', 'GET');
    $request->headers->set('Referer', 'http://localhost/api/students/stats');
    $request->setLaravelSession($session);

    (new RestorePreviousUrlAfterApiRequest)->handle(
        $request,
        static fn (): Response => response('ok'),
    );

    expect($session->previousUrl())->toBe('http://localhost/api/students');
});

it('does not restore previous url from cross-origin referer', function (): void {
    $session = sessionForPreviousUrlTest();
    $session->setPreviousUrl('http://localhost/api/students');

    $request = Request::create('http://localhost/api/students', 'GET');
    $request->headers->set('Referer', 'https://evil.example/students');
    $request->setLaravelSession($session);

    (new RestorePreviousUrlAfterApiRequest)->handle(
        $request,
        static fn (): Response => response('ok'),
    );

    expect($session->previousUrl())->toBe('http://localhost/api/students');
});

it('ignores non-api requests', function (): void {
    $session = sessionForPreviousUrlTest();
    $session->setPreviousUrl('http://localhost/dashboard');

    $request = Request::create('http://localhost/students', 'GET');
    $request->headers->set('Referer', 'http://localhost/students?page=2');
    $request->setLaravelSession($session);

    (new RestorePreviousUrlAfterApiRequest)->handle(
        $request,
        static fn (): Response => response('ok'),
    );

    expect($session->previousUrl())->toBe('http://localhost/dashboard');
});
