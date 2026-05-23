<?php

namespace App\JsonApi\V1\HMS;

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
        return false;
    }

    public function show(Request $request, object $model): bool
    {
        return $request->user() !== null;
    }

    public function update(Request $request, object $model): bool
    {
        return false;
    }

    public function destroy(Request $request, object $model): bool
    {
        return false;
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
