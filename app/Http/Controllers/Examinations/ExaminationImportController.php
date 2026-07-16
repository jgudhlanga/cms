<?php

namespace App\Http\Controllers\Examinations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Examinations\ExaminationImportStoreRequest;
use App\Http\Resources\Examinations\ExaminationImportResource;
use App\Models\Examinations\ExaminationImport;
use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;
use App\Services\Examinations\ExaminationImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationImportController extends Controller
{
    public function __construct(private readonly ExaminationImportService $importService) {}

    public function create(Request $request): Response
    {
        $this->authorize('import', ExaminationResult::class);

        return Inertia::render('examinations/Import', [
            'canImport' => true,
        ]);
    }

    public function store(ExaminationImportStoreRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('import', ExaminationResult::class);

        /** @var User $user */
        $user = $request->user();

        $import = $this->importService->startFromUpload(
            $request->file('file'),
            $user,
        );

        $payload = ExaminationImportResource::make($import)->resolve();

        if ($request->wantsJson()) {
            return response()->json([
                'import' => $payload,
                'message' => __('examinations.import_queued'),
            ]);
        }

        return redirect()
            ->route('examinations.imports.show', $import)
            ->with('success', __('examinations.import_queued'));
    }

    public function index(Request $request): Response
    {
        $this->authorize('import', ExaminationResult::class);

        $paginator = ExaminationImport::query()
            ->with('starter:id,first_name,middle_name,last_name,email')
            ->latest('id')
            ->paginate()
            ->withQueryString();

        return Inertia::render('examinations/Imports/Index', [
            'imports' => ExaminationImportResource::collection($paginator),
        ]);
    }

    public function show(Request $request, ExaminationImport $examinationImport): JsonResponse|Response
    {
        $this->authorize('import', ExaminationResult::class);

        $payload = ExaminationImportResource::make(
            $examinationImport->loadMissing('starter:id,first_name,middle_name,last_name,email')
        )->resolve();

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json(['import' => $payload]);
        }

        return Inertia::render('examinations/Imports/Show', [
            'examinationImport' => $payload,
        ]);
    }
}
