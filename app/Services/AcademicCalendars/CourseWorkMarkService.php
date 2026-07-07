<?php

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkAuditEventEnum;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Support\AcademicCalendars\CourseWorkMarkValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseWorkMarkService
{
    public function __construct(
        private readonly CourseWorkAuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{studentEnrolmentId: int, courseSyllabusModuleId: int, assessmentTypeId?: int|null, mark?: int|null, remark?: string|null}  $data
     */
    public function upsert(array $data, ?int $academicCalendarClassId = null, ?int $classConfigId = null): CourseWorkMark
    {
        $this->assertEnrolmentScope(
            (int) $data['studentEnrolmentId'],
            $academicCalendarClassId,
            $classConfigId,
        );

        $module = CourseSyllabusModule::query()->findOrFail((int) $data['courseSyllabusModuleId']);
        $assessmentTypeId = array_key_exists('assessmentTypeId', $data) && $data['assessmentTypeId'] !== null
            ? (int) $data['assessmentTypeId']
            : null;

        $this->assertMarkCaptureMode($module, $assessmentTypeId);

        if (array_key_exists('mark', $data) && $data['mark'] !== null) {
            $parsedMark = CourseWorkMarkValue::tryParse($data['mark']);

            if ($parsedMark === null) {
                throw ValidationException::withMessages([
                    'mark' => [__('academic_calendar.course_work_mark_invalid')],
                ]);
            }

            $data['mark'] = $parsedMark;
        }

        return DB::transaction(function () use ($data, $assessmentTypeId): CourseWorkMark {
            $query = CourseWorkMark::query()
                ->withTrashed()
                ->where('student_enrolment_id', $data['studentEnrolmentId'])
                ->where('course_syllabus_module_id', $data['courseSyllabusModuleId']);

            if ($assessmentTypeId === null) {
                $query->whereNull('assessment_type_id');
            } else {
                $query->where('assessment_type_id', $assessmentTypeId);
            }

            $mark = $query->lockForUpdate()->first();

            $user = Auth::user();
            $userId = $user instanceof User ? $user->id : null;

            $attributes = [
                'mark' => array_key_exists('mark', $data) ? $data['mark'] : null,
                'remark' => array_key_exists('remark', $data) ? $data['remark'] : null,
            ];

            if ($mark?->trashed()) {
                $mark->restore();

                $this->auditLogger->log(
                    $mark,
                    CourseWorkAuditEventEnum::Restored,
                    null,
                    $mark->only(['mark', 'remark']),
                );
            }

            if ($mark === null) {
                $created = CourseWorkMark::query()->create([
                    'student_enrolment_id' => $data['studentEnrolmentId'],
                    'course_syllabus_module_id' => $data['courseSyllabusModuleId'],
                    'assessment_type_id' => $assessmentTypeId,
                    'mark' => $attributes['mark'],
                    'remark' => $attributes['remark'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $this->auditLogger->log(
                    $created,
                    CourseWorkAuditEventEnum::Created,
                    null,
                    $created->only(['mark', 'remark']),
                );

                return $created;
            }

            $oldValues = $mark->only(['mark', 'remark']);

            $mark->fill($attributes);
            $mark->updated_by = $userId;
            $mark->save();

            $this->auditLogger->log(
                $mark,
                CourseWorkAuditEventEnum::Updated,
                $oldValues,
                $mark->only(['mark', 'remark']),
            );

            return $mark;
        });
    }

    private function assertMarkCaptureMode(CourseSyllabusModule $module, ?int $assessmentTypeId): void
    {
        if ($module->capture_mark_only) {
            if ($assessmentTypeId !== null) {
                throw ValidationException::withMessages([
                    'assessmentTypeId' => [__('academic_calendar.course_work_mark_only_no_assessment')],
                ]);
            }

            return;
        }

        if ($assessmentTypeId === null) {
            throw ValidationException::withMessages([
                'assessmentTypeId' => [__('academic_calendar.course_work_assessment_required')],
            ]);
        }
    }

    public function delete(CourseWorkMark $mark, ?int $academicCalendarClassId = null, ?int $classConfigId = null): void
    {
        $this->assertEnrolmentScope(
            (int) $mark->student_enrolment_id,
            $academicCalendarClassId,
            $classConfigId,
        );

        $oldValues = $mark->only(['mark', 'remark']);
        $mark->delete();

        $this->auditLogger->log(
            $mark,
            CourseWorkAuditEventEnum::Deleted,
            $oldValues,
            null,
        );
    }

    public function restore(CourseWorkMark $mark, ?int $academicCalendarClassId = null, ?int $classConfigId = null): CourseWorkMark
    {
        $this->assertEnrolmentScope(
            (int) $mark->student_enrolment_id,
            $academicCalendarClassId,
            $classConfigId,
        );

        $mark->restore();

        $this->auditLogger->log(
            $mark,
            CourseWorkAuditEventEnum::Restored,
            null,
            $mark->only(['mark', 'remark']),
        );

        return $mark;
    }

    public function assertEnrolmentScope(
        int $studentEnrolmentId,
        ?int $academicCalendarClassId,
        ?int $classConfigId,
    ): void {
        if ($academicCalendarClassId !== null) {
            $this->assertEnrolmentBelongsToClass($studentEnrolmentId, $academicCalendarClassId);

            return;
        }

        if ($classConfigId !== null) {
            $this->assertEnrolmentBelongsToClassConfig($studentEnrolmentId, $classConfigId);

            return;
        }
    }

    public function assertEnrolmentBelongsToClass(int $studentEnrolmentId, ?int $academicCalendarClassId): void
    {
        if ($academicCalendarClassId === null) {
            return;
        }

        $exists = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $academicCalendarClassId)
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'studentEnrolmentId' => [__('academic_calendar.course_work_enrolment_not_in_class')],
            ]);
        }
    }

    public function assertEnrolmentBelongsToClassConfig(int $studentEnrolmentId, ?int $classConfigId): void
    {
        if ($classConfigId === null) {
            return;
        }

        $exists = AcademicCalendarStudentEnrolment::query()
            ->join('academic_calendar_classes', 'academic_calendar_classes.id', '=', 'academic_calendar_student_enrolments.academic_calendar_class_id')
            ->where('academic_calendar_classes.class_config_id', $classConfigId)
            ->where('academic_calendar_student_enrolments.student_enrolment_id', $studentEnrolmentId)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->whereNull('academic_calendar_classes.deleted_at')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'studentEnrolmentId' => [__('academic_calendar.course_work_enrolment_not_in_class_config')],
            ]);
        }
    }

    public function assertClassConfigExists(int $classConfigId): ClassConfig
    {
        $classConfig = ClassConfig::query()->find($classConfigId);

        if ($classConfig === null) {
            throw ValidationException::withMessages([
                'classConfigId' => [__('academic_calendar.course_work_class_config_not_found')],
            ]);
        }

        return $classConfig;
    }

    public function assertClassExists(int $academicCalendarClassId): AcademicCalendarClass
    {
        $class = AcademicCalendarClass::query()->find($academicCalendarClassId);

        if ($class === null) {
            throw ValidationException::withMessages([
                'academicCalendarClassId' => [__('academic_calendar.course_work_class_not_found')],
            ]);
        }

        return $class;
    }
}
