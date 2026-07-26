<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RbacController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('root:manage'), 403);

        return Inertia::render('rbac/Index', []);
    }
}
