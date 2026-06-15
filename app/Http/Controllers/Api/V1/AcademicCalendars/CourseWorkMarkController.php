<?php

namespace App\Http\Controllers\Api\V1\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Services\AcademicCalendars\CourseWorkMarkService;
use App\Services\AcademicCalendars\CourseWorkTreeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CourseWorkMarkController extends JsonApiController
{
    public function __construct(
        private readonly CourseWorkMarkService $markService,
        private readonly CourseWorkTreeService $treeService,
    ) {}

    public function tree(Request $request): MetaResponse
    {
        abort_unless($request->user()?->can('viewAny', CourseWorkMark::class) ?? false, 403);

        $classId = (int) data_get($request->input('filter'), 'academicCalendarClass', 0);
        $classConfigId = (int) data_get($request->input('filter'), 'classConfig', 0);
        $studentEnrolmentId = (int) data_get($request->input('filter'), 'studentEnrolment', 0);

        if ($classId > 0 && $classConfigId > 0) {
            throw ValidationException::withMessages([
                'filter' => [__('academic_calendar.course_work_scope_filter_conflict')],
            ]);
        }

        if ($classConfigId > 0) {
            if ($studentEnrolmentId > 0) {
                abort(422, __('academic_calendar.course_work_student_filter_class_only'));
            }

            return MetaResponse::make($this->treeService->buildForClassConfig($classConfigId));
        }

        abort_if($classId < 1, 422, __('academic_calendar.course_work_scope_filter_required'));

        if ($studentEnrolmentId > 0) {
            return MetaResponse::make($this->treeService->buildForStudent($classId, $studentEnrolmentId));
        }

        return MetaResponse::make($this->treeService->buildForClass($classId));
    }

    public function auditLogs(Request $request): MetaResponse
    {
        abort_unless($request->user()?->can('viewAuditTrail', CourseWorkMark::class) ?? false, 403);

        $classId = (int) data_get($request->input('filter'), 'academicCalendarClass', 0);
        $classConfigId = (int) data_get($request->input('filter'), 'classConfig', 0);
        $markId = (int) data_get($request->input('filter'), 'courseWorkMark', 0);
        $studentEnrolmentId = (int) data_get($request->input('filter'), 'studentEnrolment', 0);

        $query = CourseWorkAuditLog::query()
            ->with(['user', 'assessmentType', 'courseSyllabusModule'])
            ->orderByDesc('created_at');

        if ($markId > 0) {
            $query->where('course_work_mark_id', $markId);
        } elseif ($studentEnrolmentId > 0) {
            $query->where('student_enrolment_id', $studentEnrolmentId);
        } elseif ($classConfigId > 0) {
            $enrolmentIds = $this->enrolmentIdsForClassConfig($classConfigId);
            $query->whereIn('student_enrolment_id', $enrolmentIds);
        } elseif ($classId > 0) {
            $enrolmentIds = AcademicCalendarStudentEnrolment::query()
                ->where('academic_calendar_class_id', $classId)
                ->whereNull('deleted_at')
                ->pluck('student_enrolment_id');

            $query->whereIn('student_enrolment_id', $enrolmentIds);
        } else {
            return MetaResponse::make(['logs' => []]);
        }

        $logs = $query->limit(200)->get()->map(fn (CourseWorkAuditLog $log): array => [
            'id' => $log->id,
            'courseWorkMarkId' => $log->course_work_mark_id,
            'event' => $log->event?->value,
            'userId' => $log->user_id,
            'userName' => $log->user ? trim(sprintf('%s %s', $log->user->first_name ?? '', $log->user->last_name ?? '')) : null,
            'studentEnrolmentId' => $log->student_enrolment_id,
            'courseSyllabusModuleId' => $log->course_syllabus_module_id,
            'moduleCode' => $log->courseSyllabusModule?->code,
            'assessmentTypeId' => $log->assessment_type_id,
            'assessmentTypeName' => $log->assessmentType?->name,
            'oldValues' => $log->old_values,
            'newValues' => $log->new_values,
            'createdAt' => $log->created_at?->toIso8601String(),
        ])->values()->all();

        return MetaResponse::make(['logs' => $logs]);
    }

    public function creating(ResourceRequest $request, ResourceQuery $query): DataResponse
    {
        $validated = $request->validated();
        [$classId, $classConfigId] = $this->scopeFromRequest($request);

        $mark = $this->markService->upsert([
            'studentEnrolmentId' => (int) $validated['studentEnrolmentId'],
            'courseSyllabusModuleId' => (int) $validated['courseSyllabusModuleId'],
            'assessmentTypeId' => (int) $validated['assessmentTypeId'],
            'mark' => $validated['mark'] ?? null,
            'remark' => $validated['remark'] ?? null,
        ], $classId, $classConfigId);

        return DataResponse::make($mark)
            ->withQueryParameters($query)
            ->didCreate();
    }

    public function updating(CourseWorkMark $mark, ResourceRequest $request, ResourceQuery $query): DataResponse
    {
        $validated = $request->validated();
        [$classId, $classConfigId] = $this->scopeFromRequest($request);

        $updated = $this->markService->upsert([
            'studentEnrolmentId' => (int) ($validated['studentEnrolmentId'] ?? $mark->student_enrolment_id),
            'courseSyllabusModuleId' => (int) ($validated['courseSyllabusModuleId'] ?? $mark->course_syllabus_module_id),
            'assessmentTypeId' => (int) ($validated['assessmentTypeId'] ?? $mark->assessment_type_id),
            'mark' => array_key_exists('mark', $validated) ? $validated['mark'] : $mark->mark,
            'remark' => array_key_exists('remark', $validated) ? $validated['remark'] : $mark->remark,
        ], $classId, $classConfigId);

        return DataResponse::make($updated)->withQueryParameters($query);
    }

    public function deleting(CourseWorkMark $mark, ResourceRequest $request): Response
    {
        [$classId, $classConfigId] = $this->scopeFromRequest($request);
        $this->markService->delete($mark, $classId, $classConfigId);

        return response()->noContent();
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function scopeFromRequest(ResourceRequest $request): array
    {
        $classId = (int) data_get($request->input('filter'), 'academicCalendarClass', 0);
        $classConfigId = (int) data_get($request->input('filter'), 'classConfig', 0);

        if ($classId > 0 && $classConfigId > 0) {
            throw ValidationException::withMessages([
                'filter' => [__('academic_calendar.course_work_scope_filter_conflict')],
            ]);
        }

        if ($classConfigId > 0) {
            return [null, $classConfigId];
        }

        if ($classId > 0) {
            return [$classId, null];
        }

        return [null, null];
    }

    /**
     * @return Collection<int, int>
     */
    private function enrolmentIdsForClassConfig(int $classConfigId)
    {
        $classIds = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfigId)
            ->whereNull('deleted_at')
            ->pluck('id');

        return AcademicCalendarStudentEnrolment::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->whereNull('deleted_at')
            ->pluck('student_enrolment_id');
    }
}
