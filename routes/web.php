<?php

use App\Http\Controllers\Auth\ImpersonationController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Students\IdCardVerifyController;
use App\Support\Auth\DefaultHome;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Controllers\ImpersonateController as VendorImpersonateController;

Route::get('/', function () {
    return to_route(DefaultHome::routeName(auth()->user()));
})->middleware(['auth', 'verified'])->name('home');

Route::get('id-cards/verify/{serial}', IdCardVerifyController::class)
    ->where('serial', '[A-Za-z0-9\\-]+')
    ->name('id-cards.verify');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('impersonate/take/{id}/{guardName?}', [ImpersonationController::class, 'take'])->name('impersonate');
    Route::get('impersonate/leave', [VendorImpersonateController::class, 'leave'])->name('impersonate.leave');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'redirect.student'])->name('dashboard');

require __DIR__.'/web/integrations.php';
require __DIR__.'/web/auth.php';
require __DIR__.'/web/users.php';
require __DIR__.'/web/settings.php';
require __DIR__.'/web/finance.php';
require __DIR__.'/web/rbac.php';
require __DIR__.'/web/shared.php';
require __DIR__.'/web/payments.php';
require __DIR__.'/web/institution.php';
require __DIR__.'/web/portal.php';
require __DIR__.'/web/enrolments.php';
require __DIR__.'/web/students.php';
require __DIR__.'/web/workflows.php';
require __DIR__.'/web/documents.php';
require __DIR__.'/web/hms.php';
require __DIR__.'/web/academic-calendars.php';
require __DIR__.'/web/teaching.php';
require __DIR__.'/web/maintenance.php';
require __DIR__.'/web/examinations.php';
