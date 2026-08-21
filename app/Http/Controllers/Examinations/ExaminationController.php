<?php

declare(strict_types=1);

namespace App\Http\Controllers\Examinations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Examinations\ExaminationIndexRequest;
use App\Http\Resources\Examinations\ExaminationResultResource;
use App\Models\Examinations\ExaminationResult;
use App\Queries\Examinations\ExaminationResultQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationController extends Controller
{
    public function index(ExaminationIndexRequest $request, ExaminationResultQuery $query): Response
    {
        $filters = $query->resolveIndexFilters($request->filters());

        $paginator = $query
            ->filtered($filters)
            ->orderBy('candidate_number')
            ->orderBy('subject_code')
            ->orderBy('session')
            ->paginate()
            ->withQueryString();

        return Inertia::render('examinations/Index', [
            'results' => ExaminationResultResource::collection($paginator),
            'filters' => [
                'session' => $filters['session'],
                'discipline' => $filters['discipline'],
                'subject_code' => $filters['subject_code'],
                'surname' => $filters['surname'],
                'first_names' => $filters['first_names'],
                'candidate_number' => $filters['candidate_number'],
            ],
            'filterOptions' => [
                'sessions' => $query->sessionOptions(),
                'disciplines' => $query->disciplineOptions($filters['session']),
                'subjects' => $query->subjectOptions($filters['session'], $filters['discipline']),
            ],
            'canImport' => $request->user()?->can('import', ExaminationResult::class) ?? false,
        ]);
    }

    public function show(Request $request, string $candidateNumber): Response
    {
        $this->authorize('viewAny', ExaminationResult::class);

        $results = ExaminationResult::query()
            ->where('candidate_number', $candidateNumber)
            ->orderBy('session_date')
            ->orderBy('subject_code')
            ->get();

        abort_if($results->isEmpty(), 404);

        $first = $results->first();

        return Inertia::render('examinations/Show', [
            'candidate' => [
                'candidateNumber' => $candidateNumber,
                'surname' => $first?->surname,
                'firstNames' => $first?->first_names,
                'discipline' => $first?->discipline,
            ],
            'results' => ExaminationResultResource::collection($results),
            'canImport' => $request->user()?->can('import', ExaminationResult::class) ?? false,
        ]);
    }
}
