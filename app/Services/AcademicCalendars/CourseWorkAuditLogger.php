<?php

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkAuditEventEnum;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;

class CourseWorkAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        CourseWorkMark $mark,
        CourseWorkAuditEventEnum $event,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): CourseWorkAuditLog {
        $user = Auth::user();

        return CourseWorkAuditLog::query()->create([
            'tenant_id' => $mark->tenant_id,
            'course_work_mark_id' => $mark->id,
            'event' => $event,
            'user_id' => $user instanceof User ? $user->id : null,
            'student_enrolment_id' => $mark->student_enrolment_id,
            'course_syllabus_module_id' => $mark->course_syllabus_module_id,
            'assessment_type_id' => $mark->assessment_type_id,
            'old_values' => $this->snapshot($oldValues),
            'new_values' => $this->snapshot($newValues),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array{mark: int|null, remark: string|null}|null
     */
    private function snapshot(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return [
            'mark' => array_key_exists('mark', $values) ? ($values['mark'] !== null ? (int) $values['mark'] : null) : null,
            'remark' => array_key_exists('remark', $values) ? ($values['remark'] !== null ? (string) $values['remark'] : null) : null,
        ];
    }
}
