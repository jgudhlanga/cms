<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Students\StudentClearanceSection;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentClearance;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSponsor;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Institution\InstitutionFeatureService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentExamResultAccessService
{
    public function __construct(
        private readonly InstitutionFeatureService $featureService,
        private readonly StudentFeeClearanceService $feeClearanceService,
        private readonly ReturningStudentContextService $returningStudentContextService,
    ) {}

    /**
     * @return array{
     *     canViewResults: bool,
     *     gate: 'clearance'|'fees'|'apprentice'|'sponsored'|'not_enrolled'|'non_hexco',
     *     allowOnlineClearance: bool,
     *     fees: array<string, mixed>|null,
     *     clearance: array<string, mixed>|null,
     *     idValidation: array{required: bool, isValid: bool, needsCorrection: bool},
     *     academicCalendarId: int|null,
     *     calendarYear: int|null,
     *     semesterId: int|null,
     *     calendarType: string
     * }
     */
    public function evaluate(Student $student): array
    {
        $allowOnlineClearance = $this->featureService->allowsOnlineClearance((int) $student->tenant_id);
        $enrolment = $this->resolveEnrolmentContext($student);
        $idValidation = $this->idValidation($student);
        $calendarTypeEnum = $this->resolveCalendarType($enrolment);
        $calendarType = $calendarTypeEnum->value;
        $calendarYear = $this->resolveCalendarYear($enrolment);

        $clearanceStatus = $this->clearanceStatus($student, $enrolment);
        $clearanceForDisplay = $allowOnlineClearance
            ? $clearanceStatus
            : $this->filterClearanceToAccountsOnly($clearanceStatus);

        if ($enrolment === null) {
            return [
                'canViewResults' => false,
                'gate' => 'not_enrolled',
                'allowOnlineClearance' => $allowOnlineClearance,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => null,
                'calendarYear' => $calendarYear,
                'semesterId' => null,
                'calendarType' => $calendarType,
            ];
        }

        if (in_array($calendarTypeEnum, [
            AcademicCalendarTypeEnum::ABMA,
            AcademicCalendarTypeEnum::TERM,
        ], true)) {
            return [
                'canViewResults' => false,
                'gate' => 'non_hexco',
                'allowOnlineClearance' => $allowOnlineClearance,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => $enrolment->academic_calendar_id,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
                'calendarType' => $calendarType,
            ];
        }

        if ($allowOnlineClearance) {
            return [
                'canViewResults' => $clearanceStatus['isFullyCleared'] && ! $idValidation['needsCorrection'],
                'gate' => 'clearance',
                'allowOnlineClearance' => true,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => $enrolment->academic_calendar_id,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
                'calendarType' => $calendarType,
            ];
        }

        if ($clearanceStatus['accountsCleared']) {
            return [
                'canViewResults' => ! $idValidation['needsCorrection'],
                'gate' => 'clearance',
                'allowOnlineClearance' => false,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => $enrolment->academic_calendar_id,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
                'calendarType' => $calendarType,
            ];
        }

        if ($this->isApprenticeExemptFromSchoolFees($student, $calendarYear)) {
            return [
                'canViewResults' => ! $idValidation['needsCorrection'],
                'gate' => 'apprentice',
                'allowOnlineClearance' => false,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => $enrolment->academic_calendar_id,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
                'calendarType' => $calendarType,
            ];
        }

        if ($this->isSponsoredExemptFromSchoolFees($student, $calendarYear)) {
            return [
                'canViewResults' => ! $idValidation['needsCorrection'],
                'gate' => 'sponsored',
                'allowOnlineClearance' => false,
                'fees' => null,
                'clearance' => $clearanceForDisplay,
                'idValidation' => $idValidation,
                'academicCalendarId' => $enrolment->academic_calendar_id,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
                'calendarType' => $calendarType,
            ];
        }

        $fees = $this->feeClearanceService->evaluate($student);

        return [
            'canViewResults' => $fees['isFullyPaid'] && ! $idValidation['needsCorrection'],
            'gate' => 'fees',
            'allowOnlineClearance' => false,
            'fees' => $fees,
            'clearance' => $clearanceForDisplay,
            'idValidation' => $idValidation,
            'academicCalendarId' => $enrolment->academic_calendar_id,
            'calendarYear' => $calendarYear,
            'semesterId' => $this->resolveSemesterId($enrolment),
            'calendarType' => $calendarType,
        ];
    }

    /**
     * Current-year apprentices are exempt from the exam-results school-fees gate
     * (employer pays tuition in bulk). Does not alter fee clearance / payment flows.
     */
    private function isApprenticeExemptFromSchoolFees(Student $student, ?int $calendarYear): bool
    {
        if ($calendarYear !== null) {
            if ($student->relationLoaded('apprentices')) {
                return $student->apprentices->contains(
                    fn (StudentApprentice $apprentice): bool => (int) $apprentice->calendar_year === $calendarYear
                );
            }

            return StudentApprentice::query()
                ->where('student_id', $student->id)
                ->where('calendar_year', $calendarYear)
                ->exists();
        }

        return $this->returningStudentContextService->currentApprenticeForStudentProfile($student) instanceof StudentApprentice;
    }

    /**
     * Current-year sponsored students are exempt from the exam-results school-fees gate
     * (sponsor pays tuition in bulk). Does not alter fee clearance / payment flows.
     */
    private function isSponsoredExemptFromSchoolFees(Student $student, ?int $calendarYear): bool
    {
        if ($calendarYear !== null) {
            if ($student->relationLoaded('studentSponsors')) {
                return $student->studentSponsors->contains(
                    fn (StudentSponsor $sponsor): bool => (int) $sponsor->calendar_year === $calendarYear
                );
            }

            return StudentSponsor::query()
                ->where('student_id', $student->id)
                ->where('calendar_year', $calendarYear)
                ->exists();
        }

        return $this->returningStudentContextService->currentSponsorForStudentProfile($student) instanceof StudentSponsor;
    }

    /**
     * @return array{
     *     isFullyCleared: bool,
     *     accountsCleared: bool,
     *     pendingSections: list<string>,
     *     sections: list<array{key: string, label: string, cleared: bool}>,
     *     recordId: int|null,
     *     calendarYear: int|null,
     *     semesterId: int|null
     * }
     */
    public function clearanceStatus(Student $student, ?StudentEnrolment $enrolment = null): array
    {
        $enrolment ??= $this->resolveEnrolmentContext($student);
        $sections = [];
        $calendarYear = $this->resolveCalendarYear($enrolment);

        foreach (StudentClearanceSection::all() as $section) {
            $sections[] = [
                'key' => $section->value,
                'label' => $section->label(),
                'cleared' => false,
            ];
        }

        $semesterId = $this->resolveSemesterId($enrolment);

        if ($enrolment === null || $calendarYear === null || $semesterId === null) {
            return [
                'isFullyCleared' => false,
                'accountsCleared' => false,
                'pendingSections' => array_column($sections, 'key'),
                'sections' => $sections,
                'recordId' => null,
                'calendarYear' => $calendarYear,
                'semesterId' => null,
            ];
        }

        $clearance = StudentClearance::query()
            ->where('student_id', $student->id)
            ->where('calendar_year', $calendarYear)
            ->where('semester_id', $semesterId)
            ->first();

        if ($clearance === null) {
            return [
                'isFullyCleared' => false,
                'accountsCleared' => false,
                'pendingSections' => array_column($sections, 'key'),
                'sections' => $sections,
                'recordId' => null,
                'calendarYear' => $calendarYear,
                'semesterId' => $this->resolveSemesterId($enrolment),
            ];
        }

        $sections = [];
        foreach (StudentClearanceSection::all() as $section) {
            $sections[] = [
                'key' => $section->value,
                'label' => $section->label(),
                'cleared' => (bool) $clearance->getAttribute($section->clearedColumn()),
            ];
        }

        return [
            'isFullyCleared' => $clearance->isFullyCleared(),
            'accountsCleared' => $clearance->isAccountsCleared(),
            'pendingSections' => $clearance->pendingSections(),
            'sections' => $sections,
            'recordId' => $clearance->id,
            'calendarYear' => $calendarYear,
            'semesterId' => $this->resolveSemesterId($enrolment),
        ];
    }

    /**
     * @return array{required: bool, isValid: bool, needsCorrection: bool}
     */
    public function idValidation(Student $student): array
    {
        if (! $student->isZimbabwean()) {
            return [
                'required' => false,
                'isValid' => true,
                'needsCorrection' => false,
            ];
        }

        $isValid = ZimbabweanIdNumber::isValid($student->id_number);

        return [
            'required' => true,
            'isValid' => $isValid,
            'needsCorrection' => ! $isValid,
        ];
    }

    public function resolveEnrolmentContext(Student $student): ?StudentEnrolment
    {
        /** @var Collection<int, StudentEnrolment> $enrolments */
        $enrolments = StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->with([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse',
                'modeOfStudy',
                'academicCalendar',
                'semester',
                'studentEnrolmentStatus',
                'studentSemesters.semester',
            ])
            ->get();

        if ($enrolments->isEmpty()) {
            return null;
        }

        $active = $enrolments
            ->filter(fn (StudentEnrolment $enrolment): bool => $this->isActiveEnrolmentStatus(
                $enrolment->studentEnrolmentStatus?->slug ?? $enrolment->studentEnrolmentStatus?->name
            ))
            ->sortByDesc(fn (StudentEnrolment $enrolment): array => $this->enrolmentSortKey($enrolment))
            ->first();

        if ($active !== null) {
            return $active;
        }

        return $enrolments
            ->sortByDesc(fn (StudentEnrolment $enrolment): array => $this->enrolmentSortKey($enrolment))
            ->first();
    }

    public function resolveCalendarType(?StudentEnrolment $enrolment): AcademicCalendarTypeEnum
    {
        $fromLevel = $enrolment?->departmentLevel?->level?->calendar_type;

        if ($fromLevel instanceof AcademicCalendarTypeEnum) {
            return $fromLevel;
        }

        if (is_string($fromLevel) && $fromLevel !== '') {
            $parsed = AcademicCalendarTypeEnum::tryFrom($fromLevel);
            if ($parsed instanceof AcademicCalendarTypeEnum) {
                return $parsed;
            }
        }

        $fromCalendar = $enrolment?->academicCalendar?->type;

        if ($fromCalendar instanceof AcademicCalendarTypeEnum) {
            return $fromCalendar;
        }

        return AcademicCalendarTypeEnum::SEMESTER;
    }

    public function resolveCalendarYear(?StudentEnrolment $enrolment): ?int
    {
        return $this->parseCalendarYear($enrolment?->academicCalendar?->calendar_year);
    }

    public function parseCalendarYear(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 2000 && $value <= 2100 ? $value : null;
        }

        if (is_numeric($value)) {
            $year = (int) $value;

            return $year >= 2000 && $year <= 2100 ? $year : null;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match_all('/(20\d{2}|19\d{2})/', $value, $matches) >= 1) {
            $years = array_map('intval', $matches[1]);
            $year = max($years);

            return $year >= 2000 && $year <= 2100 ? $year : null;
        }

        return null;
    }

    private function isActiveEnrolmentStatus(?string $status): bool
    {
        return Str::lower(trim((string) $status)) === 'active';
    }

    /**
     * @param array{
     *     isFullyCleared: bool,
     *     accountsCleared: bool,
     *     pendingSections: list<string>,
     *     sections: list<array{key: string, label: string, cleared: bool}>,
     *     recordId: int|null,
     *     calendarYear: int|null,
     *     semesterId: int|null
     * } $clearanceStatus
     * @return array{
     *     isFullyCleared: bool,
     *     accountsCleared: bool,
     *     pendingSections: list<string>,
     *     sections: list<array{key: string, label: string, cleared: bool}>,
     *     recordId: int|null,
     *     calendarYear: int|null,
     *     semesterId: int|null
     * }
     */
    private function filterClearanceToAccountsOnly(array $clearanceStatus): array
    {
        $accountsKey = StudentClearanceSection::Accounts->value;

        $clearanceStatus['sections'] = array_values(array_filter(
            $clearanceStatus['sections'],
            fn (array $section): bool => $section['key'] === $accountsKey,
        ));

        $clearanceStatus['pendingSections'] = array_values(array_filter(
            $clearanceStatus['pendingSections'],
            fn (string $key): bool => $key === $accountsKey,
        ));

        return $clearanceStatus;
    }

    private function resolveSemesterId(?StudentEnrolment $enrolment): ?int
    {
        if ($enrolment === null) {
            return null;
        }

        $current = $enrolment->currentStudentSemester();

        return $current?->semester_id ?? $enrolment->semester_id;
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function enrolmentSortKey(StudentEnrolment $enrolment): array
    {
        $openingDate = $enrolment->academicCalendar?->opening_date;

        if ($openingDate instanceof CarbonInterface) {
            $openingDate = $openingDate->toDateString();
        }

        return [
            (string) ($enrolment->academicCalendar?->calendar_year ?? ''),
            (string) ($openingDate ?? ''),
            (int) $enrolment->id,
        ];
    }
}
