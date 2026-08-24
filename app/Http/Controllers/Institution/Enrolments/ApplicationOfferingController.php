<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Enrolments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\Enrolments\SyncApplicationOfferingsRequest;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Services\Applications\ApplicationOfferingSyncService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationOfferingController extends Controller
{
    public function __construct(
        protected ApplicationOfferingSyncService $syncService,
    ) {}

    public function index(): Response
    {
        $this->authorize('manage:online-application-catalogue');

        return Inertia::render('institution/enrolments/catalogue/Index', [
            'departments' => $this->catalogueDepartments(),
        ]);
    }

    public function show(InstitutionDepartment $institution_department): Response
    {
        $this->authorize('manage:online-application-catalogue');

        $institution_department->load([
            'department',
            'departmentLevels.level',
            'departmentCourses.course',
            'departmentCourses.departmentCourseLevels',
        ]);

        $offering = ApplicationOfferingDepartment::query()
            ->where('institution_department_id', $institution_department->id)
            ->with(['levels.courses.modes'])
            ->first();

        $offeredLevels = [];
        if ($offering !== null) {
            foreach ($offering->levels as $level) {
                $courses = [];
                foreach ($level->courses as $course) {
                    $courses[] = [
                        'department_course_id' => (int) $course->department_course_id,
                        'mode_of_study_ids' => $course->modes->pluck('mode_of_study_id')->map(fn ($id) => (int) $id)->values()->all(),
                    ];
                }
                $offeredLevels[] = [
                    'department_level_id' => (int) $level->department_level_id,
                    'courses' => $courses,
                ];
            }
        }

        $levelLinks = DepartmentLevelCourse::query()
            ->whereIn('department_level_id', $institution_department->departmentLevels->pluck('id'))
            ->get()
            ->groupBy('department_course_id');

        $availableCourses = $institution_department->departmentCourses
            ->sortBy(fn ($dc) => (string) ($dc->course?->name ?? ''))
            ->values()
            ->map(function ($departmentCourse) use ($institution_department, $levelLinks) {
                $linkedLevelIds = collect($levelLinks->get($departmentCourse->id, []))
                    ->pluck('department_level_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $levels = $institution_department->departmentLevels
                    ->whereIn('id', $linkedLevelIds)
                    ->sortBy(fn ($dl) => (int) ($dl->level?->position ?? 0))
                    ->values()
                    ->map(fn ($dl) => [
                        'id' => (int) $dl->id,
                        'name' => (string) ($dl->level?->name ?? ''),
                    ])
                    ->all();

                return [
                    'id' => (int) $departmentCourse->id,
                    'name' => (string) ($departmentCourse->course?->name ?? ''),
                    'levels' => $levels,
                ];
            })
            ->filter(fn (array $course) => $course['levels'] !== [])
            ->values()
            ->all();

        return Inertia::render('institution/enrolments/catalogue/Show', [
            'department' => [
                'id' => (int) $institution_department->id,
                'name' => (string) ($institution_department->department?->name ?? ''),
                'departmentCode' => (string) ($institution_department->department_code ?? ''),
                'colorCode' => $institution_department->color_code,
            ],
            'offering' => [
                'enabled' => $offering !== null,
                'has_apprentice_programmes' => (bool) ($offering?->has_apprentice_programmes ?? false),
                'levels' => $offeredLevels,
            ],
            'availableCourses' => $availableCourses,
            'modesOfStudy' => ModeOfStudy::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ModeOfStudy $mode) => [
                    'id' => (int) $mode->id,
                    'name' => (string) $mode->name,
                ])
                ->values()
                ->all(),
            'navigationDepartments' => $this->catalogueDepartments(),
        ]);
    }

    public function update(
        SyncApplicationOfferingsRequest $request,
        InstitutionDepartment $institution_department,
    ): RedirectResponse {
        $this->authorize('manage:online-application-catalogue');

        $this->syncService->sync($institution_department, $request->validated());

        return back()->with('success', __('application_offerings.saved'));
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     departmentCode: string,
     *     colorCode: string|null,
     *     enabled: bool,
     *     hasApprenticeProgrammes: bool,
     *     levelsCount: int
     * }>
     */
    private function catalogueDepartments(): array
    {
        return InstitutionDepartment::query()
            ->with(['department'])
            ->whereHas('department', fn ($q) => $q->where('is_academic', true))
            ->orderBy('id')
            ->get()
            ->map(function (InstitutionDepartment $department) {
                $offering = ApplicationOfferingDepartment::query()
                    ->where('institution_department_id', $department->id)
                    ->withCount('levels')
                    ->first();

                return [
                    'id' => (int) $department->id,
                    'name' => (string) ($department->department?->name ?? ''),
                    'departmentCode' => (string) ($department->department_code ?? ''),
                    'colorCode' => $department->color_code,
                    'enabled' => $offering !== null,
                    'hasApprenticeProgrammes' => (bool) ($offering?->has_apprentice_programmes ?? false),
                    'levelsCount' => (int) ($offering?->levels_count ?? 0),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['name']), SORT_NATURAL)
            ->values()
            ->all();
    }
}
