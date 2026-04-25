<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\CourseSyllabusModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseSyllabusModuleRequest;
use App\Http\Resources\Institution\CourseSyllabusModuleResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Repositories\Institution\interface\ICourseSyllabusModuleRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CourseSyllabusModuleController extends Controller
{
    public function __construct(protected ICourseSyllabusModuleRepository $repository) {}

    public function index(InstitutionDepartment $institutionDepartment, CourseSyllabus $courseSyllabus): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CourseSyllabusModule::class);
        $this->authorize('view', $courseSyllabus);

        return CourseSyllabusModuleResource::collection(
            $this->repository->allByCourseSyllabus((int) $courseSyllabus->id)
        );
    }

    public function store(CourseSyllabusModuleRequest $request): void
    {
        $this->authorize('create', CourseSyllabusModule::class);
        $courseSyllabus = CourseSyllabus::query()->findOrFail((int) $request->integer('course_syllabus_id'));
        $this->authorize('update', $courseSyllabus);
        $dto = CourseSyllabusModuleDto::fromRequest($request);

        $this->repository->create($dto);
    }

    public function update(CourseSyllabusModuleRequest $request, CourseSyllabusModule $courseSyllabusModule): void
    {
        $this->authorize('update', $courseSyllabusModule);
        $courseSyllabus = CourseSyllabus::query()->findOrFail((int) $request->integer('course_syllabus_id'));
        $this->authorize('update', $courseSyllabus);

        abort_if(
            (int) $courseSyllabusModule->course_syllabus_id !== (int) $courseSyllabus->id,
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            'The selected module does not belong to this course syllabus.'
        );

        $dto = CourseSyllabusModuleDto::fromRequest($request);

        $this->repository->update($courseSyllabusModule, $dto);
    }
}
