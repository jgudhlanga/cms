<?php

namespace App\Http\Controllers\Institution\Departments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\IntakePeriodClassSizeRequest;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DepartmentClassSizeController extends Controller
{

    public function index()
    {
        //
    }


    public function create()
    {
        //
    }


    /**
     * @throws Throwable
     */
    public function store(InstitutionDepartment $institutionDepartment, IntakePeriodClassSizeRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            foreach ($request->class_sizes as $entry) {
                $institutionDepartment->intakeClassSizes()->updateOrCreate([
                    'tenant_id' => auth()->user()->tenant_id,
                    'institution_department_id' => $institutionDepartment->id,
                    'intake_period_id' => $request->intake_period_id,
                    'mode_of_study_id' => $request->mode_of_study_id,
                    'department_course_id' => $entry['department_course_id'],
                    'department_level_id' => $entry['department_level_id'],
                ], [
                        'class_size' => $entry['class_size'] ?? 0,
                    ]
                );
            }
            DB::commit();
            return back()->with('success', 'Class sizes saved successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Class sizes saving failed failed', ['exception' => $e]);
            return back()->withErrors([
                'error' => 'An error occurred while submitting class sizes. Please try again.',
            ]);
        }
    }


    public function show(string $id)
    {
        //
    }


    public function edit(string $id)
    {
        //
    }


    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        //
    }
}
