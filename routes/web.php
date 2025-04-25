<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', SiteController::class)->name('home');
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/web/auth.php';
require __DIR__.'/web/users.php';
require __DIR__.'/web/settings.php';
require __DIR__.'/web/acl.php';
require __DIR__.'/web/shared.php';
require __DIR__.'/web/payments.php';
