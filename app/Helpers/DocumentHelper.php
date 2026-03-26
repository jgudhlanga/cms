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
use App\Models\Institution\IntakePeriod;
use App\Models\Shared\DocumentType;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;

class DocumentHelper
{
    public static function assembleOfferLetter(StudentProgram $studentProgram): array
    {
        $studentProgram = StudentProgram::query()
            ->with([
                'student.user',
                'intakePeriod',
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'modeOfStudy',
            ])
            ->where('id', $studentProgram->id)
            ->whereHas('classList', fn ($q) => $q->where('type', 'verified'))->firstOrFail();
        $student = $studentProgram->student;
        $user = $student->user;

        // Determine correct ID number (national vs passport)
        $studentIdNumber = $student->id_type_id == IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()
            ? $student->passport_number
            : $student->id_number;

        // Extract student program info
        $studentName = $user->full_name;
        $studentNumber = $student->student_number;
        $intakePeriod = $studentProgram->intakePeriod->name ?? '';
        $department = $studentProgram->institutionDepartment->department->name ?? '';
        $level = $studentProgram->departmentLevel->level->name ?? '';
        $course = $studentProgram->departmentCourse->course->name ?? '';
        $modeOfStudy = $studentProgram->modeOfStudy->name ?? '';

        // Tuition Lookup
        $tuitionFeeType = FeeType::where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = FeeStructure::query()
            ->where('tenant_id', $studentProgram->tenant_id)
            ->where('level_id', $studentProgram->departmentLevel->level->id ?? null)
            ->where('mode_of_study_id', $studentProgram->modeOfStudy->id ?? null)
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
        }
        // Base query
        $query = DocumentTemplate::query()
            // ->where('intake_period_id', $studentProgram->intakePeriod->id ?? null)
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
}
