<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\AcademicCalendars\CourseWorkAuthorizer;
use App\JsonApi\V1\HMS\HmsAuthorizer;
use App\JsonApi\V1\Students\StudentsAuthorizer;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Students\StudentProgram;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class JsonApiAuthorizer implements Authorizer
{
    public function __construct(
        private readonly HmsAuthorizer $hmsAuthorizer = new HmsAuthorizer,
        private readonly StudentsAuthorizer $studentsAuthorizer = new StudentsAuthorizer,
        private readonly CourseWorkAuthorizer $courseWorkAuthorizer = new CourseWorkAuthorizer,
    ) {}

    private function delegate(string $modelClass): Authorizer
    {
        return match ($modelClass) {
            StudentProgram::class => $this->studentsAuthorizer,
            CourseWorkMark::class => $this->courseWorkAuthorizer,
            default => $this->hmsAuthorizer,
        };
    }

    public function index(Request $request, string $modelClass): bool
    {
        return $this->delegate($modelClass)->index($request, $modelClass);
    }

    public function store(Request $request, string $modelClass): bool
    {
        return $this->delegate($modelClass)->store($request, $modelClass);
    }

    public function show(Request $request, object $model): bool
    {
        return $this->delegate($model::class)->show($request, $model);
    }

    public function update(Request $request, object $model): bool
    {
        return $this->delegate($model::class)->update($request, $model);
    }

    public function destroy(Request $request, object $model): bool
    {
        return $this->delegate($model::class)->destroy($request, $model);
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $this->delegate($model::class)->showRelated($request, $model, $fieldName);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->delegate($model::class)->showRelationship($request, $model, $fieldName);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->delegate($model::class)->updateRelationship($request, $model, $fieldName);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->delegate($model::class)->attachRelationship($request, $model, $fieldName);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->delegate($model::class)->detachRelationship($request, $model, $fieldName);
    }
}
