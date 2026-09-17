<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ExportForBillingRequest;
use App\Http\Requests\Finance\MarkBillingRecordsRequest;
use App\Http\Resources\Finance\StudentBillingRecordResource;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Finance\StudentBillingRecord;
use App\Queries\Finance\BillingExportQuery;
use App\Services\Finance\BillingExportService;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use App\Support\Finance\BillingExportFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingExportController extends Controller
{
    public function index(
        Request $request,
        BillingExportQuery $query,
        CurrentStudyPeriodResolver $periods,
    ): Response {
        $this->authorize('exportForBilling');

        $defaultPeriodIds = $periods->currentPeriodIds();
        $filters = BillingExportFilters::fromRequest($request, $defaultPeriodIds);
        $studentNumberStartsWith = $this->resolveStudentNumberStartsWith($request, $filters);

        if ($studentNumberStartsWith !== $filters->studentNumberStartsWith) {
            $filters = new BillingExportFilters(
                academicCalendarIds: $filters->academicCalendarIds,
                studentNumberStartsWith: $studentNumberStartsWith,
                programmeSemesterIds: $filters->programmeSemesterIds,
                sources: $filters->sources,
                syncStatuses: $filters->syncStatuses,
                confirmedFrom: $filters->confirmedFrom,
                confirmedTo: $filters->confirmedTo,
                institutionDepartmentId: $filters->institutionDepartmentId,
                departmentLevelId: $filters->departmentLevelId,
                departmentCourseId: $filters->departmentCourseId,
                modeOfStudyId: $filters->modeOfStudyId,
                pastelLinked: $filters->pastelLinked,
            );
        }

        $exportCount = $filters->hasPeriods() ? $query->count($filters) : null;
        $stats = $query->stats($filters);
        $search = $request->string('search')->toString();
        $displayStudentNumberStartsWith = $request->has('student_number_starts_with')
            ? trim((string) $request->input('student_number_starts_with'))
            : ($filters->studentNumberStartsWith ?? ExportForBillingRequest::DEFAULT_STUDENT_NUMBER_STARTS_WITH);

        $billingRecords = StudentBillingRecordResource::collection(
            $query
                ->recordsQuery(
                    $search !== '' ? $search : null,
                    $filters->academicCalendarIds,
                )
                ->paginate((new StudentBillingRecord)->getPerPage())
                ->withQueryString(),
        );

        return Inertia::render('finance/BillingExport', [
            'periodOptions' => $query->periodOptions(),
            'filterOptions' => $query->filterOptions(),
            'filters' => array_merge($filters->toArray(), [
                'student_number_starts_with' => $displayStudentNumberStartsWith,
                'search' => $search !== '' ? $search : null,
            ]),
            'exportCount' => $exportCount,
            'billingStats' => [
                'total' => $stats['total'],
                'billedToday' => $stats['billed_today'],
                'readyToBill' => $stats['ready_to_bill'],
            ],
            'billingRecords' => $billingRecords,
        ]);
    }

    public function download(ExportForBillingRequest $request, BillingExportService $exportService): BinaryFileResponse
    {
        $relativePath = $exportService->export($request);
        $absolutePath = Storage::disk('local')->path($relativePath);
        $fileName = 'billing-export-'.now()->format('Y-m-d_His').'.csv';

        return response()->download($absolutePath, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    public function markBilled(MarkBillingRecordsRequest $request, BillingExportService $exportService): RedirectResponse
    {
        $exportService->markBilled($request->ids(), (int) $request->user()?->id);

        return back();
    }

    public function markFailed(MarkBillingRecordsRequest $request, BillingExportService $exportService): RedirectResponse
    {
        $exportService->markFailed($request->ids());

        return back();
    }

    public function destroy(StudentBillingRecord $studentBillingRecord, BillingExportService $exportService): RedirectResponse
    {
        $this->authorize('exportForBilling');

        $exportService->unbill([(int) $studentBillingRecord->id]);

        return back();
    }

    public function bulkDestroy(MarkBillingRecordsRequest $request, BillingExportService $exportService): RedirectResponse
    {
        $exportService->unbill($request->ids());

        return back();
    }

    private function resolveStudentNumberStartsWith(Request $request, BillingExportFilters $filters): ?string
    {
        if ($request->has('student_number_starts_with')) {
            $value = trim((string) $request->input('student_number_starts_with'));

            return $value !== '' ? $value : null;
        }

        $derivedPrefix = $this->deriveStudentNumberPrefixFromPeriodIds($filters->academicCalendarIds);

        return $derivedPrefix ?? ExportForBillingRequest::DEFAULT_STUDENT_NUMBER_STARTS_WITH;
    }

    /**
     * @param  list<int>  $periodIds
     */
    private function deriveStudentNumberPrefixFromPeriodIds(array $periodIds): ?string
    {
        if ($periodIds === []) {
            return null;
        }

        $calendarYear = AcademicCalendar::query()->whereKey($periodIds[0])->value('calendar_year');

        return $this->deriveStudentNumberPrefixFromCalendarYear(
            is_string($calendarYear) ? $calendarYear : null,
        );
    }

    private function deriveStudentNumberPrefixFromCalendarYear(?string $calendarYear): ?string
    {
        if ($calendarYear === null || trim($calendarYear) === '') {
            return null;
        }

        if (preg_match('/(\d{4})/', $calendarYear, $matches) === 1) {
            return substr($matches[1], -2);
        }

        return null;
    }
}
