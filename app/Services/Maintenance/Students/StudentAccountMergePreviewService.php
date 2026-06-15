<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StudentAccountMergePreviewService
{
    public function __construct(
        private readonly EnrollmentLookupService $enrollmentLookup,
    ) {}

    /**
     * @return array{source: Student, target: Student, proposedIdNumber: string}
     */
    public function build(Student $source, int $targetStudentId, string $idNumber): array
    {
        $this->assertFaultySource($source);

        $normalized = EnrollmentLookupService::normalizeNationalId($idNumber);
        $target = $this->resolveTarget($targetStudentId, $normalized);

        if ($target->id === $source->id) {
            throw ValidationException::withMessages([
                'target' => [__('trans.maintenance_faulty_data_merge_same_student')],
            ]);
        }

        $programRelations = [
            'programs' => fn ($query) => $query->with([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'intakePeriod',
                'modeOfStudy',
                'departmentWorkflowStep.workflowStep',
                'classList',
            ]),
        ];

        $source->loadCount([
            'programs',
            'enrolments',
            'contacts',
            'addresses',
            'hostelApplications',
        ])->load(array_merge(['user'], $programRelations));

        $target->loadCount([
            'programs',
            'enrolments',
            'contacts',
            'addresses',
            'hostelApplications',
        ])->load(array_merge(['user'], $programRelations));

        return [
            'source' => $this->studentPreviewPayload($source, true),
            'target' => $this->studentPreviewPayload($target, false),
            'proposedIdNumber' => $normalized,
        ];
    }

    private function assertFaultySource(Student $source): void
    {
        if ($source->id_number === null || ZimbabweanIdNumber::isValid($source->id_number)) {
            throw ValidationException::withMessages([
                'source' => [__('trans.maintenance_faulty_data_student_not_faulty')],
            ]);
        }
    }

    private function resolveTarget(int $targetStudentId, string $normalizedIdNumber): Student
    {
        $target = Student::query()->withTrashed()->find($targetStudentId);

        if ($target === null) {
            throw ValidationException::withMessages([
                'target' => [__('trans.maintenance_faulty_data_merge_target_not_found')],
            ]);
        }

        $owner = $this->enrollmentLookup->findStudentByNationalIdUnscoped($normalizedIdNumber);

        if ($owner !== null && $owner->id === $target->id) {
            return $target;
        }

        if ($this->targetOwnsNormalizedId($target, $normalizedIdNumber)) {
            return $target;
        }

        throw ValidationException::withMessages([
            'id_number' => [__('trans.maintenance_faulty_data_merge_id_mismatch')],
        ]);
    }

    private function targetOwnsNormalizedId(Student $target, string $normalizedIdNumber): bool
    {
        if ($target->id_number === null) {
            return false;
        }

        $targetNormalized = EnrollmentLookupService::normalizeNationalId((string) $target->id_number);
        $proposedCompact = strtoupper(str_replace('-', '', $normalizedIdNumber));
        $targetCompact = strtoupper(str_replace('-', '', $targetNormalized));

        return $targetNormalized === $normalizedIdNumber || $targetCompact === $proposedCompact;
    }

    /**
     * @return array{student: Student, counts: array<string, int>, programs: Collection<int, StudentProgram>}
     */
    private function studentPreviewPayload(Student $student, bool $isFaultySource): array
    {
        return [
            'student' => $student,
            'programs' => $student->programs,
            'counts' => [
                'programmesCount' => (int) ($student->programs_count ?? 0),
                'enrolmentsCount' => (int) ($student->enrolments_count ?? 0),
                'paidReceiptsCount' => $this->countPaidReceipts($student->user),
                'contactsCount' => (int) ($student->contacts_count ?? 0),
                'addressesCount' => (int) ($student->addresses_count ?? 0),
                'academicResultsCount' => $student->academicRecord()->count(),
                'hostelApplicationsCount' => (int) ($student->hostel_applications_count ?? 0),
                'isFaultySource' => $isFaultySource ? 1 : 0,
            ],
        ];
    }

    private function countPaidReceipts(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        return $user->ledgerTransactions()
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->count();
    }
}
