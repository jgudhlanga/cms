<?php

namespace App\Services\AcademicCalendars;

use App\DTO\Assessments\EffectiveAssessmentWindow;
use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Enums\Assessments\AssessmentWindowStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Services\Assessments\EffectiveAssessmentWindowResolver;
use App\Services\Lecturer\LecturerCourseWorkAccess;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Time-boxed reopening of coursework capture after a window closes. The HOD may approve up to the
 * global (college) end date; anything later needs approveBeyondGlobal (VP Academics). Every step is
 * written to the activity log.
 */
class CourseWorkCaptureExtensionService
{
    public function __construct(
        private readonly LecturerCourseWorkAccess $access,
        private readonly EffectiveAssessmentWindowResolver $windowResolver,
    ) {}

    public function request(
        User $requester,
        AcademicCalendarClass $class,
        CourseSyllabusModule $module,
        ?int $assessmentTypeId,
        string $requestedUntil,
        string $reason,
    ): CourseWorkCaptureExtension {
        if (! $this->access->canCaptureClassModule($requester, (int) $class->id, (int) $module->id)) {
            throw new AuthorizationException;
        }

        if ($module->capture_mark_only) {
            $assessmentTypeId = null;
        } elseif ($assessmentTypeId === null) {
            throw ValidationException::withMessages([
                'assessment_type_id' => [__('academic_calendar.course_work_assessment_required')],
            ]);
        }

        $classConfig = $this->classConfigFor($class);
        $window = $this->windowFor($class, $classConfig, $module, $assessmentTypeId);

        if ($window->status !== AssessmentWindowStatusEnum::Closed) {
            throw ValidationException::withMessages([
                'assessment_type_id' => [__('academic_calendar.course_work_extension_window_not_closed', [
                    'assessment' => $window->assessmentTypeName,
                ])],
            ]);
        }

        $today = now()->startOfDay();
        $until = Carbon::parse($requestedUntil)->startOfDay();
        $maxDays = (int) config('coursework.extension_max_days', 14);

        if ($until->lte($today) || $until->gt($today->copy()->addDays($maxDays))) {
            throw ValidationException::withMessages([
                'requested_until' => [__('academic_calendar.course_work_extension_until_range', ['days' => $maxDays])],
            ]);
        }

        $pendingExists = CourseWorkCaptureExtension::query()
            ->where('academic_calendar_class_id', $class->id)
            ->where('course_syllabus_module_id', $module->id)
            ->when(
                $assessmentTypeId === null,
                fn ($query) => $query->whereNull('assessment_type_id'),
                fn ($query) => $query->where('assessment_type_id', $assessmentTypeId),
            )
            ->where('status', CourseWorkExtensionStatusEnum::Pending->value)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'assessment_type_id' => [__('academic_calendar.course_work_extension_pending_exists')],
            ]);
        }

        return DB::transaction(function () use ($requester, $class, $module, $assessmentTypeId, $classConfig, $window, $until, $reason): CourseWorkCaptureExtension {
            $extension = CourseWorkCaptureExtension::query()->create([
                'tenant_id' => $class->tenant_id,
                'academic_calendar_class_id' => $class->id,
                'course_syllabus_module_id' => $module->id,
                'assessment_type_id' => $assessmentTypeId,
                'institution_department_id' => $classConfig->institution_department_id,
                'assessment_calendar_id' => $window->globalCalendarId,
                'requested_by' => $requester->id,
                'reason' => trim($reason),
                'requested_until' => $until->toDateString(),
                'status' => CourseWorkExtensionStatusEnum::Pending->value,
            ]);

            activity()
                ->performedOn($extension)
                ->causedBy($requester)
                ->withProperties(['requested_until' => $until->toDateString()])
                ->log('course_work_extension_requested');

            return $extension;
        });
    }

    public function approve(User $approver, CourseWorkCaptureExtension $extension, string $approvedUntil, ?string $note = null): CourseWorkCaptureExtension
    {
        $this->assertStatus($extension, CourseWorkExtensionStatusEnum::Pending);

        if ((int) $extension->requested_by === (int) $approver->id) {
            throw new AuthorizationException;
        }

        $today = now()->startOfDay();
        $until = Carbon::parse($approvedUntil)->startOfDay();

        if ($until->lte($today)) {
            throw ValidationException::withMessages([
                'approved_until' => [__('academic_calendar.course_work_extension_until_future')],
            ]);
        }

        $class = $extension->academicCalendarClass()->firstOrFail();
        $module = $extension->courseSyllabusModule()->firstOrFail();
        $window = $this->windowFor($class, $this->classConfigFor($class), $module, $extension->assessment_type_id !== null ? (int) $extension->assessment_type_id : null);
        $globalEnd = $window->globalEndDate !== null ? Carbon::parse($window->globalEndDate)->startOfDay() : null;

        if ($globalEnd !== null && $until->gt($globalEnd) && ! $approver->can('approveBeyondGlobal:course-work-extensions')) {
            throw ValidationException::withMessages([
                'approved_until' => [__('academic_calendar.course_work_extension_beyond_global_requires_vp', [
                    'date' => $globalEnd->format('d M Y'),
                ])],
            ]);
        }

        $academicCalendarId = $this->windowResolver->academicCalendarIdForClass((int) $class->id);
        $closingDate = $academicCalendarId !== null
            ? AcademicCalendar::query()->whereKey($academicCalendarId)->value('closing_date')
            : null;

        if ($closingDate !== null && $until->gt(Carbon::parse($closingDate)->startOfDay())) {
            throw ValidationException::withMessages([
                'approved_until' => [__('academic_calendar.course_work_extension_beyond_academic_calendar', [
                    'date' => Carbon::parse($closingDate)->format('d M Y'),
                ])],
            ]);
        }

        return $this->decide($extension, $approver, CourseWorkExtensionStatusEnum::Approved, $note, [
            'approved_until' => $until->toDateString(),
        ], 'course_work_extension_approved');
    }

    public function reject(User $approver, CourseWorkCaptureExtension $extension, ?string $note = null): CourseWorkCaptureExtension
    {
        $this->assertStatus($extension, CourseWorkExtensionStatusEnum::Pending);

        if ((int) $extension->requested_by === (int) $approver->id) {
            throw new AuthorizationException;
        }

        return $this->decide($extension, $approver, CourseWorkExtensionStatusEnum::Rejected, $note, [], 'course_work_extension_rejected');
    }

    public function revoke(User $revoker, CourseWorkCaptureExtension $extension, ?string $note = null): CourseWorkCaptureExtension
    {
        $this->assertStatus($extension, CourseWorkExtensionStatusEnum::Approved);

        return DB::transaction(function () use ($revoker, $extension, $note): CourseWorkCaptureExtension {
            $extension->update([
                'status' => CourseWorkExtensionStatusEnum::Revoked->value,
                'revoked_by' => $revoker->id,
                'revoked_at' => now(),
                'decision_note' => $note ?? $extension->decision_note,
            ]);

            activity()->performedOn($extension)->causedBy($revoker)->log('course_work_extension_revoked');

            return $extension->refresh();
        });
    }

    public function cancel(User $requester, CourseWorkCaptureExtension $extension): CourseWorkCaptureExtension
    {
        $this->assertStatus($extension, CourseWorkExtensionStatusEnum::Pending);

        if ((int) $extension->requested_by !== (int) $requester->id) {
            throw new AuthorizationException;
        }

        $extension->update(['status' => CourseWorkExtensionStatusEnum::Cancelled->value]);
        activity()->performedOn($extension)->causedBy($requester)->log('course_work_extension_cancelled');

        return $extension->refresh();
    }

    public function windowFor(
        AcademicCalendarClass $class,
        ClassConfig $classConfig,
        CourseSyllabusModule $module,
        ?int $assessmentTypeId,
    ): EffectiveAssessmentWindow {
        $this->windowResolver->flush();
        $academicCalendarId = (int) $this->windowResolver->academicCalendarIdForClass((int) $class->id);
        $departmentId = (int) $classConfig->institution_department_id;
        $modeOfStudyId = (int) $classConfig->mode_of_study_id;

        // Resolve without the class so an existing extension does not mask the closed base window.
        return $module->capture_mark_only || $assessmentTypeId === null
            ? $this->windowResolver->moduleMarkWindowFor($academicCalendarId, $departmentId, $modeOfStudyId)
            : $this->windowResolver->windowFor($academicCalendarId, $departmentId, $modeOfStudyId, $assessmentTypeId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function decide(
        CourseWorkCaptureExtension $extension,
        User $decider,
        CourseWorkExtensionStatusEnum $status,
        ?string $note,
        array $attributes,
        string $activity,
    ): CourseWorkCaptureExtension {
        return DB::transaction(function () use ($extension, $decider, $status, $note, $attributes, $activity): CourseWorkCaptureExtension {
            $extension->update([
                ...$attributes,
                'status' => $status->value,
                'decided_by' => $decider->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            activity()
                ->performedOn($extension)
                ->causedBy($decider)
                ->withProperties(['note' => $note, ...$attributes])
                ->log($activity);

            return $extension->refresh();
        });
    }

    private function assertStatus(CourseWorkCaptureExtension $extension, CourseWorkExtensionStatusEnum $expected): void
    {
        if ($extension->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => [__('academic_calendar.course_work_extension_status_invalid', [
                    'status' => $extension->status?->label() ?? '',
                ])],
            ]);
        }
    }

    private function classConfigFor(AcademicCalendarClass $class): ClassConfig
    {
        $class->loadMissing('classConfig');
        $classConfig = $class->classConfig;

        if (! $classConfig instanceof ClassConfig) {
            throw ValidationException::withMessages([
                'academic_calendar_class_id' => [__('academic_calendar.course_work_class_config_not_found')],
            ]);
        }

        return $classConfig;
    }
}
