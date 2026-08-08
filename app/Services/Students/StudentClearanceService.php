<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\StudentClearanceSection;
use App\Models\Students\Student;
use App\Models\Students\StudentClearance;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentClearanceService
{
    /**
     * @param  array{
     *     cleared: bool,
     *     notes?: string|null
     * }  $payload
     */
    public function updateSection(
        Student $student,
        int $calendarYear,
        int $semesterId,
        StudentClearanceSection $section,
        array $payload,
        User $actor,
    ): StudentClearance {
        return $this->updateSections(
            $student,
            $calendarYear,
            $semesterId,
            [
                [
                    'section' => $section,
                    'cleared' => (bool) ($payload['cleared'] ?? false),
                    'notes' => $payload['notes'] ?? null,
                ],
            ],
            $actor,
        );
    }

    /**
     * @param  list<array{
     *     section: StudentClearanceSection,
     *     cleared: bool,
     *     notes?: string|null
     * }>  $sections
     */
    public function updateSections(
        Student $student,
        int $calendarYear,
        int $semesterId,
        array $sections,
        User $actor,
    ): StudentClearance {
        if ($sections === []) {
            throw ValidationException::withMessages([
                'sections' => [__('validation.required', ['attribute' => 'sections'])],
            ]);
        }

        return DB::transaction(function () use ($student, $calendarYear, $semesterId, $sections, $actor): StudentClearance {
            $clearance = StudentClearance::query()->firstOrCreate(
                [
                    'student_id' => $student->id,
                    'calendar_year' => $calendarYear,
                    'semester_id' => $semesterId,
                ],
                [
                    'tenant_id' => $student->tenant_id,
                ]
            );

            foreach ($sections as $index => $row) {
                /** @var StudentClearanceSection $section */
                $section = $row['section'];
                $cleared = (bool) ($row['cleared'] ?? false);
                $notes = isset($row['notes']) ? trim((string) $row['notes']) : null;

                if (! $cleared && ($notes === null || $notes === '')) {
                    throw ValidationException::withMessages([
                        "sections.{$index}.notes" => [__('trans.clearance_unclear_reason_required')],
                    ]);
                }

                $clearance->setAttribute($section->clearedColumn(), $cleared);
                $clearance->setAttribute($section->notesColumn(), $notes !== '' ? $notes : null);
                $clearance->setAttribute($section->clearedByColumn(), $cleared ? $actor->id : null);
                $clearance->setAttribute($section->clearedAtColumn(), $cleared ? now() : null);
            }

            $clearance->save();

            return $clearance->fresh([
                'accountsClearedBy',
                'libraryClearedBy',
                'securityClearedBy',
                'hostelClearedBy',
                'departmentClearedBy',
                'semester',
                'student',
            ]);
        });
    }

    public function findOrNew(Student $student, int $calendarYear, int $semesterId): StudentClearance
    {
        return StudentClearance::query()->firstOrNew(
            [
                'student_id' => $student->id,
                'calendar_year' => $calendarYear,
                'semester_id' => $semesterId,
            ],
            [
                'tenant_id' => $student->tenant_id,
            ]
        );
    }
}
