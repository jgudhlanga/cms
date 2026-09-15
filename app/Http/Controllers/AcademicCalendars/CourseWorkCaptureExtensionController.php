<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicCalendars;

use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicCalendars\CourseWorkExtensionDecisionRequest;
use App\Http\Requests\AcademicCalendars\CourseWorkExtensionStoreRequest;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Notifications\Assessments\CourseWorkExtensionDecidedNotification;
use App\Notifications\Assessments\CourseWorkExtensionRequestedNotification;
use App\Services\AcademicCalendars\CourseWorkCaptureExtensionService;
use App\Support\AcademicCalendars\CourseWorkExtensionSummary;
use App\Support\Institution\DepartmentLeadershipResolver;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class CourseWorkCaptureExtensionController extends Controller
{
    public function __construct(
        private readonly CourseWorkCaptureExtensionService $extensions,
        private readonly DepartmentLeadershipResolver $leadership,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CourseWorkCaptureExtension::class);

        /** @var User $user */
        $user = $request->user();
        $isApprover = $user->can('viewDecisionQueue', CourseWorkCaptureExtension::class);
        $status = (string) $request->query('status', $isApprover ? CourseWorkExtensionStatusEnum::Pending->value : 'all');
        $statusFilter = CourseWorkExtensionStatusEnum::tryFrom($status);
        $departmentIds = UserAccessScope::for($user)->departmentIds();

        $extensions = CourseWorkCaptureExtension::query()
            ->with([
                'academicCalendarClass',
                'courseSyllabusModule',
                'assessmentType',
                'assessmentCalendar',
                'institutionDepartment.department',
                'requester',
                'decider',
            ])
            ->when(! $isApprover, fn ($query) => $query->where('requested_by', $user->id))
            ->when(
                $isApprover && $departmentIds !== null,
                fn ($query) => $query->where(fn ($scoped) => $scoped
                    ->whereIn('institution_department_id', $departmentIds === [] ? [-1] : $departmentIds)
                    ->orWhere('requested_by', $user->id)),
            )
            ->when($statusFilter !== null, fn ($query) => $query->where('status', $statusFilter->value))
            ->latest()
            ->limit(200)
            ->get();

        return Inertia::render('institution/courseWorkExtensions/Index', [
            'extensions' => $extensions->map(fn (CourseWorkCaptureExtension $extension): array => $this->row($extension, $user))->values()->all(),
            'status' => $statusFilter?->value ?? 'all',
            'statuses' => collect(CourseWorkExtensionStatusEnum::cases())
                ->map(fn (CourseWorkExtensionStatusEnum $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->values()
                ->all(),
            'isApprover' => $isApprover,
            'maxExtensionDays' => (int) config('coursework.extension_max_days', 14),
        ]);
    }

    public function store(
        CourseWorkExtensionStoreRequest $request,
        AcademicCalendarClass $academicCalendarClass,
        CourseSyllabusModule $courseSyllabusModule,
    ): RedirectResponse {
        $assessmentTypeId = $request->validated('assessment_type_id');

        $extension = $this->extensions->request(
            $request->user(),
            $academicCalendarClass,
            $courseSyllabusModule,
            $assessmentTypeId !== null ? (int) $assessmentTypeId : null,
            (string) $request->validated('requested_until'),
            (string) $request->validated('reason'),
        );

        $this->notifyApprovers($extension);

        return back()->with('success', __('academic_calendar.course_work_extension_requested'));
    }

    public function approve(CourseWorkExtensionDecisionRequest $request, CourseWorkCaptureExtension $courseWorkCaptureExtension): RedirectResponse
    {
        $extension = $this->extensions->approve(
            $request->user(),
            $courseWorkCaptureExtension,
            (string) $request->validated('approved_until'),
            $request->validated('note'),
        );

        $this->notifyRequester($extension);

        return back()->with('success', __('academic_calendar.course_work_extension_approved'));
    }

    public function reject(CourseWorkExtensionDecisionRequest $request, CourseWorkCaptureExtension $courseWorkCaptureExtension): RedirectResponse
    {
        $extension = $this->extensions->reject($request->user(), $courseWorkCaptureExtension, $request->validated('note'));
        $this->notifyRequester($extension);

        return back()->with('success', __('academic_calendar.course_work_extension_rejected'));
    }

    public function revoke(CourseWorkExtensionDecisionRequest $request, CourseWorkCaptureExtension $courseWorkCaptureExtension): RedirectResponse
    {
        $extension = $this->extensions->revoke($request->user(), $courseWorkCaptureExtension, $request->validated('note'));
        $this->notifyRequester($extension);

        return back()->with('success', __('academic_calendar.course_work_extension_revoked'));
    }

    public function cancel(Request $request, CourseWorkCaptureExtension $courseWorkCaptureExtension): RedirectResponse
    {
        $this->authorize('cancel', $courseWorkCaptureExtension);
        $this->extensions->cancel($request->user(), $courseWorkCaptureExtension);

        return back()->with('success', __('academic_calendar.course_work_extension_cancelled'));
    }

    private function notifyApprovers(CourseWorkCaptureExtension $extension): void
    {
        $extension->loadMissing('assessmentCalendar');
        $globalEnd = $extension->assessmentCalendar?->end_date;
        $beyondGlobalDeadline = $globalEnd !== null && $extension->requested_until?->gt($globalEnd);

        $recipients = $this->leadership->headsOfDepartment((int) $extension->institution_department_id);

        // Past the college deadline only VP Academics can approve; with no HOD on record the VP picks it up too.
        if ($beyondGlobalDeadline || $recipients->isEmpty()) {
            $recipients = $recipients->merge($this->leadership->vicePrincipalsAcademics((int) $extension->tenant_id));
        }

        $recipients = $recipients
            ->reject(fn (User $recipient): bool => (int) $recipient->id === (int) $extension->requested_by)
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CourseWorkExtensionRequestedNotification($extension, $beyondGlobalDeadline));
        }
    }

    private function notifyRequester(CourseWorkCaptureExtension $extension): void
    {
        $requester = $extension->requester()->first();

        if ($requester instanceof User) {
            $requester->notify(new CourseWorkExtensionDecidedNotification($extension));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function row(CourseWorkCaptureExtension $extension, User $user): array
    {
        $summary = CourseWorkExtensionSummary::for($extension);
        $isPending = $extension->status === CourseWorkExtensionStatusEnum::Pending;

        return [
            'id' => (int) $extension->id,
            'className' => $summary['class'],
            'moduleName' => $summary['module'],
            'assessmentName' => $summary['assessment'],
            'departmentName' => (string) ($extension->institutionDepartment?->department?->name ?? ''),
            'requesterName' => $summary['requester'],
            'reason' => (string) $extension->reason,
            'requestedUntil' => $extension->requested_until?->toDateString(),
            'approvedUntil' => $extension->approved_until?->toDateString(),
            'globalEndDate' => $extension->assessmentCalendar?->end_date?->toDateString(),
            'status' => $extension->status->value,
            'statusLabel' => $extension->status->label(),
            'decisionNote' => $extension->decision_note,
            'deciderName' => (string) ($extension->decider?->full_name ?? ''),
            'createdAt' => $extension->created_at?->toIso8601String(),
            'decidedAt' => $extension->decided_at?->toIso8601String(),
            'can' => [
                'approve' => $isPending && $user->can('approve', $extension),
                'reject' => $isPending && $user->can('reject', $extension),
                'revoke' => $extension->status === CourseWorkExtensionStatusEnum::Approved && $user->can('revoke', $extension),
                'cancel' => $user->can('cancel', $extension),
                'approveBeyondGlobal' => $user->can('approveBeyondGlobal:course-work-extensions'),
            ],
        ];
    }
}
