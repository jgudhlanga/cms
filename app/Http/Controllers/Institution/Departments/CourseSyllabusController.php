<?php

namespace App\Http\Controllers\Institution\Departments;

use App\DTO\Institution\CourseSyllabusDto;
use App\Exports\Institution\CourseSyllabusImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\CourseSyllabusImportPreviewRequest;
use App\Http\Requests\Institution\CourseSyllabusImportProcessRequest;
use App\Http\Requests\Institution\CourseSyllabusRequest;
use App\Http\Resources\Institution\CourseSyllabusResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Repositories\Institution\interface\ICourseSyllabusRepository;
use App\Services\Institution\CourseSyllabusImportService;
use App\Services\Institution\CourseSyllabusImportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

    public function showImport(
        InstitutionDepartment $institutionDepartment,
    ): Response {
        $this->authorize('import', CourseSyllabus::class);

        $institutionDepartment->loadMissing('department');

        return Inertia::render('institution/syllabus/Import', [
            'institutionDepartment' => new InstitutionDepartmentResource($institutionDepartment),
            'syllabusImportResult' => session('syllabusImportResult'),
        ]);
    }

    public function downloadImportTemplate(
        InstitutionDepartment $institutionDepartment,
        CourseSyllabusImportTemplateService $templateService,
    ): BinaryFileResponse {
        $this->authorize('import', CourseSyllabus::class);

        $data = $templateService->assemble((int) $institutionDepartment->id);

        return Excel::download(
            new CourseSyllabusImportTemplateExport($data),
            $templateService->downloadFileName($institutionDepartment),
        );
    }

    public function previewImport(
        InstitutionDepartment $institutionDepartment,
        CourseSyllabusImportPreviewRequest $request,
        CourseSyllabusImportService $importService,
    ): JsonResponse {
        $this->authorize('import', CourseSyllabus::class);

        return response()->json(
            $importService->preview((int) $institutionDepartment->id, $request->file('file')),
        );
    }

    public function processImport(
        InstitutionDepartment $institutionDepartment,
        CourseSyllabusImportProcessRequest $request,
        CourseSyllabusImportService $importService,
    ): RedirectResponse {
        $this->authorize('import', CourseSyllabus::class);

        $validated = $request->validated();
        $rowCorrections = isset($validated['row_corrections']) && is_array($validated['row_corrections'])
            ? $validated['row_corrections']
            : null;
        $excludedRowNumbers = isset($validated['excluded_row_numbers']) && is_array($validated['excluded_row_numbers'])
            ? $validated['excluded_row_numbers']
            : null;

        $result = $importService->processFromPreview(
            (int) $institutionDepartment->id,
            (string) $validated['preview_token'],
            $rowCorrections,
            $excludedRowNumbers,
        );

        return to_route('department-course-syllabuses.import', [
            'institution_department' => $institutionDepartment->id,
        ])
            ->with('syllabusImportResult', $result)
            ->with('success', __('syllabus.import_success', [
                'succeeded' => $result['rowsSucceeded'],
                'failed' => $result['rowsFailed'],
                'skipped' => $result['rowsSkipped'],
            ]));
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

    public function store(CourseSyllabusRequest $request): RedirectResponse
    {
        $this->authorize('create', CourseSyllabus::class);
        $dto = CourseSyllabusDto::fromRequest($request);

        $courseSyllabus = DB::transaction(function () use ($request, $dto): CourseSyllabus {
            $courseSyllabus = $this->repository->create($dto);
            $this->attachSyllabusDocumentIfPresent($request, $courseSyllabus);

            return $courseSyllabus;
        });

        return to_route('department-course-syllabuses.show', [
            'institution_department' => $dto->institution_department_id,
            'course_syllabus' => $courseSyllabus->id,
        ]);
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

    public function update(CourseSyllabusRequest $request, CourseSyllabus $courseSyllabus): RedirectResponse
    {
        $this->authorize('update', $courseSyllabus);
        $dto = CourseSyllabusDto::fromRequest($request);

        DB::transaction(function () use ($request, $courseSyllabus, $dto): void {
            $this->repository->update($courseSyllabus, $dto);
            $courseSyllabus->refresh();
            $this->attachSyllabusDocumentIfPresent($request, $courseSyllabus);
        });

        return to_route('department-course-syllabuses.show', [
            'institution_department' => $courseSyllabus->institution_department_id,
            'course_syllabus' => $courseSyllabus->id,
        ]);
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

    public function syllabusDocument(InstitutionDepartment $institutionDepartment, CourseSyllabus $courseSyllabus): SymfonyResponse
    {
        $this->authorize('view', $courseSyllabus);

        $media = $courseSyllabus->syllabusDocument
            ?? $courseSyllabus->getFirstMedia(CourseSyllabus::MEDIA_COLLECTION_SYLLABUS_DOCUMENT);

        if ($media === null) {
            abort(404);
        }

        $headers = [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
        ];

        $isPdfDocument = Str::endsWith(Str::lower($media->file_name), '.pdf')
            || Str::contains(Str::lower((string) $media->mime_type), 'pdf');

        if ($isPdfDocument) {
            $headers['Content-Disposition'] = sprintf('inline; filename="%s"', $media->file_name);

            return response()->stream(
                static function () use ($media): void {
                    readfile($media->getPath());
                },
                200,
                $headers
            );
        }

        return response()->download($media->getPath(), $media->file_name, $headers);
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
