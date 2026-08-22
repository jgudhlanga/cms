<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\EnrolmentHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeClassListType
{
    public function handle(Request $request, Closure $next): Response
    {
        $type = $request->string('type')->toString();
        $permission = EnrolmentHelper::classListBrowsePermissionForType($type !== '' ? $type : null);
        $user = $request->user();

        if ($permission === null || $user === null || $user->cannot($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
