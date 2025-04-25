<?php

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AclController extends Controller
{

	public function __invoke()
	{
		return Inertia::render('acl/Index', []);
	}
}
