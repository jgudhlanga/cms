<?php

namespace App\Http\Controllers\Enrolments;

use App\DTO\Enrolments\ClassListDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\ClassListRequest;
use App\Models\Enrolments\ClassList;
use App\Repositories\Institution\interface\IClassListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use Throwable;

class ClassListController extends Controller
{
    public function __construct(protected IClassListRepository $repository)
    {
    }

    public function store(ClassListRequest $request)
    {
        try {
            $classList = $this->buildClassListDto($request->input('class_list', []), 'provisional');
            $waitingList = $this->buildClassListDto($request->input('waiting_list', []), 'waiting');

            DB::transaction(function () use ($classList, $waitingList) {
                collect([...$classList, ...$waitingList])
                    ->each(fn($dto) => $this->repository->create($dto));
            });

            return back()->with('success', 'Class lists created successfully.');
        } catch (Throwable $e) {
            Log::error('Failed to create class lists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An error occurred while creating class lists. All changes have been rolled back.');
        }
    }

    /**
     * Build an array of ClassListDto objects.
     */
    private function buildClassListDto(array $ids, string $type): array
    {
        $defaultAttributes = [
            'identity_confirmed' => false,
            'disability_confirmed' => false,
            'names_confirmed' => false,
            'o_level_confirmed' => false,
            'previous_level_confirmed' => false,
            'application_fee_confirmed' => false,
            'tuition_fee_confirmed' => false,
        ];

        return array_map(
            fn($id) => new ClassListDto(
                student_program_id: $id,
                type: $type,
                attributes: $defaultAttributes
            ),
            $ids
        );
    }


    public function update(Request $request, ClassList $classList)
    {

    }
}
