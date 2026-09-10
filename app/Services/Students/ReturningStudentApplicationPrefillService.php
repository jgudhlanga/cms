<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\Student;
use App\Models\Students\StudentAcademicResult;

class ReturningStudentApplicationPrefillService
{
    /**
     * @param  array{
     *     canApplyToNextLevel?: bool,
     *     nextLevelId?: int|null,
     *     nextLevelName?: string|null,
     *     nextDepartmentLevelId?: int|null,
     *     institutionDepartmentId?: int|null,
     *     departmentCourseId?: int|null,
     * }|null  $nextLevelContext
     * @return array<string, mixed>
     */
    public function build(Student $student, ?array $nextLevelContext = null): array
    {
        $student->loadMissing([
            'user',
            'title',
            'gender',
            'maritalStatus',
            'race',
            'idType',
            'country',
            'contacts',
            'addresses',
            'nextOfKins.relationship',
            'nextOfKins.contacts',
            'nextOfKins.addresses',
            'oLevelResults.subject',
            'oLevelResults.grade',
        ]);

        $user = $student->user;
        $contact = $student->contacts()->where('contact_is_main', true)->first()
            ?? $student->contacts()->first();
        $address = $student->addresses()->where('address_is_main', true)->first()
            ?? $student->addresses()->first();
        $nextOfKin = $student->nextOfKins->first();
        $kinContact = $nextOfKin?->contacts->firstWhere('contact_is_main', true)
            ?? $nextOfKin?->contacts->first();
        $kinAddress = $nextOfKin?->addresses->firstWhere('address_is_main', true)
            ?? $nextOfKin?->addresses->first();

        return array_merge(
            [
                'email' => $contact?->email_address ?? $user?->email,
                'first_name' => $user?->first_name,
                'middle_name' => $user?->middle_name,
                'last_name' => $user?->last_name,
                'gender_id' => $student->gender_id,
                'title_id' => $student->title_id,
                'marital_status_id' => $student->marital_status_id,
                'race_id' => $student->race_id,
                'id_type_id' => $student->id_type_id,
                'id_number' => $student->id_number,
                'passport_number' => $student->passport_number,
                'country_id' => $student->country_id,
                'study_permit_number' => $student->study_permit_number,
                'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
                'disability_status' => $student->disability_status,
                'phone_number' => $contact?->phone_number ?? $user?->phone_number,
                'alt_phone_number' => $contact?->alt_phone_number,
                'address_1' => $address?->address_1,
                'address_2' => $address?->address_2,
                'address_3' => $address?->address_3,
                'address_4' => $address?->address_4,
                'next_of_kin_name' => $nextOfKin?->name,
                'relationship_id' => $nextOfKin?->relationship_id,
                'next_of_kin_phone_number' => $kinContact?->phone_number,
                'next_of_kin_address_1' => $kinAddress?->address_1,
                'next_of_kin_address_2' => $kinAddress?->address_2,
                'next_of_kin_address_3' => $kinAddress?->address_3,
                'next_of_kin_address_4' => $kinAddress?->address_4,
                'title' => $this->comboOption($student->title_id, $student->title?->name),
                'gender' => $this->comboOption($student->gender_id, $student->gender?->title),
                'maritalStatus' => $this->comboOption($student->marital_status_id, $student->maritalStatus?->title),
                'country' => $this->comboOption($student->country_id, $student->country?->name),
                'idType' => $this->comboOption($student->id_type_id, $student->idType?->name),
                'relationship' => $this->comboOption($nextOfKin?->relationship_id, $nextOfKin?->relationship?->name),
            ],
            $this->buildOLevelPrefill($student),
            $this->buildNextLevelProgrammePrefill($nextLevelContext),
        );
    }

    /**
     * @param  array{
     *     canApplyToNextLevel?: bool,
     *     nextLevelId?: int|null,
     *     nextLevelName?: string|null,
     *     nextDepartmentLevelId?: int|null,
     *     institutionDepartmentId?: int|null,
     *     departmentCourseId?: int|null,
     * }|null  $nextLevelContext
     * @return array<string, mixed>
     */
    private function buildNextLevelProgrammePrefill(?array $nextLevelContext): array
    {
        if (! is_array($nextLevelContext) || ($nextLevelContext['canApplyToNextLevel'] ?? false) !== true) {
            return [];
        }

        $departmentLevelId = $nextLevelContext['nextDepartmentLevelId'] ?? null;
        $institutionDepartmentId = $nextLevelContext['institutionDepartmentId'] ?? null;
        $previousCourseId = $nextLevelContext['departmentCourseId'] ?? null;

        if (! is_int($departmentLevelId) || $departmentLevelId < 1) {
            return [];
        }

        $departmentLevel = DepartmentLevel::query()
            ->with(['level', 'institutionDepartment.department'])
            ->find($departmentLevelId);

        if (! $departmentLevel instanceof DepartmentLevel) {
            return [];
        }

        $department = $departmentLevel->institutionDepartment;
        if (! $department instanceof InstitutionDepartment) {
            return [];
        }

        $prefill = [
            'department_id' => (int) $department->id,
            'level_id' => (int) $departmentLevel->id,
            'department' => $this->comboOption(
                (int) $department->id,
                (string) ($department->department?->name ?? $department->department_code ?? ''),
            ),
            'level' => $this->comboOption(
                (int) $departmentLevel->id,
                (string) ($departmentLevel->level?->name ?? ''),
            ),
            'required_level_completed' => true,
        ];

        if (is_int($previousCourseId) && $previousCourseId > 0) {
            $matchingCourse = DepartmentCourse::query()
                ->with('course')
                ->where('institution_department_id', $institutionDepartmentId ?? $department->id)
                ->where('id', $previousCourseId)
                ->whereNull('deleted_at')
                ->first();

            if ($matchingCourse instanceof DepartmentCourse) {
                $hasOffering = DepartmentLevelCourse::query()
                    ->where('department_level_id', $departmentLevel->id)
                    ->where('department_course_id', $matchingCourse->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($hasOffering) {
                    $prefill['course_id'] = (int) $matchingCourse->id;
                    $prefill['course'] = $this->comboOption(
                        (int) $matchingCourse->id,
                        (string) ($matchingCourse->course?->name ?? ''),
                    );
                }
            }
        }

        return $prefill;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOLevelPrefill(Student $student): array
    {
        $oLevelSubjectIds = [];
        $oLevelYears = [];
        $oLevelSittings = [];

        foreach ($student->oLevelResults as $result) {
            if (! $result instanceof StudentAcademicResult || $result->subject_id === null) {
                continue;
            }

            $subjectId = (string) $result->subject_id;
            $oLevelSubjectIds[$subjectId] = (string) $result->grade_id;
            $oLevelYears[$subjectId] = (string) $result->exam_year;
            $oLevelSittings[$subjectId] = [
                'value' => (string) $result->exam_sitting,
                'label' => (string) $result->exam_sitting,
            ];
        }

        if ($oLevelSubjectIds === []) {
            return [];
        }

        return [
            'o_level_subject_ids' => $oLevelSubjectIds,
            'o_level_years' => $oLevelYears,
            'o_level_sittings' => $oLevelSittings,
        ];
    }

    /**
     * @return array{value: int, label: string}|null
     */
    private function comboOption(?int $id, ?string $label): ?array
    {
        if ($id === null) {
            return null;
        }

        return [
            'value' => $id,
            'label' => $label ?? '',
        ];
    }
}
