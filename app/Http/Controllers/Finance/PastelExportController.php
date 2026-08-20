<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Helpers\DropdownHelper;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BulkUnlinkPastelLinkedStudentsRequest;
use App\Http\Requests\Finance\ExportPastelRequest;
use App\Http\Resources\Finance\PastelLinkedStudentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Shared\WorkflowStepResource;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Institution\IntakePeriod;
use App\Queries\Finance\PastelExportQuery;
use App\Services\Finance\PastelExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PastelExportController extends Controller
{
    public function index(Request $request, PastelExportQuery $query): Response
    {
        $this->authorize('exportToPastel');

        $intakePeriodId = $request->integer('intake_period_id') ?: null;
        $workflowStepIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('workflow_step_ids', []),
        )));

        $studentNumberStartsWith = $this->resolveStudentNumberStartsWith($request);

        $exportCount = null;

        if ($intakePeriodId !== null) {
            $exportCount = $query->count(
                $intakePeriodId,
                $workflowStepIds,
                $this->queryStudentNumberStartsWith($studentNumberStartsWith),
            );
        }

        $linkedStats = $query->linkedStats();
        $search = $request->string('search')->toString();

        $linkedStudents = PastelLinkedStudentResource::collection(
            $query
                ->linkedStudentsQuery($search !== '' ? $search : null)
                ->paginate((new PastelLinkedStudent)->getPerPage())
                ->withQueryString(),
        );

        return Inertia::render('finance/PastelExport', [
            'intakePeriods' => IntakePeriodResource::collection(DropdownHelper::getIntakePeriods()),
            'workflowSteps' => WorkflowStepResource::collection(WorkflowHelper::getAllSteps()),
            'filters' => [
                'intake_period_id' => $intakePeriodId,
                'workflow_step_ids' => $workflowStepIds,
                'student_number_starts_with' => $studentNumberStartsWith,
                'search' => $search !== '' ? $search : null,
            ],
            'exportCount' => $exportCount,
            'linkedStats' => [
                'total' => $linkedStats['total'],
                'linkedToday' => $linkedStats['linked_today'],
                'readyToExport' => $exportCount,
            ],
            'linkedStudents' => $linkedStudents,
        ]);
    }

    public function download(ExportPastelRequest $request, PastelExportService $exportService): BinaryFileResponse
    {
        $relativePath = $exportService->export($request);
        $absolutePath = Storage::disk('local')->path($relativePath);
        $fileName = 'pastel-export-'.now()->format('Y-m-d_His').'.csv';

        return response()->download($absolutePath, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    public function destroy(PastelLinkedStudent $pastelLinkedStudent): RedirectResponse
    {
        $this->authorize('exportToPastel');

        $pastelLinkedStudent->delete();

        return back();
    }

    public function bulkDestroy(BulkUnlinkPastelLinkedStudentsRequest $request): RedirectResponse
    {
        PastelLinkedStudent::query()
            ->whereIn('id', $request->ids())
            ->delete();

        return back();
    }

    private function resolveStudentNumberStartsWith(Request $request): string
    {
        if ($request->has('student_number_starts_with')) {
            return trim((string) $request->input('student_number_starts_with'));
        }

        $intakePeriodId = $request->integer('intake_period_id') ?: null;

        if ($intakePeriodId !== null) {
            $calendarYear = IntakePeriod::query()->whereKey($intakePeriodId)->value('calendar_year');
            $derivedPrefix = $this->deriveStudentNumberPrefixFromCalendarYear(
                is_string($calendarYear) ? $calendarYear : null,
            );

            if ($derivedPrefix !== null) {
                return $derivedPrefix;
            }
        }

        return ExportPastelRequest::DEFAULT_STUDENT_NUMBER_STARTS_WITH;
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

    private function queryStudentNumberStartsWith(string $studentNumberStartsWith): ?string
    {
        $value = trim($studentNumberStartsWith);

        return $value !== '' ? $value : null;
    }
}
