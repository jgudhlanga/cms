<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Enums\Students\ApplicationTrackEnum;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IModeOfStudyRepository;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationTrackSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeOfStudyController extends Controller
{
    public function __construct(protected IModeOfStudyRepository $repository) {}

    public function index(SharedNameFilter $filters): AnonymousResourceCollection
    {
        return ModeOfStudyResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function courseModes(DepartmentCourse $departmentCourse, DepartmentLevel $departmentLevel): AnonymousResourceCollection
    {
        $departmentLevel->loadMissing('level');

        $courseLevelModes = $departmentCourse->courseLevelModes()->where('department_level_id', $departmentLevel->id)->get();
        $modeObjects = $courseLevelModes->flatMap(fn ($clm) => $clm->mode_objects);

        $track = app(ApplicationTrackSession::class)->get();
        if ($track === ApplicationTrackEnum::Continuous && $departmentLevel->level !== null) {
            $eligibility = app(ApplicationEligibilityService::class);
            if (! $eligibility->isSdpLevel($departmentLevel->level)) {
                $modeObjects = $modeObjects->filter(
                    fn (ModeOfStudy $mode) => $eligibility->isOjetMode($mode),
                );
            }
        }

        return ModeOfStudyResource::collection($modeObjects->values());
    }

    public function store(Request $request) {}

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
