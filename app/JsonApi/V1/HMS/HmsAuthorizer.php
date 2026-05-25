<?php

namespace App\JsonApi\V1\HMS;

use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class HmsAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool
    {
        return $request->user() !== null;
    }

    public function store(Request $request, string $modelClass): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return match ($modelClass) {
            HostelApplication::class => $user->can('create', HostelApplication::class),
            HmsSetting::class => $user->can('create', HmsSetting::class),
            default => false,
        };
    }

    public function show(Request $request, object $model): bool
    {
        return $request->user() !== null;
    }

    public function update(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return match (true) {
            $model instanceof HostelApplication => $user->can('update', $model),
            $model instanceof HmsSetting => $user->can('update', $model),
            default => false,
        };
    }

    public function destroy(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return match (true) {
            $model instanceof HostelApplication => $user->can('delete', $model),
            $model instanceof HmsSetting => $user->can('delete', $model),
            default => false,
        };
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $request->user() !== null;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user() !== null;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }
}
