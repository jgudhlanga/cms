<?php

namespace App\Http\Controllers\Institution\DocumentTemplates;

use App\DTO\DocumentTemplates\DocumentTemplateDto;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\DocumentTemplates\DocumentTemplateRequest;
use App\Http\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\FeeStructure;
use App\Models\Shared\FeeType;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Repositories\Institution\interface\IDocumentTemplateRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Throwable;
use Barryvdh\DomPDF\Facade\Pdf;


class DocumentTemplateController extends Controller
{
    public function __construct(protected IDocumentTemplateRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
        $this->authorize('viewAny', DocumentTemplate::class);
        $documentTemplates = DocumentTemplateResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/document-templates/Index', [
            'documentTemplates' => $documentTemplates,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('create', DocumentTemplate::class);
        return Inertia::render('institution/document-templates/Create');
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(DocumentTemplateRequest $request)
    {
        $this->authorize('create', DocumentTemplate::class);
        DB::transaction(function () use ($request) {
            $template = $this->repository->create(DocumentTemplateDto::fromDocumentTemplateRequest($request));
            $this->uploadLogos($request, $template);
        });
        return to_route('document-templates.index');
    }

    /**
     * @throws AuthorizationException
     */
    public function show(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('view', $documentTemplate);
        //
    }

    public function edit(DocumentTemplate $documentTemplate): Response
    {
        $this->authorize('update', $documentTemplate);
        $documentTemplate = DocumentTemplateResource::make($documentTemplate);
        return Inertia::render('institution/document-templates/Edit', compact('documentTemplate'));
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    public function update(DocumentTemplateRequest $request, DocumentTemplate $documentTemplate)
    {

        $this->authorize('update', $documentTemplate);
        DB::transaction(function () use ($request, $documentTemplate) {
            $template = $this->repository->update($documentTemplate, DocumentTemplateDto::fromDocumentTemplateRequest($request));
            $this->uploadLogos($request, $template);
        });
        return to_route('document-templates.index');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('delete', $documentTemplate);
        $this->repository->delete($documentTemplate);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $documentTemplate = $this->repository->findTrashed($id);
        $this->authorize('restore', $documentTemplate);
        $this->repository->restore($documentTemplate);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DocumentTemplate $documentTemplate): void
    {
        $this->authorize('forceDelete', $documentTemplate);
        $this->repository->delete($documentTemplate, true);
    }

    public function preview(DocumentTemplate $documentTemplate)
    {
        $this->authorize('view', $documentTemplate);

        $studentApplication = StudentApplication::join('class_lists', 'student_applications.id', '=', 'class_lists.student_application_id')
            ->where('class_lists.type', 'verified')
            ->select('student_applications.*')
            ->first();
        $student = $studentApplication->student;
        $user = $student->user;

        $studentName = $user->full_name;
        $studentIdNumber = $student->id_number;
        if ($student->id_type_id == IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()) {
            $studentIdNumber = $student->passport_number;
        }
        $studentNumber = $student->student_number;
        $intakePeriod = $studentApplication?->intakePeriod?->name;
        $department = $studentApplication?->institutionDepartment?->department?->name;
        $level = $studentApplication?->departmentLevel?->level?->name;
        $course = $studentApplication?->departmentCourse?->course?->name;
        $modeOfStudy = $studentApplication?->modeOfStudy?->name;

        $tuitionFeeType = FeeType::where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = FeeStructure::where('tenant_id', $studentApplication->tenant_id)->where('level_id', $studentApplication?->departmentLevel?->level?->id)
            ->where('mode_of_study_id', $studentApplication?->modeOfStudy->id)->where('fee_type_id', $tuitionFeeType?->id)->first();
        $tuition = $feeStructure?->local_fca_amount ?? 0;
        $nameParts = array_filter([$user->first_name,$user->middle_name,$user->last_name]);
        $fileName =  implode('_', $nameParts).'_offer_letter_' . time() . '.pdf';
        $pdf = Pdf::loadView('students.offer-letter',
            compact('documentTemplate', 'studentName', 'studentIdNumber', 'studentNumber',
                'intakePeriod', 'department', 'level', 'course', 'modeOfStudy', 'tuition'));
        return $pdf->stream($fileName);
    }

    /**
     * @param DocumentTemplateRequest $request
     * @param DocumentTemplate $template
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function uploadLogos(DocumentTemplateRequest $request, DocumentTemplate $template): void
    {
        if ($request->hasFile('header_logo_1') && $request->file('header_logo_1')->isValid() && $request->file('header_logo_1')->getSize() > 0) {
            $media = $template->addMedia($request->file('header_logo_1'))
                ->toMediaCollection('logo-1');
            $template->update(['header_logo_1' => $media->id]);
        }

        if ($request->hasFile('header_logo_2') && $request->file('header_logo_2')->isValid() && $request->file('header_logo_2')->getSize() > 0) {
            $media = $template->addMedia($request->file('header_logo_2'))
                ->toMediaCollection('logo-2');
            $template->update(['header_logo_2' => $media->id]);
        }
    }
}
