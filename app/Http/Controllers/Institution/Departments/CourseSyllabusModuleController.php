<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\CourseSyllabusModuleDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseSyllabusModuleRequest;
use App\Http\Requests\Institution\MoveCourseSyllabusModulesRequest;
use App\Http\Resources\Institution\CourseSyllabusModuleResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Repositories\Institution\interface\ICourseSyllabusModuleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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

    public function moveModules(
        InstitutionDepartment $institutionDepartment,
        CourseSyllabus $courseSyllabus,
        MoveCourseSyllabusModulesRequest $request,
    ): RedirectResponse {
        abort_if(
            (int) $courseSyllabus->institution_department_id !== (int) $institutionDepartment->id,
            SymfonyResponse::HTTP_NOT_FOUND,
        );

        $this->authorize('view', $courseSyllabus);

        $validated = $request->validated();
        /** @var array<int, int> $moduleIds */
        $moduleIds = array_map('intval', $validated['course_syllabus_module_ids']);
        $targetOptionId = (int) $validated['target_academic_year_option_id'];

        $modules = CourseSyllabusModule::query()
            ->where('course_syllabus_id', $courseSyllabus->id)
            ->whereIn('id', $moduleIds)
            ->get();

        foreach ($modules as $module) {
            $this->authorize('update', $module);
        }

        DB::transaction(function () use ($moduleIds, $targetOptionId): void {
            CourseSyllabusModule::query()
                ->whereIn('id', $moduleIds)
                ->update(['academic_year_option_id' => $targetOptionId]);
        });

        return back()->with('success', __('syllabus.move_modules_success'));
    }
}
