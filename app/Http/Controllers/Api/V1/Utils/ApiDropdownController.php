<?php

namespace App\Http\Controllers\Api\V1\Utils;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ApiDropdownController extends Controller implements HasMiddleware
{

	public static function middleware(): array
    {
        return [new Middleware('auth:sanctum', except: ['index'])];
    }
}
