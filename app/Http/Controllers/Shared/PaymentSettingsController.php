<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PaymentSettingsController extends Controller
{

	public function __invoke()
	{
		return Inertia::render('shared/payments/Index', []);
	}
}
