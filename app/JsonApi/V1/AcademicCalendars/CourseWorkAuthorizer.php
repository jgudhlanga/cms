<?php

namespace App\JsonApi\V1\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkMark;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class CourseWorkAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('viewAny', CourseWorkMark::class) ?? false;
    }

    public function store(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('create', CourseWorkMark::class) ?? false;
    }

    public function show(Request $request, object $model): bool
    {
        if (! $model instanceof CourseWorkMark) {
            return false;
        }

        return $request->user()?->can('view', $model) ?? false;
    }

    public function update(Request $request, object $model): bool
    {
        if (! $model instanceof CourseWorkMark) {
            return false;
        }

        return $request->user()?->can('update', $model) ?? false;
    }

    public function destroy(Request $request, object $model): bool
    {
        if (! $model instanceof CourseWorkMark) {
            return false;
        }

        return $request->user()?->can('delete', $model) ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
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
