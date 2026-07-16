<?php

namespace App\Http\Controllers\Examinations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Examinations\ExaminationResultResource;
use App\Models\Examinations\ExaminationResult;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ExaminationResult::class);

        $search = trim((string) $request->query('search', ''));

        $paginator = ExaminationResult::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('candidate_number', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('discipline', 'like', "%{$search}%")
                        ->orWhere('course_code', 'like', "%{$search}%")
                        ->orWhere('subject_code', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('grade', 'like', "%{$search}%")
                        ->orWhere('session', 'like', "%{$search}%")
                        ->orWhere('course_comment', 'like', "%{$search}%");
                });
            })
            ->orderBy('candidate_number')
            ->orderBy('subject_code')
            ->orderBy('session')
            ->paginate()
            ->withQueryString();

        return Inertia::render('examinations/Index', [
            'results' => ExaminationResultResource::collection($paginator),
            'filters' => $request->only(['search']),
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
