<?php

declare(strict_types=1);

namespace App\Http\Controllers\Institution\Departments;

use App\Actions\Institution\UpdateProgrammeStructureAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\UpdateProgrammeStructureRequest;
use App\Models\Institution\DepartmentLevelCourse;
use Illuminate\Http\RedirectResponse;

class DepartmentLevelCourseProgrammeStructureController extends Controller
{
    public function update(
        DepartmentLevelCourse $departmentLevelCourse,
        UpdateProgrammeStructureRequest $request,
        UpdateProgrammeStructureAction $updateProgrammeStructure,
    ): RedirectResponse {
        $updateProgrammeStructure->execute(
            $departmentLevelCourse,
            $request->validated(),
        );

        return back()->with('success', __('institution.programme_structure_updated'));
    }
}
