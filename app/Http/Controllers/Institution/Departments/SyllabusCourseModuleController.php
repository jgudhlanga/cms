<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\SyllabusCourseModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\SyllabusCourseModuleRequest;
use App\Http\Resources\Institution\SyllabusCourseModuleResource;
use App\Models\Institution\CourseSyllabus;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\SyllabusCourseModule;
use App\Repositories\Institution\interface\ISyllabusCourseModuleRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SyllabusCourseModuleController extends Controller
{
    public function __construct(protected ISyllabusCourseModuleRepository $repository) {}

    public function index(InstitutionDepartment $institutionDepartment, CourseSyllabus $courseSyllabus): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SyllabusCourseModule::class);
        $this->authorize('view', $courseSyllabus);

        return SyllabusCourseModuleResource::collection(
            $this->repository->allByCourseSyllabus((int) $courseSyllabus->id)
        );
    }

    public function store(SyllabusCourseModuleRequest $request): void
    {
        $this->authorize('create', SyllabusCourseModule::class);
        $courseSyllabus = CourseSyllabus::query()->findOrFail((int) $request->integer('course_syllabus_id'));
        $this->authorize('update', $courseSyllabus);
        $dto = SyllabusCourseModuleDto::fromRequest($request);

        $this->repository->create($dto);
    }

    public function update(SyllabusCourseModuleRequest $request, SyllabusCourseModule $syllabusCourseModule): void
    {
        $this->authorize('update', $syllabusCourseModule);
        $courseSyllabus = CourseSyllabus::query()->findOrFail((int) $request->integer('course_syllabus_id'));
        $this->authorize('update', $courseSyllabus);

        abort_if(
            (int) $syllabusCourseModule->course_syllabus_id !== (int) $courseSyllabus->id,
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            'The selected module does not belong to this course syllabus.'
        );

        $dto = SyllabusCourseModuleDto::fromRequest($request);

        $this->repository->update($syllabusCourseModule, $dto);
    }
}
