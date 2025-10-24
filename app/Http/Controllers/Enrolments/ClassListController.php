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
            $classLists = [];
            if ($request->has('class_lists') && is_array($request->input('class_lists'))) {
                foreach ($request->input('class_lists') as $applicationId => $value) {
                    if ($value) {
                        $classLists[] = new ClassListDto(
                            student_program_id: $applicationId,
                            type: $request->get('type'),
                            attributes: [
                                'identity_confirmed' => false,
                                'disability_confirmed' => false,
                                'names_confirmed' => false,
                                'requirements_confirmed' => false,
                                'application_fee_confirmed' => false,
                                'tuition_fee_confirmed' => false,
                            ],
                        );
                    }
                }
            }

            DB::transaction(function () use ($classLists) {
                foreach ($classLists as $dto) {
                    $this->repository->create($dto);
                }
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

    public function update(Request $request, ClassList $classList)
    {

    }
}
