<?php

namespace App\Helpers;

use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\DocumentTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\FeeStructure;
use App\Models\Shared\DocumentType;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use App\Services\Students\IntakePeriodResolver;

class DocumentHelper
{
    public static function assembleOfferLetter(StudentApplication $studentApplication): array
    {
        $studentApplication = StudentApplication::query()
            ->with([
                'student.user',
                'intakePeriod',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
            ])
            ->where('id', $studentApplication->id)
            ->whereHas('classList', fn ($q) => $q->whereIn('type', ['verified', 'final']))->firstOrFail();

        abort_unless(
            app(IntakePeriodResolver::class)->isApplicationInActiveIntake($studentApplication),
            404
        );

        $student = $studentApplication->student;
        $user = $student->user;

        // Determine correct ID number (national vs passport)
        $studentIdNumber = $student->id_type_id == IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()
            ? $student->passport_number
            : $student->id_number;

        // Extract student program info
        $studentName = $user->full_name;
        $studentNumber = $student->student_number;
        $intakePeriod = $studentApplication->intakePeriod->name ?? '';
        $department = $studentApplication->institutionDepartment->department->name ?? '';
        $level = $studentApplication->departmentLevel->level->name ?? '';
        $course = $studentApplication->departmentCourse->course->name ?? '';
        $modeOfStudy = $studentApplication->modeOfStudy->name ?? '';

        // Tuition Lookup
        $tuitionFeeType = FeeType::where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = FeeStructure::query()
            ->where('tenant_id', $studentApplication->tenant_id)
            ->where('level_id', $studentApplication->departmentLevel->level->id ?? null)
            ->where('mode_of_study_id', $studentApplication->modeOfStudy->id ?? null)
            ->where('fee_type_id', $tuitionFeeType->id)
            ->first();

        $tuition = $feeStructure->local_fca_amount ?? 0;

        // Document type
        $documentType = DocumentType::where('name', DocumentTypeEnum::OFFER_LETTER->name())->firstOrFail();

        // USD-only rules
        $usdOnlyLevels = [
            LevelEnum::ABMA_LEVEL_3,
            LevelEnum::ABMA_LEVEL_4,
            LevelEnum::ABMA_LEVEL_5,
            LevelEnum::ABMA_LEVEL_6,
        ];

        $sdpLevels = [LevelEnum::SDP];

        $usdOnlyModes = [
            ModeOfStudyEnum::BLOCK_RELEASE,
        ];

        $isUsdOnly =
            in_array($level, array_map(fn ($l) => $l->name(), $usdOnlyLevels), true)
            || in_array($modeOfStudy, array_map(fn ($m) => $m->label(), $usdOnlyModes), true);

        $isSDP = in_array($level, array_map(fn ($l) => $l->name(), $sdpLevels), true);
        if ($isSDP && strtolower($department) === strtolower(DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->label())) {
            $tuition = '375.00';
            if(strtolower($modeOfStudy) === strtolower(ModeOfStudyEnum::OJET->label())) {
                $tuition = '237.00';
            }
        }
        // Base query
        $query = DocumentTemplate::query()
            // ->where('intake_period_id', $studentApplication->intakePeriod->id ?? null)
            ->where('document_type_id', $documentType->id);

        // Apply USD-only constraint if needed
        $documentTemplate = $query
            ->when($isUsdOnly, fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%usd only%']))
            ->when($isSDP, fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%sdp%']))
            ->firstOrFail();

        return [
            $documentTemplate,
            $studentName,
            $studentIdNumber,
            $studentNumber,
            $intakePeriod,
            $department,
            $level,
            $course,
            $modeOfStudy,
            $tuition,
        ];
    }

    public static function resolvePdfHeaderTemplate(?int $tenantId = null): DocumentTemplate
    {
        $documentType = DocumentType::query()
            ->where('name', DocumentTypeEnum::OFFER_LETTER->name())
            ->first();

        $query = DocumentTemplate::query()->whereNotNull('header_line_1');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($documentType !== null) {
            $offerLetterTemplate = (clone $query)->where('document_type_id', $documentType->id)->first();

            if ($offerLetterTemplate !== null) {
                return $offerLetterTemplate;
            }
        }

        $template = $query->first();

        if ($template !== null) {
            return $template;
        }

        return new DocumentTemplate([
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
            'header_address_line_1' => 'Harare',
            'header_address_line_2' => '',
            'header_telephone' => '',
            'header_email' => '',
            'header_website' => '',
        ]);
    }
}
