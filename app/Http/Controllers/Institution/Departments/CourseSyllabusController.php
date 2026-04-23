<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\CourseSyllabusDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseSyllabusRequest;
use App\Http\Resources\Institution\CourseSyllabusResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\CourseSyllabus;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\ICourseSyllabusRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseSyllabusController extends Controller
{
    public function __construct(protected ICourseSyllabusRepository $repository) {}

    public function index(InstitutionDepartment $institutionDepartment): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CourseSyllabus::class);

        return CourseSyllabusResource::collection(
            $this->repository->allByInstitutionDepartment((int) $institutionDepartment->id)
        );
    }

    public function create(InstitutionDepartment $institutionDepartment): Response
    {
        $this->authorize('create', CourseSyllabus::class);

        $institutionDepartment->loadMissing('department');

        return Inertia::render('institution/syllabus/CreateEdit', [
            'institutionDepartment' => new InstitutionDepartmentResource($institutionDepartment),
            'courseSyllabus' => null,
        ]);
    }

    public function store(CourseSyllabusRequest $request): void
    {
        $this->authorize('create', CourseSyllabus::class);
        $dto = CourseSyllabusDto::fromRequest($request);

        DB::transaction(function () use ($request, $dto): void {
            $courseSyllabus = $this->repository->create($dto);
            $this->attachSyllabusDocumentIfPresent($request, $courseSyllabus);
        });
    }

    public function edit(InstitutionDepartment $institutionDepartment, CourseSyllabus $courseSyllabus): Response
    {
        $this->authorize('update', $courseSyllabus);

        $institutionDepartment->loadMissing('department');
        $courseSyllabus->loadMissing([
            'departmentLevelCourse.departmentLevel.level',
            'departmentLevelCourse.departmentCourse.course',
            'syllabusDocument',
        ]);

        return Inertia::render('institution/syllabus/CreateEdit', [
            'institutionDepartment' => new InstitutionDepartmentResource($institutionDepartment),
            'courseSyllabus' => new CourseSyllabusResource($courseSyllabus),
        ]);
    }

    public function update(CourseSyllabusRequest $request, CourseSyllabus $courseSyllabus): void
    {
        $this->authorize('update', $courseSyllabus);
        $dto = CourseSyllabusDto::fromRequest($request);

        DB::transaction(function () use ($request, $courseSyllabus, $dto): void {
            $this->repository->update($courseSyllabus, $dto);
            $courseSyllabus->refresh();
            $this->attachSyllabusDocumentIfPresent($request, $courseSyllabus);
        });
    }

    public function destroy(CourseSyllabus $courseSyllabus): void
    {
        $this->authorize('delete', $courseSyllabus);
        $this->repository->delete($courseSyllabus);
    }

    public function show(InstitutionDepartment $institutionDepartment, CourseSyllabus $courseSyllabus): Response
    {
        $this->authorize('view', $courseSyllabus);

        $institutionDepartment->loadMissing('department');
        $courseSyllabus->loadMissing([
            'departmentLevelCourse.departmentLevel.level',
            'departmentLevelCourse.departmentCourse.course',
            'syllabusDocument',
        ]);

        return Inertia::render('institution/syllabus/Show', [
            'institutionDepartment' => new InstitutionDepartmentResource($institutionDepartment),
            'courseSyllabus' => new CourseSyllabusResource($courseSyllabus),
        ]);
    }

    private function attachSyllabusDocumentIfPresent(CourseSyllabusRequest $request, CourseSyllabus $courseSyllabus): void
    {
        if (! $request->hasFile('syllabus_document')) {
            return;
        }

        $courseSyllabus
            ->addMediaFromRequest('syllabus_document')
            ->toMediaCollection(CourseSyllabus::MEDIA_COLLECTION_SYLLABUS_DOCUMENT);

        $media = $courseSyllabus->getFirstMedia(CourseSyllabus::MEDIA_COLLECTION_SYLLABUS_DOCUMENT);

        if ($media !== null) {
            $courseSyllabus->update(['syllabus_document_id' => $media->id]);
        }
    }
}
