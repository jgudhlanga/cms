<?php

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('dashboard');
})->middleware(['auth', 'verified'])->name('home');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'redirect.student'])->name('dashboard');

require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/users.php';
require __DIR__ . '/web/settings.php';
require __DIR__ . '/web/acl.php';
require __DIR__ . '/web/shared.php';
require __DIR__ . '/web/payments.php';
require __DIR__ . '/web/institution.php';
require __DIR__ . '/web/portal.php';
require __DIR__ . '/web/students.php';
require __DIR__ . '/web/enrolments.php';
