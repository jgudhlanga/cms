<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\DTO\Documents\OfferLetterAssembly;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use App\Services\Students\StudentOfferLetterService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OfferLetterAssembler
{
    public function __construct(
        private readonly OfferLetterTemplateResolver $templateResolver,
        private readonly StudentOfferLetterService $offerLetterService,
    ) {}

    public function assemble(
        StudentApplication $studentApplication,
        bool $requireVerifiedClassList = true,
        ?OfferLetterTemplate $forcedTemplate = null,
    ): OfferLetterAssembly {
        $query = StudentApplication::query()
            ->with([
                'student.user',
                'intakePeriod',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
                'programmeStage',
            ])
            ->whereKey($studentApplication->id);

        if ($requireVerifiedClassList) {
            $query->whereHas(
                'classList',
                fn ($classListQuery) => $classListQuery->whereIn('type', [
                    ClassListTypeEnum::VERIFIED->value,
                    ClassListTypeEnum::FINAL->value,
                ]),
            );
        }

        $studentApplication = $query->firstOrFail();
        $student = $studentApplication->student;
        $user = $student?->user;

        if ($student === null || $user === null) {
            throw (new ModelNotFoundException)->setModel(StudentApplication::class);
        }

        $studentIdNumber = (int) $student->id_type_id === IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()
            ? (string) $student->passport_number
            : (string) $student->id_number;

        $levelName = (string) ($studentApplication->departmentLevel?->level?->name ?? '');
        $stageName = trim((string) ($studentApplication->programmeStage?->name ?? ''));
        $level = $stageName !== '' ? $stageName : $levelName;
        $course = (string) ($studentApplication->departmentCourse?->course?->name ?? '');
        $modeOfStudy = (string) ($studentApplication->modeOfStudy?->name ?? '');
        $department = (string) ($studentApplication->institutionDepartment?->department?->name ?? '');
        $intakePeriod = (string) ($studentApplication->intakePeriod?->name ?? '');

        $documentTemplate = $forcedTemplate instanceof OfferLetterTemplate
            ? $forcedTemplate
            : $this->templateResolver->resolve($studentApplication);

        $tuition = $this->resolveTuition($studentApplication, $documentTemplate);

        $offerLetterDate = $this->offerLetterService->issuedAt($studentApplication)?->format('d M Y');

        return new OfferLetterAssembly(
            documentTemplate: $documentTemplate,
            studentName: (string) $user->full_name,
            studentIdNumber: $studentIdNumber,
            studentNumber: (string) $student->student_number,
            intakePeriod: $intakePeriod,
            department: $department,
            level: $level,
            course: $course,
            modeOfStudy: $modeOfStudy,
            tuition: $tuition,
            offerLetterDate: $offerLetterDate,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function previewViewData(OfferLetterTemplate $template, string $generatedAt): array
    {
        $sampleApplication = $this->sampleApplicationFor($template);

        if ($sampleApplication instanceof StudentApplication) {
            return $this->assemble($sampleApplication, false, $template)->viewData($generatedAt);
        }

        $template->loadMissing(['intakePeriod', 'institutionDepartments.department', 'levels', 'course', 'modeOfStudy']);

        return (new OfferLetterAssembly(
            documentTemplate: $template,
            studentName: 'SAMPLE STUDENT',
            studentIdNumber: '00-000000-A-00',
            studentNumber: 'H000000',
            intakePeriod: $template->intakePeriod?->name ?? 'Intake',
            department: $template->institutionDepartments->first()?->department?->name ?? 'Department',
            level: $template->levels->first()?->name ?? 'Level',
            course: $template->course?->name ?? 'Course',
            modeOfStudy: $template->modeOfStudy?->name ?? 'Full Time',
            tuition: $template->tuition_override !== null
                ? number_format((float) $template->tuition_override, 2, '.', '')
                : '0.00',
            offerLetterDate: null,
        ))->viewData($generatedAt);
    }

    private function resolveTuition(
        StudentApplication $studentApplication,
        OfferLetterTemplate $documentTemplate,
    ): string {
        if ($documentTemplate->tuition_override !== null) {
            return number_format((float) $documentTemplate->tuition_override, 2, '.', '');
        }

        $tuitionFeeType = FeeType::query()->where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = $tuitionFeeType === null
            ? null
            : FeeStructure::query()
                ->where('tenant_id', $studentApplication->tenant_id)
                ->where('level_id', $studentApplication->departmentLevel?->level?->id)
                ->where('mode_of_study_id', $studentApplication->mode_of_study_id)
                ->where('fee_type_id', $tuitionFeeType->id)
                ->first();

        return number_format((float) ($feeStructure?->local_fca_amount ?? 0), 2, '.', '');
    }

    private function sampleApplicationFor(OfferLetterTemplate $template): ?StudentApplication
    {
        $template->loadMissing(['institutionDepartments', 'levels']);

        $query = StudentApplication::query()
            ->where('tenant_id', $template->tenant_id)
            ->whereHas(
                'classList',
                fn ($classListQuery) => $classListQuery->whereIn('type', [
                    ClassListTypeEnum::VERIFIED->value,
                    ClassListTypeEnum::FINAL->value,
                ]),
            );

        if ($template->intake_period_id) {
            $query->where('intake_period_id', $template->intake_period_id);
        }

        $departmentIds = $template->departmentIds();
        if ($departmentIds !== []) {
            $query->whereIn('institution_department_id', $departmentIds);
        }

        $levelIds = $template->levelIds();
        if ($levelIds !== []) {
            $query->whereHas(
                'departmentLevel',
                fn ($levelQuery) => $levelQuery->whereIn('level_id', $levelIds),
            );
        }

        if ($template->course_id) {
            $query->whereHas(
                'departmentCourse',
                fn ($courseQuery) => $courseQuery->where('course_id', $template->course_id),
            );
        }

        if ($template->mode_of_study_id) {
            $query->where('mode_of_study_id', $template->mode_of_study_id);
        }

        return $query->first();
    }
}
