<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

class SiteController extends Controller
{
    public function __invoke()
	{
		return Inertia::render('site/Index', [
			'canLogin' => Route::has('login'),
			'canRegister' => Route::has('register'),
			'laravelVersion' => Application::VERSION,
			'phpVersion' => PHP_VERSION,
		]);
	}
}
